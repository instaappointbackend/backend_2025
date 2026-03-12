<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['appointment', 'user', 'provider']);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        } elseif ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date.' 00:00:00');
        } elseif ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date.' 23:59:59');
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Filter by provider
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Filter by customer
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                // Transaction ID
                $q->where('transaction_id', 'like', "%{$search}%")

                    // Customer (user)
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })

                    // Provider
                    ->orWhereHas('provider', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Default sorting
        $query->orderBy('created_at', 'desc');

        $payments = $query->paginate(15)->withQueryString();

        // Get all providers and customers for filters
        $providers = User::where('role', 'vendor')->get();
        $customers = User::where('role', 'customer')->get();

        // Get payment methods for filters
        $paymentMethods = Payment::distinct()->pluck('payment_method')->toArray();

        return view('admin.payments.index', compact(
            'payments',
            'providers',
            'customers',
            'paymentMethods'
        ));
    }

    /**
     * Display the payment details.
     */
    public function show(Payment $payment)
    {
        $payment->load(['appointment', 'appointment.service', 'appointment.comboService', 'user', 'provider']);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the payment.
     */
    public function edit(Payment $payment)
    {
        $payment->load(['appointment', 'user', 'provider']);

        return view('admin.payments.edit', compact('payment'));
    }

    /**
     * Update the payment status.
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Begin database transaction
        DB::beginTransaction();

        try {
            // Store old values for notification comparison
            $oldStatus = $payment->status;

            // Update payment details
            $updateData = [
                'status' => $validated['status'],
            ];

            if ($request->has('payment_method') && $request->payment_method) {
                $updateData['payment_method'] = $validated['payment_method'];
                $updateData['payment_mode'] = $validated['payment_method'];
            }

            if ($request->has('transaction_id') && $request->transaction_id) {
                $updateData['transaction_id'] = $validated['transaction_id'];
            }

            // Update payment details with notes if provided
            if ($request->has('notes') && $request->notes) {
                // Get existing payment details
                $paymentDetails = is_array($payment->payment_details)
                    ? $payment->payment_details
                    : json_decode($payment->payment_details ?? '{}', true) ?? [];

                // Add status update notes
                $statusUpdateNotes = [
                    'status_update' => [
                        'previous_status' => $payment->status,
                        'new_status' => $validated['status'],
                        'notes' => $validated['notes'],
                        'updated_by' => 'admin',
                        'updated_at' => now()->toIso8601String(),
                    ],
                ];

                $updateData['payment_details'] = array_merge($paymentDetails, $statusUpdateNotes);
            }

            // Apply updates to payment
            $payment->update($updateData);

            // Update appointment payment status
            if ($payment->appointment) {
                $payment->appointment->update([
                    'payment_status' => $validated['status'],
                    'payment_method' => $request->has('payment_method') ? $validated['payment_method'] : $payment->appointment->payment_method,
                    'payment_id' => $request->has('transaction_id') ? $validated['transaction_id'] : $payment->appointment->payment_id,
                ]);
            }

            // Send notifications if status changed
            if ($oldStatus !== $validated['status']) {
                $this->sendPaymentStatusNotifications($payment, $oldStatus, $validated['status']);
            }

            // Commit transaction
            DB::commit();

            return redirect()->route('admin.payments.show', $payment->id)
                ->with('success', 'Payment status updated successfully.');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error updating payment: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to update payment: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process refund for a payment.
     */
    public function refund(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'refund_amount' => 'required|numeric|min:0|max:'.$payment->amount,
            'refund_reason' => 'required|string|max:255',
        ]);

        // Begin database transaction
        DB::beginTransaction();

        try {
            // Only paid payments can be refunded
            if ($payment->status !== Payment::STATUS_PAID) {
                return redirect()->back()->with('error', 'Only paid payments can be refunded.');
            }

            // Generate refund ID
            $refundId = 'REF_'.uniqid();

            // Update payment status to refunded
            $refundDetails = [
                'refund' => [
                    'refund_id' => $refundId,
                    'amount' => $validated['refund_amount'],
                    'reason' => $validated['refund_reason'],
                    'initiated_by' => 'admin',
                    'initiated_at' => now()->toIso8601String(),
                    'status' => 'completed',
                ],
            ];

            // Merge with existing payment details
            $paymentDetails = is_array($payment->payment_details)
                ? $payment->payment_details
                : json_decode($payment->payment_details ?? '{}', true) ?? [];

            $updatedPaymentDetails = array_merge($paymentDetails, $refundDetails);

            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'payment_details' => $updatedPaymentDetails,
            ]);

            // Update appointment payment status
            if ($payment->appointment) {
                $payment->appointment->update([
                    'payment_status' => Payment::STATUS_REFUNDED,
                ]);
            }

            // Send refund notification
            $this->sendRefundNotification($payment, $validated['refund_amount'], $validated['refund_reason']);

            // Commit transaction
            DB::commit();

            return redirect()->route('admin.payments.show', $payment->id)
                ->with('success', 'Payment refunded successfully.');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error refunding payment: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to refund payment: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display payment reports.
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Convert to Carbon instances
        $startDateCarbon = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();

        // Get payment statistics
        $totalPayments = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])->count();
        $totalAmount = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        $platformFees = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->sum('platform_fee');

        $otherCharges = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->sum('other_charges');

        $gstAmount = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->sum('gst_amount');

        $adminEarnings = $platformFees + $otherCharges + $gstAmount;

        $vendorEarnings = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->sum('vendor_earnings');

        if ($vendorEarnings == 0) {
            $vendorEarnings = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
                ->where('status', Payment::STATUS_PAID)
                ->sum('booking_price');
        }

        // Get payment counts by status
        $pendingCount = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        $paidCount = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->count();

        $failedCount = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_FAILED)
            ->count();

        $refundedCount = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_REFUNDED)
            ->count();

        // Get payment counts by method
        $paymentMethods = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Get daily payment statistics for chart
        $dailyStats = [];
        $currentDate = clone $startDateCarbon;

        while ($currentDate <= $endDateCarbon) {
            $dayStart = (clone $currentDate)->startOfDay();
            $dayEnd = (clone $currentDate)->endOfDay();

            $dayAmount = Payment::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', Payment::STATUS_PAID)
                ->sum('amount');

            $dailyStats[] = [
                'date' => $currentDate->format('Y-m-d'),
                'formatted_date' => $currentDate->format('M d'),
                'amount' => $dayAmount,
                'formatted_amount' => '₹'.number_format($dayAmount, 2),
            ];

            $currentDate->addDay();
        }

        // Get top vendors by earnings
        $topVendors = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', Payment::STATUS_PAID)
            ->select('provider_id', DB::raw('sum(vendor_earnings) as earnings'), DB::raw('count(*) as transactions'))
            ->with('provider')
            ->groupBy('provider_id')
            ->orderBy('earnings', 'desc')
            ->take(10)
            ->get();

        if ($topVendors->sum('earnings') == 0) {
            $topVendors = Payment::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
                ->where('status', Payment::STATUS_PAID)
                ->select('provider_id', DB::raw('sum(booking_price) as earnings'), DB::raw('count(*) as transactions'))
                ->with('provider')
                ->groupBy('provider_id')
                ->orderBy('earnings', 'desc')
                ->take(10)
                ->get();
        }

        return view('admin.payments.reports', compact(
            'startDate',
            'endDate',
            'totalPayments',
            'totalAmount',
            'platformFees',
            'otherCharges',
            'gstAmount',
            'adminEarnings',
            'vendorEarnings',
            'pendingCount',
            'paidCount',
            'failedCount',
            'refundedCount',
            'paymentMethods',
            'dailyStats',
            'topVendors'
        ));
    }

    /**
     * Export payments data.
     */
    public function export(Request $request)
    {
        $query = Payment::with(['appointment', 'user', 'provider']);

        // Apply filters (same as index method)
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method') && $request->payment_method != 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date.' 00:00:00', $request->end_date.' 23:59:59']);
        } elseif ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date.' 00:00:00');
        } elseif ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date.' 23:59:59');
        }

        if ($request->has('provider_id') && $request->provider_id) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Get all payments
        $payments = $query->get();

        // Create CSV content
        $csvData = [];

        // Add CSV headers
        $csvData[] = [
            'Transaction ID',
            'Date',
            'Customer',
            'Provider',
            'Service',
            'Amount',
            'Payment Method',
            'Status',
            'Platform Fee',
            'GST',
            'Provider Earnings',
        ];

        // Add payment rows
        foreach ($payments as $payment) {
            $serviceName = '';
            if ($payment->appointment) {
                if ($payment->appointment->service) {
                    $serviceName = $payment->appointment->service->name;
                } elseif ($payment->appointment->comboService) {
                    $serviceName = $payment->appointment->comboService->name;
                }
            }

            $csvData[] = [
                $payment->transaction_id,
                $payment->created_at->format('Y-m-d H:i:s'),
                $payment->user ? $payment->user->name : 'N/A',
                $payment->provider ? $payment->provider->name : 'N/A',
                $serviceName,
                $payment->amount,
                $payment->getPaymentMethodDisplayAttribute(),
                $payment->getHumanStatusAttribute(),
                $payment->platform_fee,
                $payment->gst_amount,
                $payment->vendor_earnings ?? $payment->booking_price,
            ];
        }

        // Create unique filename
        $filename = 'payments_export_'.date('Y-m-d_H-i-s').'.csv';
        $filepath = storage_path('app/public/exports/'.$filename);

        // Ensure directory exists
        if (! file_exists(storage_path('app/public/exports/'))) {
            mkdir(storage_path('app/public/exports/'), 0755, true);
        }

        // Create the file
        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Return download response
        return response()->download($filepath, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Send payment status change notifications.
     */
    private function sendPaymentStatusNotifications(Payment $payment, $oldStatus, $newStatus)
    {
        // Load needed relations if not already loaded
        if (
            ! $payment->relationLoaded('appointment') ||
            ! $payment->relationLoaded('user') ||
            ! $payment->relationLoaded('provider')
        ) {
            $payment->load(['appointment', 'user', 'provider']);
        }

        if ($payment->appointment) {
            if (
                ! $payment->appointment->relationLoaded('service') &&
                ! $payment->appointment->relationLoaded('comboService')
            ) {
                $payment->appointment->load(['service', 'comboService']);
            }
        }

        // Get service name
        $serviceName = '';
        if ($payment->appointment) {
            if ($payment->appointment->service) {
                $serviceName = $payment->appointment->service->name;
            } elseif ($payment->appointment->comboService) {
                $serviceName = $payment->appointment->comboService->name;
            }
        }

        // Format date for notifications
        $appointmentDate = $payment->appointment ? $payment->appointment->date->format('M d, Y') : 'N/A';

        switch ($newStatus) {
            case Payment::STATUS_PAID:
                // Payment marked as paid - notify both provider and customer

                // Notify provider
                if ($payment->provider) {
                    $providerTitle = 'Payment Received';
                    $providerBody = "Payment received for appointment with {$payment->user->name} for {$serviceName} on {$appointmentDate}.";
                    $providerData = [
                        'type' => 'payment_received',
                        'appointment_id' => $payment->appointment_id,
                        'appointment_date' => $payment->appointment ? $payment->appointment->date : null,
                        'client_id' => $payment->user_id,
                        'client_name' => $payment->user ? $payment->user->name : 'Customer',
                        'service_name' => $serviceName,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'payment_id' => $payment->transaction_id,
                        'appointmentId' => $payment->appointment_id,
                        'screenName' => 'AppointmentDetails',
                    ];

                    $this->notificationService->sendPushNotification(
                        $payment->provider_id,
                        $providerTitle,
                        $providerBody,
                        $providerData
                    );
                }

                // Notify customer
                if ($payment->user) {
                    $clientTitle = 'Payment Successful';
                    $clientBody = "Your payment for the appointment with {$payment->provider->name} for {$serviceName} on {$appointmentDate} was successful.";
                    $clientData = [
                        'type' => 'payment_successful',
                        'appointment_id' => $payment->appointment_id,
                        'appointment_date' => $payment->appointment ? $payment->appointment->date : null,
                        'provider_id' => $payment->provider_id,
                        'provider_name' => $payment->provider ? $payment->provider->name : 'Provider',
                        'service_name' => $serviceName,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'payment_id' => $payment->transaction_id,
                        'appointmentId' => $payment->appointment_id,
                        'screenName' => 'AppointmentDetails',
                    ];

                    $this->notificationService->sendPushNotification(
                        $payment->user_id,
                        $clientTitle,
                        $clientBody,
                        $clientData
                    );
                }
                break;

            case Payment::STATUS_FAILED:
                // Payment marked as failed - notify customer
                if ($payment->user) {
                    $clientTitle = 'Payment Failed';
                    $clientBody = "Your payment for the appointment with {$payment->provider->name} for {$serviceName} on {$appointmentDate} has failed. Please try again.";
                    $clientData = [
                        'type' => 'payment_failed',
                        'appointment_id' => $payment->appointment_id,
                        'appointment_date' => $payment->appointment ? $payment->appointment->date : null,
                        'provider_id' => $payment->provider_id,
                        'provider_name' => $payment->provider ? $payment->provider->name : 'Provider',
                        'service_name' => $serviceName,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'appointmentId' => $payment->appointment_id,
                        'screenName' => 'AppointmentDetails',
                    ];

                    $this->notificationService->sendPushNotification(
                        $payment->user_id,
                        $clientTitle,
                        $clientBody,
                        $clientData
                    );
                }
                break;

            case Payment::STATUS_REFUNDED:
                // Payment marked as refunded - handled by refund method
                break;
        }
    }

    /**
     * Send refund notification.
     */
    private function sendRefundNotification(Payment $payment, $refundAmount, $reason)
    {
        // Load needed relations if not already loaded
        if (
            ! $payment->relationLoaded('appointment') ||
            ! $payment->relationLoaded('user') ||
            ! $payment->relationLoaded('provider')
        ) {
            $payment->load(['appointment', 'user', 'provider']);
        }

        if ($payment->appointment) {
            if (
                ! $payment->appointment->relationLoaded('service') &&
                ! $payment->appointment->relationLoaded('comboService')
            ) {
                $payment->appointment->load(['service', 'comboService']);
            }
        }

        // Get service name
        $serviceName = '';
        if ($payment->appointment) {
            if ($payment->appointment->service) {
                $serviceName = $payment->appointment->service->name;
            } elseif ($payment->appointment->comboService) {
                $serviceName = $payment->appointment->comboService->name;
            }
        }

        // Format date for notifications
        $appointmentDate = $payment->appointment ? $payment->appointment->date->format('M d, Y') : 'N/A';

        // Notify customer
        if ($payment->user) {
            $clientTitle = 'Payment Refunded';
            $clientBody = "Your payment for the appointment with {$payment->provider->name} for {$serviceName} on {$appointmentDate} has been refunded.";
            $clientData = [
                'type' => 'payment_refunded',
                'appointment_id' => $payment->appointment_id,
                'appointment_date' => $payment->appointment ? $payment->appointment->date : null,
                'provider_id' => $payment->provider_id,
                'provider_name' => $payment->provider ? $payment->provider->name : 'Provider',
                'service_name' => $serviceName,
                'amount' => $refundAmount,
                'reason' => $reason,
                'payment_method' => $payment->payment_method,
                'refund_id' => 'REF_'.$payment->transaction_id,
                'appointmentId' => $payment->appointment_id,
                'screenName' => 'AppointmentDetails',
            ];

            $this->notificationService->sendPushNotification(
                $payment->user_id,
                $clientTitle,
                $clientBody,
                $clientData
            );
        }

        // Notify provider
        if ($payment->provider) {
            $providerTitle = 'Payment Refunded';
            $providerBody = "Payment for appointment with {$payment->user->name} for {$serviceName} on {$appointmentDate} has been refunded.";
            $providerData = [
                'type' => 'payment_refunded_admin',
                'appointment_id' => $payment->appointment_id,
                'appointment_date' => $payment->appointment ? $payment->appointment->date : null,
                'client_id' => $payment->user_id,
                'client_name' => $payment->user ? $payment->user->name : 'Customer',
                'service_name' => $serviceName,
                'amount' => $refundAmount,
                'reason' => $reason,
                'payment_method' => $payment->payment_method,
                'refund_id' => 'REF_'.$payment->transaction_id,
                'appointmentId' => $payment->appointment_id,
                'screenName' => 'AppointmentDetails',
            ];

            $this->notificationService->sendPushNotification(
                $payment->provider_id,
                $providerTitle,
                $providerBody,
                $providerData
            );
        }
    }
}
