<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\ReceiptService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Save payment details after successful payment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'transaction_id' => 'required|string',
            'payment_method' => 'required|string',
            'payment_mode' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'required|in:'.implode(',', [
                Payment::STATUS_PENDING,
                Payment::STATUS_PAID,
                Payment::STATUS_FAILED,
                Payment::STATUS_REFUNDED,
            ]),
            'payment_details' => 'nullable|json',

            // Detailed payment breakdown fields
            'booking_price' => 'nullable|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'gst_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0',
            'home_visit_fee' => 'nullable|numeric|min:0',
            'additional_services_fee' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',

            // Discount and offer related fields
            'coupon_code' => 'nullable|string',
            'offer_title' => 'nullable|string',
            'vendor_offer_id' => 'nullable|numeric',
            'admin_offer_id' => 'nullable|numeric',
            'offer_type' => 'nullable|in:vendor,admin,coupon',

            // Additional notes
            'additional_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            // Begin database transaction
            DB::beginTransaction();

            $userId = Auth::id();

            // Get the appointment
            $appointment = Appointment::findOrFail($request->appointment_id);

            // Check if user is authorized to save payment for this appointment
            if ($appointment->client_id !== $userId && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized to save payment for this appointment.', 403);
            }

            // Check if payment already exists for this appointment
            $existingPayment = Payment::where('appointment_id', $request->appointment_id)->first();
            if ($existingPayment) {
                return $this->error([], 'Payment already exists for this appointment.', 422);
            }

            $paymentCalculation = $this->calculatePaymentBreakdown([
                'original_price' => $request->original_price ?? ($request->booking_price ?? 0),
                'home_visit_fee' => $request->home_visit_fee ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
            ]);

            // Prepare payment data
            $paymentData = [
                'appointment_id' => $request->appointment_id,
                'user_id' => $appointment->client_id,
                'provider_id' => $appointment->user_id,
                'transaction_id' => $request->transaction_id,
                'payment_method' => $request->payment_method,
                'payment_mode' => $request->payment_mode ?? $request->payment_method,
                'currency' => $request->currency,
                'status' => $request->status,
                'payment_details' => $request->payment_details,

                // Use calculated values for payment breakdown
                'original_price' => $paymentCalculation['original_price'],
                'booking_price' => $paymentCalculation['booking_price'],
                'platform_fee' => $paymentCalculation['platform_fee'],
                'other_charges' => $paymentCalculation['other_charges'],
                'gst_amount' => $paymentCalculation['gst_amount'],
                'discount_amount' => $paymentCalculation['discount_amount'],
                'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                'amount' => $paymentCalculation['amount'],
                'net_amount' => $paymentCalculation['net_amount'],
                'vendor_earnings' => $paymentCalculation['vendor_earnings'],
                'admin_earnings' => $paymentCalculation['admin_earnings'],
            ];

            // Add detailed payment breakdown fields if provided
            foreach ([
                'booking_price', 'platform_fee', 'other_charges', 'gst_amount',
                'discount_amount', 'discount_percentage', 'home_visit_fee',
                'additional_services_fee', 'net_amount', 'coupon_code',
                'offer_title', 'vendor_offer_id', 'admin_offer_id', 'offer_type',
                'additional_notes',
            ] as $field) {
                if ($request->has($field)) {
                    $paymentData[$field] = $request->$field;
                }
            }

            // Calculate net amount if not provided
            if (! $request->has('net_amount')) {
                // Net amount calculation logic
                $netAmount = $request->amount;

                // Apply discounts if present
                if ($request->has('discount_amount') && $request->discount_amount > 0) {
                    $netAmount -= $request->discount_amount;
                }

                $paymentData['net_amount'] = $netAmount;
            }

            // Create the payment record
            $payment = Payment::create($paymentData);

            // Update appointment payment information
            $appointmentUpdateData = [
                'payment_status' => $request->status,
                'payment_id' => $request->transaction_id,
                'payment_method' => $request->payment_method,
                'payment_mode' => $request->payment_mode ?? $request->payment_method,
                'payment_amount' => $request->amount,
            ];

            // Map payment fields to appointment fields
            $fieldMappings = [
                'discount_amount' => 'discount_amount',
                'discount_percentage' => 'discount_percentage',
                'coupon_code' => 'coupon_code',
                'booking_price' => 'original_price',
                'offer_title' => 'offer_title',
                'vendor_offer_id' => 'vendor_offer_id',
                'admin_offer_id' => 'admin_offer_id',
                'offer_type' => 'offer_type',
                'home_visit_fee' => 'home_visit_fee',
                'additional_services_fee' => 'additional_services_fee',
                'additional_notes' => 'notes',
                'net_amount' => 'final_price',
                'platform_fee' => 'platform_fees',
                'other_charges' => 'other_charges',
                'gst_amount' => 'gst',
            ];

            foreach ($fieldMappings as $paymentField => $appointmentField) {
                if ($request->has($paymentField)) {
                    $appointmentUpdateData[$appointmentField] = $request->$paymentField;
                } elseif (isset($paymentData[$paymentField])) {
                    $appointmentUpdateData[$appointmentField] = $paymentData[$paymentField];
                }
            }

            $appointment->update($appointmentUpdateData);

            // Commit transaction
            DB::commit();

            // Load relations
            $payment->load(['appointment', 'user', 'provider']);
            $payment->appointment->load(['service', 'client', 'provider']);

            // Return success response with resource
            return $this->success(new PaymentResource($payment), 'Payment details saved successfully.', 201);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error saving payment details: '.$e->getMessage());

            return $this->error([], 'Failed to save payment details: '.$e->getMessage(), 500);
        }
    }

    /**
     * Verify payment status with payment gateway.
     *
     * @param  string  $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment($transactionId)
    {
        try {
            Log::info('Verifying payment for transaction: '.$transactionId);

            // In a production environment, you would make an API call to payment gateway's verification endpoint
            // This is a placeholder implementation for development/testing purposes

            // For development mode, all transactions are considered successful
            $isDevelopmentMode = config('app.env') !== 'production';

            if ($isDevelopmentMode) {
                $verificationResult = [
                    'status' => 'SUCCESS',
                    'transaction_id' => $transactionId,
                    'verified' => true,
                    'amount' => null,
                    'currency' => 'INR',
                    'payment_method' => 'PhonePe',
                    'message' => 'Payment verified in development mode',
                ];

                // Check if payment exists in our database
                $payment = Payment::where('transaction_id', $transactionId)->first();
                if ($payment) {
                    $verificationResult['amount'] = $payment->amount;
                    $verificationResult['payment_method'] = $payment->payment_method;
                    $verificationResult['payment_method_display'] = $payment->getPaymentMethodDisplayAttribute();
                    $verificationResult['formatted_amount'] = $payment->getFormattedAmountAttribute();
                }

                return $this->success($verificationResult, 'Payment verified successfully.');
            }

            // Default fallback response
            return $this->error([], 'Payment verification not implemented in this environment.', 501);

        } catch (\Exception $e) {
            Log::error('Payment verification error: '.$e->getMessage());

            return $this->error([], 'Payment verification failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get payment history for the authenticated user with date filtering support.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $userId = Auth::id();

            // Get optional filters from request
            $status = $request->query('status');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $perPage = $request->query('per_page', 15);

            // Base query to get payments for the current user
            $query = Payment::with(['appointment', 'appointment.service', 'appointment.provider', 'provider'])
                ->where('user_id', $userId);

            // Apply status filter if provided
            if ($status && in_array($status, [
                Payment::STATUS_PENDING,
                Payment::STATUS_PAID,
                Payment::STATUS_FAILED,
                Payment::STATUS_REFUNDED,
            ])) {
                $query->where('status', $status);
            }

            // Apply date range filter if provided
            if ($startDate && $endDate) {
                $startDate = Carbon::parse($startDate)->startOfDay();
                $endDate = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Order by most recent first
            $query->orderBy('created_at', 'desc');

            // Paginate the results if requested
            if ($perPage === 'all') {
                $payments = $query->get();
                $result = PaymentResource::collection($payments);
            } else {
                $payments = $query->paginate($perPage);
                $result = PaymentResource::collection($payments);
            }

            // Return the response
            return $this->success($result, 'Payment history retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error retrieving payment history: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve payment history: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get payment history for the provider (vendor earnings).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProviderPayments(Request $request)
    {
        try {
            $userId = Auth::id();

            // Get optional filters from request
            $status = $request->query('status');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $perPage = $request->query('per_page', 15);

            // Base query to get payments where the user is the provider
            $query = Payment::with(['appointment', 'appointment.service', 'appointment.comboService', 'appointment.client', 'user'])
                ->where('provider_id', $userId);

            // Apply status filter if provided
            if ($status && in_array($status, [
                Payment::STATUS_PENDING,
                Payment::STATUS_PAID,
                Payment::STATUS_FAILED,
                Payment::STATUS_REFUNDED,
            ])) {
                $query->where('status', $status);
            }

            // Apply date range filter if provided
            if ($startDate && $endDate) {
                $startDate = Carbon::parse($startDate)->startOfDay();
                $endDate = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Order by most recent first
            $query->orderBy('created_at', 'desc');

            // Paginate the results if requested
            if ($perPage === 'all') {
                $payments = $query->get();
                $result = PaymentResource::collection($payments);
            } else {
                $payments = $query->paginate($perPage);
                $result = PaymentResource::collection($payments);
            }
            //            echo "<pre>";
            //            print_r($result);die;

            // Return the response
            return $this->success($result, 'Provider payment history retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error retrieving provider payments: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve provider payment history: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get payment statistics for provider (earnings summary).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProviderPaymentStats(Request $request)
    {
        try {
            $userId = Auth::id();

            // Get date range from request or default to current month
            $startDate = $request->query('start_date')
                ? Carbon::parse($request->query('start_date'))->startOfDay()
                : Carbon::now()->startOfMonth();

            $endDate = $request->query('end_date')
                ? Carbon::parse($request->query('end_date'))->endOfDay()
                : Carbon::now()->endOfDay();

            // Base query for consistent filters
            $baseQuery = Payment::where('provider_id', $userId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Get payment counts by status
            $paidCount = (clone $baseQuery)->where('status', Payment::STATUS_PAID)->count();
            $pendingCount = (clone $baseQuery)->where('status', Payment::STATUS_PENDING)->count();
            $failedCount = (clone $baseQuery)->where('status', Payment::STATUS_FAILED)->count();
            $refundedCount = (clone $baseQuery)->where('status', Payment::STATUS_REFUNDED)->count();

            // Get total payments count
            $totalPayments = $paidCount + $pendingCount + $failedCount + $refundedCount;

            // Get vendor earnings (total and by status)
            $vendorTotalEarnings = (clone $baseQuery)
                ->where('status', Payment::STATUS_PAID)
                ->sum('vendor_earnings');

            // If no vendor_earnings field values exist yet, use booking_price as fallback
            if ($vendorTotalEarnings == 0) {
                $vendorTotalEarnings = (clone $baseQuery)
                    ->where('status', Payment::STATUS_PAID)
                    ->sum('booking_price');
            }

            // Get pending vendor earnings
            $vendorPendingAmount = (clone $baseQuery)
                ->where('status', Payment::STATUS_PENDING)
                ->sum('vendor_earnings');

            // If no vendor_earnings field values exist yet, use booking_price as fallback
            if ($vendorPendingAmount == 0) {
                $vendorPendingAmount = (clone $baseQuery)
                    ->where('status', Payment::STATUS_PENDING)
                    ->sum('booking_price');
            }

            // Get refunded vendor earnings
            $vendorRefundedAmount = (clone $baseQuery)
                ->where('status', Payment::STATUS_REFUNDED)
                ->sum('vendor_earnings');

            // If no vendor_earnings field values exist yet, use booking_price as fallback
            if ($vendorRefundedAmount == 0) {
                $vendorRefundedAmount = (clone $baseQuery)
                    ->where('status', Payment::STATUS_REFUNDED)
                    ->sum('booking_price');
            }

            // Get total transaction amounts (for reference only)
            $totalTransactionAmount = (clone $baseQuery)
                ->where('status', Payment::STATUS_PAID)
                ->sum('amount');

            $pendingTransactionAmount = (clone $baseQuery)
                ->where('status', Payment::STATUS_PENDING)
                ->sum('amount');

            $refundedTransactionAmount = (clone $baseQuery)
                ->where('status', Payment::STATUS_REFUNDED)
                ->sum('amount');

            // Get platform fees and GST (these should not be part of vendor earnings)
            $platformFees = (clone $baseQuery)
                ->where('status', Payment::STATUS_PAID)
                ->sum('platform_fee');

            $otherCharges = (clone $baseQuery)
                ->where('status', Payment::STATUS_PAID)
                ->sum('other_charges');

            $gstAmount = (clone $baseQuery)
                ->where('status', Payment::STATUS_PAID)
                ->sum('gst_amount');

            // Admin earnings (for reference)
            $adminEarnings = $platformFees + $otherCharges + $gstAmount;

            // Get payment counts by status
            $paymentCounts = [
                'total' => $totalPayments,
                'paid' => $paidCount,
                'pending' => $pendingCount,
                'failed' => $failedCount,
                'refunded' => $refundedCount,
            ];

            // Get monthly breakdown for the selected period
            $monthlyData = [];
            $currentDate = clone $startDate;

            while ($currentDate <= $endDate) {
                $monthStart = (clone $currentDate)->startOfMonth();
                $monthEnd = (clone $currentDate)->endOfMonth();

                // If end date is before the end of this month, use the end date
                if ($endDate < $monthEnd) {
                    $monthEnd = clone $endDate;
                }

                // Monthly base query
                $monthlyBaseQuery = Payment::where('provider_id', $userId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd]);

                // Get vendor earnings for the month
                $monthlyVendorEarnings = (clone $monthlyBaseQuery)
                    ->where('status', Payment::STATUS_PAID)
                    ->sum('vendor_earnings');

                // If no vendor_earnings field values exist yet, use booking_price as fallback
                if ($monthlyVendorEarnings == 0) {
                    $monthlyVendorEarnings = (clone $monthlyBaseQuery)
                        ->where('status', Payment::STATUS_PAID)
                        ->sum('booking_price');
                }

                // Get total transaction amount for reference
                $monthlyTransactionAmount = (clone $monthlyBaseQuery)
                    ->where('status', Payment::STATUS_PAID)
                    ->sum('amount');

                $monthlyData[] = [
                    'month' => $monthStart->format('M Y'),
                    'vendor_earnings' => $monthlyVendorEarnings,
                    'formatted_vendor_earnings' => '₹'.number_format($monthlyVendorEarnings, 2),
                    'transaction_amount' => $monthlyTransactionAmount,
                    'formatted_transaction_amount' => '₹'.number_format($monthlyTransactionAmount, 2),
                ];

                // Move to next month
                $currentDate->addMonth();
            }

            // Get payment method breakdown
            $paymentMethods = (clone $baseQuery)
                ->where('status', Payment::STATUS_PAID)
                ->select('payment_method', DB::raw('count(*) as count'))
                ->groupBy('payment_method')
                ->get();

            // Add vendor earnings to payment methods
            foreach ($paymentMethods as $method) {
                $methodVendorEarnings = (clone $baseQuery)
                    ->where('status', Payment::STATUS_PAID)
                    ->where('payment_method', $method->payment_method)
                    ->sum('vendor_earnings');

                // If no vendor_earnings field values exist yet, use booking_price as fallback
                if ($methodVendorEarnings == 0) {
                    $methodVendorEarnings = (clone $baseQuery)
                        ->where('status', Payment::STATUS_PAID)
                        ->where('payment_method', $method->payment_method)
                        ->sum('booking_price');
                }

                // Get total transaction amount for reference
                $methodTransactionAmount = (clone $baseQuery)
                    ->where('status', Payment::STATUS_PAID)
                    ->where('payment_method', $method->payment_method)
                    ->sum('amount');

                $method->vendor_earnings = $methodVendorEarnings;
                $method->formatted_vendor_earnings = '₹'.number_format($methodVendorEarnings, 2);
                $method->transaction_amount = $methodTransactionAmount;
                $method->formatted_transaction_amount = '₹'.number_format($methodTransactionAmount, 2);
            }

            $formattedPaymentMethods = $paymentMethods->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'method_display' => (new Payment(['payment_method' => $item->payment_method]))->getPaymentMethodDisplayAttribute(),
                    'count' => $item->count,
                    'vendor_earnings' => $item->vendor_earnings,
                    'formatted_vendor_earnings' => $item->formatted_vendor_earnings,
                    'transaction_amount' => $item->transaction_amount,
                    'formatted_transaction_amount' => $item->formatted_transaction_amount,
                ];
            });

            // Prepare the response
            $stats = [
                'date_range' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'formatted_range' => $startDate->format('d M Y').' - '.$endDate->format('d M Y'),
                ],
                'earnings' => [
                    // Vendor earnings (what vendors actually receive)
                    'total' => $vendorTotalEarnings,
                    'formatted_total' => '₹'.number_format($vendorTotalEarnings, 2),
                    'pending' => $vendorPendingAmount,
                    'formatted_pending' => '₹'.number_format($vendorPendingAmount, 2),
                    'refunded' => $vendorRefundedAmount,
                    'formatted_refunded' => '₹'.number_format($vendorRefundedAmount, 2),
                    'net' => $vendorTotalEarnings, // Net earnings for vendor is the vendor earnings
                    'formatted_net' => '₹'.number_format($vendorTotalEarnings, 2),

                    // Admin earnings (for reference)
                    'admin_earnings' => $adminEarnings,
                    'formatted_admin_earnings' => '₹'.number_format($adminEarnings, 2),
                    'platform_fees' => $platformFees,
                    'formatted_platform_fees' => '₹'.number_format($platformFees, 2),
                    'other_charges' => $otherCharges,
                    'formatted_other_charges' => '₹'.number_format($otherCharges, 2),
                    'gst' => $gstAmount,
                    'formatted_gst' => '₹'.number_format($gstAmount, 2),

                    // Total transaction amount (for reference)
                    'transaction_amount' => $totalTransactionAmount,
                    'formatted_transaction_amount' => '₹'.number_format($totalTransactionAmount, 2),
                    'pending_transaction_amount' => $pendingTransactionAmount,
                    'formatted_pending_transaction_amount' => '₹'.number_format($pendingTransactionAmount, 2),
                    'refunded_transaction_amount' => $refundedTransactionAmount,
                    'formatted_refunded_transaction_amount' => '₹'.number_format($refundedTransactionAmount, 2),
                ],
                'counts' => $paymentCounts,
                'monthly_data' => $monthlyData,
                'payment_methods' => $formattedPaymentMethods,
            ];

            return $this->success($stats, 'Provider payment statistics retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error retrieving provider payment statistics: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve provider payment statistics: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get payment details for a specific appointment.
     *
     * @param  int  $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentByAppointment($appointmentId)
    {
        try {
            $userId = Auth::id();

            $appointment = Appointment::findOrFail($appointmentId);

            // Check if user is authorized to view this appointment's payment
            if ($appointment->client_id !== $userId && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized to view payment for this appointment.', 403);
            }

            $payment = Payment::where('appointment_id', $appointmentId)
                ->with(['appointment', 'appointment.service', 'appointment.client', 'appointment.provider', 'user', 'provider'])
                ->first();

            if (! $payment) {
                return $this->error([], 'No payment found for this appointment.', 404);
            }

            return $this->success(new PaymentResource($payment), 'Payment details retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error retrieving payment by appointment: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve payment details: '.$e->getMessage());
        }
    }

    /**
     * Initiate refund for a payment.
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiateRefund(Request $request, $paymentId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $userId = Auth::id();
            $payment = Payment::findOrFail($paymentId);

            // Only service providers can initiate refunds
            if ($payment->provider_id !== $userId) {
                return $this->error([], 'Unauthorized to initiate refund for this payment.', 403);
            }

            // Check if payment is eligible for refund
            if ($payment->status !== Payment::STATUS_PAID) {
                return $this->error([], 'Only successful payments can be refunded.', 422);
            }

            // Determine refund amount
            $refundAmount = $request->amount ?? $payment->amount;

            // Validate refund amount cannot exceed original payment amount
            if ($refundAmount > $payment->amount) {
                return $this->error([], 'Refund amount cannot exceed the original payment amount.', 422);
            }

            // In a production environment, you would make an API call to payment gateway's refund endpoint
            // This is a placeholder implementation for development/testing purposes

            // Generate a unique refund ID
            $refundId = 'REF_'.uniqid();

            // Update payment status to refunded
            $refundDetails = [
                'refund' => [
                    'refund_id' => $refundId,
                    'amount' => $refundAmount,
                    'reason' => $request->reason,
                    'initiated_by' => $userId,
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

            DB::commit();

            // Load relationships for response
            $payment->load(['appointment', 'user', 'provider']);

            return $this->success(new PaymentResource($payment), 'Refund initiated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error initiating refund: '.$e->getMessage());

            return $this->error([], 'Failed to initiate refund: '.$e->getMessage(), 500);
        }
    }

    /**
     * Export payment history for provider (vendor earnings).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportProviderPayments(Request $request)
    {
        try {
            $userId = Auth::id();

            // Get optional filters from request
            $status = $request->query('status');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            // Base query to get payments where the user is the provider
            $query = Payment::with(['appointment', 'appointment.service', 'appointment.client', 'user'])
                ->where('provider_id', $userId);

            // Apply status filter if provided
            if ($status && in_array($status, [
                Payment::STATUS_PENDING,
                Payment::STATUS_PAID,
                Payment::STATUS_FAILED,
                Payment::STATUS_REFUNDED,
            ])) {
                $query->where('status', $status);
            }

            // Apply date range filter if provided
            if ($startDate && $endDate) {
                $startDate = Carbon::parse($startDate)->startOfDay();
                $endDate = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Order by most recent first
            $query->orderBy('created_at', 'desc');

            // Get the results
            $payments = $query->get();

            // Prepare CSV data
            $csvData = [];

            // CSV Header
            $csvData[] = [
                'Transaction ID',
                'Service',
                'Client Name',
                'Client Email',
                'Date',
                'Amount',
                'Net Amount',
                'Platform Fee',
                'GST',
                'Currency',
                'Payment Method',
                'Status',
                'Discount Amount',
                'Coupon Code',
                'Offer Title',
            ];

            // CSV rows
            foreach ($payments as $payment) {
                $csvData[] = [
                    $payment->transaction_id,
                    $payment->appointment && $payment->appointment->service ? $payment->appointment->service->name : 'N/A',
                    $payment->appointment && $payment->appointment->client ? $payment->appointment->client->name : 'N/A',
                    $payment->appointment && $payment->appointment->client ? $payment->appointment->client->email : 'N/A',
                    $payment->created_at->format('Y-m-d H:i:s'),
                    $payment->amount,
                    $payment->net_amount,
                    $payment->platform_fee,
                    $payment->gst_amount,
                    $payment->currency,
                    $payment->getPaymentMethodDisplayAttribute(),
                    $payment->getHumanStatusAttribute(),
                    $payment->discount_amount,
                    $payment->coupon_code ?? 'N/A',
                    $payment->offer_title ?? 'N/A',
                ];
            }

            // Create a unique filename
            $filename = 'earnings_export_'.time().'.csv';
            $filepath = storage_path('app/public/exports/'.$filename);

            // Make sure the directory exists
            if (! file_exists(storage_path('app/public/exports/'))) {
                mkdir(storage_path('app/public/exports/'), 0755, true);
            }

            // Create the CSV file
            $file = fopen($filepath, 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            // Generate a download URL
            $downloadUrl = asset('storage/exports/'.$filename);

            return $this->success(['downloadUrl' => $downloadUrl], 'Payment export created successfully');
        } catch (\Exception $e) {
            Log::error('Error exporting provider payments: '.$e->getMessage());

            return $this->error([], 'Failed to export provider payment history: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get detailed information for a specific payment.
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentDetails($paymentId)
    {
        try {
            $userId = Auth::id();

            // Find the payment with appointment and related data
            $payment = Payment::with([
                'appointment',
                'appointment.service',
                'appointment.client',
                'appointment.provider',
                'user',
                'provider',
            ])->findOrFail($paymentId);

            // Check if user is authorized to view this payment
            // Allow both the provider and the client to view the payment
            if ($payment->user_id !== $userId && $payment->provider_id !== $userId) {
                return $this->error([], 'Unauthorized to view this payment.', 403);
            }

            // Return the payment resource
            return $this->success(new PaymentResource($payment), 'Payment details retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error getting payment details: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve payment details: '.$e->getMessage(), 500);
        }
    }

    /**
     * Generate a receipt for a payment.
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateReceipt($paymentId)
    {
        try {
            $userId = Auth::id();

            // Find the payment with appointment and related data
            $payment = Payment::with([
                'appointment',
                'appointment.service',
                'appointment.client',
                'provider',
            ])->findOrFail($paymentId);

            // Check if user is authorized to view this payment
            // Allow both the provider and the client to view the receipt
            if ($payment->user_id !== $userId && $payment->provider_id !== $userId) {
                return $this->error([], 'Unauthorized to generate receipt for this payment.', 403);
            }

            // Get business details for the provider
            $businessDetails = null;

            if ($payment->provider) {
                $providerKyc = $payment->provider->kycDocument()->first();

                if ($providerKyc) {
                    $businessDetails = [
                        'business_name' => $providerKyc->business_name ?? $payment->provider->name,
                        'address' => $providerKyc->business_address ?? '',
                        'phone' => $payment->provider->phone ?? '',
                        'email' => $payment->provider->email ?? '',
                    ];
                } else {
                    $businessDetails = [
                        'business_name' => $payment->provider->name ?? 'Business',
                        'address' => $payment->provider->address ?? '',
                        'phone' => $payment->provider->phone ?? '',
                        'email' => $payment->provider->email ?? '',
                    ];
                }
            }

            // Get payment breakdown
            $paymentBreakdown = $payment->getPaymentBreakdownAttribute();

            // Prepare all data needed for the receipt
            $receiptData = [
                'payment' => [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'formatted_amount' => $payment->getFormattedAmountAttribute(),
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'payment_method_display' => $payment->getPaymentMethodDisplayAttribute(),
                    'status' => $payment->status,
                    'human_status' => $payment->getHumanStatusAttribute(),
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    'payment_breakdown' => $paymentBreakdown,
                ],
                'appointment' => $payment->appointment ? [
                    'id' => $payment->appointment->id,
                    'date' => $payment->appointment->date,
                    'formatted_date' => $payment->appointment->date ? Carbon::parse($payment->appointment->date)->format('F d, Y') : null,
                    'start_time' => $payment->appointment->start_time,
                    'end_time' => $payment->appointment->end_time,
                    'formatted_time' => $payment->appointment->getFormattedTimeAttribute() ?? null,
                    'status' => $payment->appointment->status,
                    'human_status' => $payment->appointment->getHumanStatusAttribute(),
                    'notes' => $payment->appointment->notes,
                    'visit_type' => $payment->appointment->visit_type,
                ] : null,
                'service' => $payment->appointment && $payment->appointment->service ? [
                    'id' => $payment->appointment->service->id,
                    'name' => $payment->appointment->service->name,
                    'duration' => $payment->appointment->service->duration,
                    'formatted_duration' => $payment->appointment->service->formatted_duration ?? null,
                    'price' => $payment->appointment->service->price,
                    'formatted_price' => $payment->appointment->service->formatted_price ?? null,
                ] : null,
                'client' => $payment->user ? [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name,
                    'email' => $payment->user->email,
                    'phone' => $payment->user->phone ?? null,
                ] : null,
                'business' => $businessDetails,
            ];

            return $this->success($receiptData, 'Receipt data generated successfully.');
        } catch (\Exception $e) {
            Log::error('Error generating receipt: '.$e->getMessage());

            return $this->error([], 'Failed to generate receipt: '.$e->getMessage(), 500);
        }
    }

    /**
     * Update payment status (for marking offline payments as received).
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentStatus(Request $request, $paymentId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:'.implode(',', [
                Payment::STATUS_PENDING,
                Payment::STATUS_PAID,
                Payment::STATUS_FAILED,
                Payment::STATUS_REFUNDED,
            ]),
            'payment_method' => 'required_if:status,'.Payment::STATUS_PAID.'|nullable|string',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $userId = Auth::id();
            $payment = Payment::findOrFail($paymentId);

            // Check authorization - only the provider or client can update
            if ($payment->provider_id !== $userId && $payment->user_id !== $userId) {
                return $this->error([], 'Unauthorized to update this payment.', 403);
            }

            // If marking as paid, payment method is required
            if ($request->status === Payment::STATUS_PAID && empty($request->payment_method)) {
                return $this->error([], 'Payment method is required when marking as paid.', 422);
            }

            // Update payment details
            $updateData = [
                'status' => $request->status,
            ];
            $paymentCalculation = $this->calculatePaymentBreakdown([
                'original_price' => $payment->original_price,
                'home_visit_fee' => $payment->home_visit_fee,
                'discount_amount' => $payment->discount_amount,
            ]);

            // Update the payment with calculated values
            $updateData['booking_price'] = $paymentCalculation['booking_price'];
            $updateData['platform_fee'] = $paymentCalculation['platform_fee'];
            $updateData['other_charges'] = $paymentCalculation['other_charges'];
            $updateData['gst_amount'] = $paymentCalculation['gst_amount'];
            $updateData['amount'] = $paymentCalculation['amount'];
            $updateData['net_amount'] = $paymentCalculation['net_amount'];
            $updateData['vendor_earnings'] = $paymentCalculation['vendor_earnings'];
            $updateData['admin_earnings'] = $paymentCalculation['admin_earnings'];

            // Update payment method if provided
            if ($request->has('payment_method')) {
                $updateData['payment_method'] = $request->payment_method;

                // Update payment_mode if not already set
                if (empty($payment->payment_mode)) {
                    $updateData['payment_mode'] = $request->payment_method;
                }
            }

            // Update payment details with notes if provided
            if ($request->has('notes')) {
                // Get existing payment details
                $paymentDetails = is_array($payment->payment_details)
                    ? $payment->payment_details
                    : json_decode($payment->payment_details ?? '{}', true) ?? [];

                // Add status update notes
                $statusUpdateNotes = [
                    'status_update' => [
                        'previous_status' => $payment->status,
                        'new_status' => $request->status,
                        'notes' => $request->notes,
                        'updated_by' => $userId,
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
                    'payment_status' => $request->status,
                    'payment_method' => $request->payment_method ?? $payment->appointment->payment_method,
                ]);
            }

            DB::commit();

            // Load the related models for the response
            $payment->load(['appointment', 'appointment.service', 'appointment.client', 'user', 'provider']);

            return $this->success(new PaymentResource($payment), 'Payment status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating payment status: '.$e->getMessage());

            return $this->error([], 'Failed to update payment status: '.$e->getMessage(), 500);
        }
    }

    private function calculatePaymentBreakdown($data)
    {
        // Get required values with fallbacks
        $originalPrice = isset($data['original_price']) ? floatval($data['original_price']) : 0;
        $homeVisitFee = isset($data['home_visit_fee']) ? floatval($data['home_visit_fee']) : 0;
        $discountAmount = isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0;

        // Platform fee is fixed at 8 Rs
        $platformFee = 8.00;

        // Calculate booking price (Vendor Earnings)
        $bookingPrice = $originalPrice + $homeVisitFee - $discountAmount;

        // Ensure booking price is not negative
        $bookingPrice = max(0, $bookingPrice);

        // Calculate Other Charges (2% of Booking Price)
        $otherCharges = round($bookingPrice * 0.02, 2);

        // Calculate GST (18% of Platform Fee + Other Charges)
        $gstAmount = round(($platformFee + $otherCharges) * 0.18, 2);

        // Calculate Admin Earnings
        $adminEarnings = $platformFee + $otherCharges + $gstAmount;

        // Calculate total amount customer pays
        $totalAmount = $bookingPrice + $adminEarnings;

        // Return calculated values
        return [
            'original_price' => $originalPrice,
            'home_visit_fee' => $homeVisitFee,
            'discount_amount' => $discountAmount,
            'booking_price' => $bookingPrice,
            'platform_fee' => $platformFee,
            'other_charges' => $otherCharges,
            'gst_amount' => $gstAmount,
            'admin_earnings' => $adminEarnings,
            'vendor_earnings' => $bookingPrice,
            'amount' => $totalAmount,
            'net_amount' => $totalAmount,
        ];
    }

    /**
     * Generate a receipt PDF for a payment.
     *
     * @param  int  $paymentId
     * @param  string  $action  ('download' or 'share')
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function generateReceiptPDF($paymentId, $action = 'download')
    {
        try {
            $userId = Auth::id();

            // Find the payment with appointment and related data
            $payment = Payment::with([
                'appointment',
                'appointment.service',
                'appointment.client',
                'provider',
            ])->findOrFail($paymentId);

            // Check if user is authorized to view this payment
            // Allow both the provider and the client to view the receipt
            if ($payment->user_id !== $userId && $payment->provider_id !== $userId) {
                return $this->error([], 'Unauthorized to generate receipt for this payment.', 403);
            }

            // Get business details for the provider
            $businessInfo = null;

            if ($payment->provider) {
                $providerKyc = $payment->provider->kycDocument()->first();

                if ($providerKyc) {
                    $businessInfo = [
                        'name' => $providerKyc->business_name ?? $payment->provider->name,
                        'address' => $providerKyc->business_address ?? '',
                        'phone' => $payment->provider->phone ?? '',
                        'email' => $payment->provider->email ?? '',
                    ];
                } else {
                    $businessInfo = [
                        'name' => $payment->provider->name ?? 'Business',
                        'address' => $payment->provider->address ?? '',
                        'phone' => $payment->provider->phone ?? '',
                        'email' => $payment->provider->email ?? '',
                    ];
                }
            }

            // Generate PDF
            $pdfPath = $this->receiptService->generatePDF($payment->appointment, $payment, $businessInfo);

            // Check if file was generated successfully
            if (! Storage::disk('public')->exists($pdfPath)) {
                throw new \Exception('Failed to generate receipt PDF');
            }

            $fullPath = Storage::disk('public')->path($pdfPath);
            $url = Storage::disk('public')->url($pdfPath);

            // If action is download, return the file for download
            if ($action === 'download') {
                return response()->download($fullPath, 'receipt_'.$payment->id.'.pdf', [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="receipt_'.$payment->id.'.pdf"',
                ]);
            }

            // If action is share, return URL that can be shared
            if ($action === 'share') {
                return $this->success([
                    'file_url' => $url,
                    'expires_at' => Carbon::now()->addHours(24)->toIso8601String(),
                ], 'Receipt generated and ready to be shared');
            }

            // Default - return download URL
            return $this->success([
                'download_url' => $url,
            ], 'Receipt generated successfully');

        } catch (\Exception $e) {
            Log::error('Error generating receipt PDF: '.$e->getMessage());

            return $this->error([], 'Failed to generate receipt PDF: '.$e->getMessage(), 500);
        }
    }

    /**
     * Download receipt PDF for a payment.
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\Response
     */
    public function downloadReceiptPDF($paymentId)
    {
        return $this->generateReceiptPDF($paymentId, 'download');
    }

    /**
     * Get share URL for receipt PDF.
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function shareReceiptPDF($paymentId)
    {
        return $this->generateReceiptPDF($paymentId, 'share');
    }

    /**
     * Generate a receipt PDF for an appointment.
     *
     * @param  int  $appointmentId
     * @param  string  $action  ('download' or 'share')
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function generateAppointmentReceiptPDF($appointmentId, $action = 'download')
    {
        try {
            $userId = Auth::id();

            // Find the appointment with related data
            $appointment = Appointment::with([
                'service',
                'comboService',
                'client',
                'user',
                'payment',
            ])->findOrFail($appointmentId);

            // Check if user is authorized to view this appointment
            if ($appointment->client_id !== $userId && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized to generate receipt for this appointment.', 403);
            }

            // Get business details for the provider
            $businessInfo = null;

            if ($appointment->user) {
                $providerKyc = $appointment->user->kycDocument()->first();

                if ($providerKyc) {
                    $businessInfo = [
                        'name' => $providerKyc->business_name ?? $appointment->user->name,
                        'address' => $providerKyc->business_address ?? '',
                        'phone' => $appointment->user->phone ?? '',
                        'email' => $appointment->user->email ?? '',
                    ];
                } else {
                    $businessInfo = [
                        'name' => $appointment->user->name ?? 'Business',
                        'address' => $appointment->user->address ?? '',
                        'phone' => $appointment->user->phone ?? '',
                        'email' => $appointment->user->email ?? '',
                    ];
                }
            }

            // Generate PDF
            $pdfPath = $this->receiptService->generatePDF($appointment, $appointment->payment, $businessInfo);

            // Check if file was generated successfully
            if (! Storage::disk('public')->exists($pdfPath)) {
                throw new \Exception('Failed to generate receipt PDF');
            }

            $fullPath = Storage::disk('public')->path($pdfPath);
            $url = Storage::disk('public')->url($pdfPath);

            // If action is download, return the file for download
            //            if ($action === 'download') {
            //                return response()->download($fullPath, 'receipt_' . $appointment->id . '.pdf', [
            //                    'Content-Type' => 'application/pdf',
            //                    'Content-Disposition' => 'attachment; filename="receipt_' . $appointment->id . '.pdf"'
            //                ]);
            //            }

            // If action is share, return URL that can be shared
            if ($action === 'share') {
                return $this->success([
                    'file_url' => $url,
                    'expires_at' => Carbon::now()->addHours(24)->toIso8601String(),
                ], 'Receipt generated and ready to be shared');
            }

            // Default - return download URL
            return $this->success([
                'download_url' => $url,
            ], 'Receipt generated successfully');

        } catch (\Exception $e) {
            Log::error('Error generating appointment receipt PDF: '.$e->getMessage());

            return $this->error([], 'Failed to generate receipt PDF: '.$e->getMessage(), 500);
        }
    }

    /**
     * Download receipt PDF for an appointment.
     *
     * @param  int  $appointmentId
     * @return \Illuminate\Http\Response
     */
    public function downloadAppointmentReceiptPDF($appointmentId)
    {
        return $this->generateAppointmentReceiptPDF($appointmentId, 'download');
    }

    /**
     * Get share URL for an appointment receipt PDF.
     *
     * @param  int  $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function shareAppointmentReceiptPDF($appointmentId)
    {
        return $this->generateAppointmentReceiptPDF($appointmentId, 'share');
    }
}
