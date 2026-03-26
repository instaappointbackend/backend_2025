<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the appointments.
     */
    public function index(Request $request)
    {
        $query = Appointment::with(['user', 'client', 'service'])
            ->whereHas('user')
            ->whereHas('client')
            ->whereHas('service');

        // Search functionality
        if ($request->has('search') && ! empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('client', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                })
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('service', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != 'all' && ! empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Fixed date filtering logic
        if ($request->has('start_date') && ! empty($request->start_date)) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('date', '>=', $startDate);
        }

        if ($request->has('end_date') && ! empty($request->end_date)) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('date', '<=', $endDate);
        }

        // Filter by vendor
        if ($request->has('vendor_id') && ! empty($request->vendor_id)) {
            $query->where('user_id', $request->vendor_id);
        }

        // Filter by client
        if ($request->has('client_id') && ! empty($request->client_id)) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by service
        if ($request->has('service_id') && ! empty($request->service_id)) {
            $query->where('service_id', $request->service_id);
        }

        // Default sorting
        $query->orderBy('date', 'desc')->orderBy('start_time', 'desc');

        $appointments = $query->paginate(15)->withQueryString();

        // Get all vendors, clients, and services for filters
        $vendors = User::where('role', 'vendor')->get();
        $clients = User::where('role', 'customer')->get();
        $services = Service::all();

        return view('admin.appointments.index', compact(
            'appointments',
            'vendors',
            'clients',
            'services'
        ));
    }

    /**
     * Display the calendar view.
     */
    public function calendar(Request $request)
    {
        // Get all appointments for calendar
        $appointments = Appointment::with(['user', 'client', 'service'])
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => ! empty($appointment->service->name) ? $appointment->service->name : $appointment->comboService->name . ' - ' . $appointment->client->name,
                    'start' => $appointment->date->format('Y-m-d') . 'T' . $appointment->start_time->format('H:i:s'),
                    'end' => $appointment->date->format('Y-m-d') . 'T' . $appointment->end_time->format('H:i:s'),
                    'url' => route('admin.appointments.show', $appointment->id),
                    'className' => $this->getStatusClass($appointment->status),
                    'extendedProps' => [
                        'status' => $appointment->status,
                        'client' => $appointment?->client?->name,
                        'vendor' => $appointment?->user?->name,
                        'service' => ! empty($appointment->service->name) ? $appointment->service->name : $appointment->comboService->name,
                        'vendor_id' => $appointment->user_id,
                        'client_id' => $appointment->client_id,
                        'service_id' => $appointment->service_id,
                        'combo_service_id' => $appointment->combo_service_id,
                        'visit_type' => $appointment->visit_type ?? 'office',
                        'payment_status' => $appointment->payment_status,
                        'notes' => $appointment->notes,
                    ],
                ];
            });

        // Get vendors and services for appointment creation
        $vendors = User::where('role', 'vendor')->get();
        $clients = User::where('role', 'customer')->get();
        $services = Service::where('is_active', true)->get();

        return view('admin.appointments.calendar', compact(
            'appointments',
            'vendors',
            'clients',
            'services'
        ));
    }

    /**
     * Get CSS class based on appointment status.
     */
    private function getStatusClass($status)
    {
        switch ($status) {
            case Appointment::STATUS_PENDING:
                return 'bg-warning';
            case Appointment::STATUS_CONFIRMED:
                return 'bg-primary';
            case Appointment::STATUS_COMPLETED:
                return 'bg-success';
            case Appointment::STATUS_CANCELLED:
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment)
    {
        $appointment->load(['user', 'client', 'service', 'payment']);
        $vendors = User::where('role', 'vendor')->get();
        $clients = User::where('role', 'customer')->get();
        $services = Service::where('is_active', true)->get();

        return view('admin.appointments.show', compact('appointment', 'vendors', 'clients', 'services'));
    }

    /**
     * Show the form for editing the specified appointment.
     */
    public function edit(Appointment $appointment)
    {
        $vendors = User::where('role', 'vendor')->get();
        $clients = User::where('role', 'customer')->get();
        $services = Service::all();

        return view('admin.appointments.edit', compact('appointment', 'vendors', 'clients', 'services'));
    }

    /**
     * Update the specified appointment in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'status' => 'required|in:' . implode(',', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_COMPLETED,
                Appointment::STATUS_CANCELLED,
            ]),
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'payment_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Store old values for notification comparison
        $oldStatus = $appointment->status;
        $oldPaymentStatus = $appointment->payment_status;

        // Begin database transaction
        DB::beginTransaction();

        try {
            // Convert time strings to proper format
            $validated['start_time'] = Carbon::parse($validated['start_time'])->format('H:i:s');
            $validated['end_time'] = Carbon::parse($validated['end_time'])->format('H:i:s');

            // Update appointment
            $appointment->update($validated);

            // Update related payment if payment_status is changed
            if (isset($validated['payment_status']) && $validated['payment_status'] !== $oldPaymentStatus) {
                $payment = Payment::where('appointment_id', $appointment->id)->first();

                if ($payment && $validated['payment_status']) {
                    $payment->update([
                        'status' => $validated['payment_status'],
                        'amount' => $validated['payment_amount'] ?? $payment->amount,
                        'payment_method' => $validated['payment_method'] ?? $payment->payment_method,
                    ]);
                } elseif (! $payment && $validated['payment_status'] === 'paid') {
                    // Create new payment if status is changing to paid
                    Payment::create([
                        'appointment_id' => $appointment->id,
                        'user_id' => $appointment->client_id,
                        'provider_id' => $appointment->user_id,
                        'transaction_id' => 'ADMIN_' . uniqid(),
                        'payment_method' => $validated['payment_method'] ?? 'cash',
                        'payment_mode' => $validated['payment_method'] ?? 'cash',
                        'amount' => $validated['payment_amount'] ?? 0,
                        'currency' => 'INR',
                        'status' => $validated['payment_status'],
                    ]);
                }
            }

            // Load appointment with relationships for notifications
            $appointment->load(['service', 'comboService', 'client', 'user']);

            // Get service name
            $serviceName = '';
            if ($appointment->service) {
                $serviceName = $appointment->service->name;
            } elseif ($appointment->comboService) {
                $serviceName = $appointment->comboService->name;
            }

            // Format date and time for notifications
            $appointmentDate = Carbon::parse($appointment->date)->format('D, M d, Y');
            $appointmentTime = Carbon::parse($appointment->start_time)->format('h:i A');

            // Send notifications for status changes
            if (isset($validated['status']) && $oldStatus !== $validated['status']) {
                switch ($validated['status']) {
                    case Appointment::STATUS_CONFIRMED:
                        // Notify client about confirmation
                        $clientTitle = 'Appointment Confirmed';
                        $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been confirmed.";
                        $clientData = [
                            'type' => 'appointment_confirmed',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'amount' => $appointment->payment_amount,
                            'payment_status' => $appointment->payment_status,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                        break;

                    case Appointment::STATUS_CANCELLED:
                        // Notify client about cancellation
                        $clientTitle = 'Appointment Cancelled';
                        $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the admin.";
                        $clientData = [
                            'type' => 'appointment_cancelled_by_admin',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'payment_status' => $appointment->payment_status,
                            'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable',
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );

                        // Notify provider about cancellation
                        $providerTitle = 'Appointment Cancelled';
                        $providerBody = "The appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the admin.";
                        $providerData = [
                            'type' => 'appointment_cancelled_by_admin',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'client_id' => $appointment->client_id,
                            'client_name' => $appointment->client->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'payment_status' => $appointment->payment_status,
                            'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable',
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->user_id,
                            $providerTitle,
                            $providerBody,
                            $providerData
                        );
                        break;

                    case Appointment::STATUS_COMPLETED:
                        // Notify client that service is complete
                        $clientTitle = 'Appointment Completed';
                        $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} has been marked as completed.";
                        $clientData = [
                            'type' => 'appointment_completed',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'payment_status' => $appointment->payment_status,
                            'amount' => $appointment->payment_amount,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                        break;
                }
            }

            // Send notifications for payment status changes
            if (isset($validated['payment_status']) && $oldPaymentStatus !== $validated['payment_status']) {
                switch ($validated['payment_status']) {
                    case Appointment::PAYMENT_STATUS_PAID:
                        // Payment made - notify provider
                        $providerTitle = 'Payment Received';
                        $providerBody = "Payment received for appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate}.";
                        $providerData = [
                            'type' => 'payment_received',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'client_id' => $appointment->client_id,
                            'client_name' => $appointment->client->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'amount' => $validated['payment_amount'] ?? $appointment->payment_amount,
                            'payment_method' => $validated['payment_method'] ?? $appointment->payment_method,
                            'original_price' => $appointment->original_price,
                            'discount_amount' => $appointment->discount_amount,
                            'final_price' => $appointment->final_price,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->user_id,
                            $providerTitle,
                            $providerBody,
                            $providerData
                        );

                        // Notify client about payment success
                        $clientTitle = 'Payment Successful';
                        $clientBody = "Your payment for the appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} was successful.";
                        $clientData = [
                            'type' => 'payment_successful',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'amount' => $validated['payment_amount'] ?? $appointment->payment_amount,
                            'payment_method' => $validated['payment_method'] ?? $appointment->payment_method,
                            'original_price' => $appointment->original_price,
                            'discount_amount' => $appointment->discount_amount,
                            'final_price' => $appointment->final_price,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                        break;

                    case Appointment::PAYMENT_STATUS_REFUNDED:
                        // Payment refunded - notify client
                        $clientTitle = 'Payment Refunded';
                        $clientBody = "Your payment for the appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} has been refunded.";
                        $clientData = [
                            'type' => 'payment_refunded',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'amount' => $appointment->payment_amount,
                            'payment_method' => $appointment->payment_method,
                            'refund_id' => $appointment->payment_id ? 'REF_' . $appointment->payment_id : null,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                        break;

                    case Appointment::PAYMENT_STATUS_FAILED:
                        // Payment failed - notify client
                        $clientTitle = 'Payment Failed';
                        $clientBody = "Your payment for the appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} has failed. Please try again.";
                        $clientData = [
                            'type' => 'payment_failed',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'amount' => $appointment->payment_amount,
                            'payment_method' => $appointment->payment_method,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                        break;
                }
            }

            // Commit transaction
            DB::commit();

            return redirect()->route('admin.appointments.index')
                ->with('success', 'Appointment updated successfully.');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to update appointment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified appointment from storage.
     */
    public function destroy(Appointment $appointment)
    {
        // Begin database transaction
        DB::beginTransaction();

        try {
            // Load appointment with relationships for notifications
            $appointment->load(['service', 'comboService', 'client', 'user']);

            // Get service name
            $serviceName = '';
            if ($appointment->service) {
                $serviceName = $appointment->service->name;
            } elseif ($appointment->comboService) {
                $serviceName = $appointment->comboService->name;
            }

            // Format date and time for notifications
            $appointmentDate = Carbon::parse($appointment->date)->format('D, M d, Y');
            $appointmentTime = Carbon::parse($appointment->start_time)->format('h:i A');

            // Delete related payment if exists
            Payment::where('appointment_id', $appointment->id)->delete();

            // Delete appointment
            $appointment->delete();

            // Send notification about appointment deletion to client
            $clientTitle = 'Appointment Deleted';
            $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been deleted by the admin.";
            $clientData = [
                'type' => 'appointment_deleted_by_admin',
                'appointment_date' => $appointment->date,
                'appointment_time' => $appointment->start_time,
                'provider_id' => $appointment->user_id,
                'provider_name' => $appointment->user->name,
                'service_name' => $serviceName,
                'appointmentId' => $appointment->id,
                'screenName' => 'AppointmentDetails',
            ];

            $this->notificationService->sendPushNotification(
                $appointment->client_id,
                $clientTitle,
                $clientBody,
                $clientData
            );

            // Send notification about appointment deletion to provider
            $providerTitle = 'Appointment Deleted';
            $providerBody = "The appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been deleted by the admin.";
            $providerData = [
                'type' => 'appointment_deleted_by_admin',
                'appointment_date' => $appointment->date,
                'appointment_time' => $appointment->start_time,
                'client_id' => $appointment->client_id,
                'client_name' => $appointment->client->name,
                'service_name' => $serviceName,
                'appointmentId' => $appointment->id,
                'screenName' => 'AppointmentDetails',
            ];

            $this->notificationService->sendPushNotification(
                $appointment->user_id,
                $providerTitle,
                $providerBody,
                $providerData
            );

            // Commit transaction
            DB::commit();

            // return redirect()->route('admin.appointments.index')
            //     ->with('success', 'Appointment deleted successfully.');
            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            // return redirect()->back()
            //     ->with('error', 'Failed to delete appointment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
            ]);
        }
    }

    /**
     * Update the status of an appointment.
     */
    public function status(Request $request, Appointment $appointment, $status = null)
    {
        // Get status from route parameter if not passed directly
        if ($status === null) {
            $status = $request->route('status');
        }

        // Begin database transaction
        DB::beginTransaction();

        try {
            // Store old status for comparison
            $oldStatus = $appointment->status;

            // Update appointment status
            if ($status === 'confirmed') {
                $appointment->confirm();
            } elseif ($status === 'completed') {
                $appointment->complete();
            } elseif ($status === 'cancelled') {
                $appointment->cancel();
            } else {
                return redirect()->back()->with('error', 'Invalid status provided.');
            }

            // Load appointment with relationships for notifications
            $appointment->load(['service', 'comboService', 'client', 'user']);

            // Get service name
            $serviceName = '';
            if ($appointment->service) {
                $serviceName = $appointment->service->name;
            } elseif ($appointment->comboService) {
                $serviceName = $appointment->comboService->name;
            }

            // Format date and time for notifications
            $appointmentDate = Carbon::parse($appointment->date)->format('D, M d, Y');
            $appointmentTime = Carbon::parse($appointment->start_time)->format('h:i A');

            // Send notifications for status changes
            if ($oldStatus !== $status) {
                switch ($status) {
                    case 'confirmed':
                        // Notify client about confirmation
                        $clientTitle = 'Appointment Confirmed';
                        $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been confirmed.";
                        $clientData = [
                            'type' => 'appointment_confirmed',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'amount' => $appointment->payment_amount,
                            'payment_status' => $appointment->payment_status,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                        break;

                    case 'cancelled':
                        // Notify client about cancellation
                        $clientTitle = 'Appointment Cancelled';
                        $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the admin.";
                        $clientData = [
                            'type' => 'appointment_cancelled_by_admin',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'payment_status' => $appointment->payment_status,
                            'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable',
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );

                        // Notify provider about cancellation
                        $providerTitle = 'Appointment Cancelled';
                        $providerBody = "The appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the admin.";
                        $providerData = [
                            'type' => 'appointment_cancelled_by_admin',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'client_id' => $appointment->client_id,
                            'client_name' => $appointment->client->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'payment_status' => $appointment->payment_status,
                            'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable',
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->user_id,
                            $providerTitle,
                            $providerBody,
                            $providerData
                        );
                        break;

                    case 'completed':
                        // Notify client that service is complete
                        $clientTitle = 'Appointment Completed';
                        $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} has been marked as completed.";
                        $clientData = [
                            'type' => 'appointment_completed',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $appointment->visit_type,
                            'payment_status' => $appointment->payment_status,
                            'amount' => $appointment->payment_amount,
                            'appointmentId' => $appointment->id,
                            'screenName' => 'AppointmentDetails',
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                        break;
                }
            }

            // Commit transaction
            DB::commit();

            return redirect()->back()->with('success', 'Appointment status updated successfully.');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to update appointment status: ' . $e->getMessage());
        }
    }
}
