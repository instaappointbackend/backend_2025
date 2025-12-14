<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResponse;
use App\Models\Appointment;
use App\Models\AppointmentSettings;
use App\Models\ComboService;
use App\Models\Payment;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\NotificationService;

use App\Models\User;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    use ApiResponseTrait;
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of appointments.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $status = $request->query('status');
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Base query
            $query = Appointment::query();

            // Filter by user role
            if ($role === 'vendor') {
                $query->where('user_id', $userId);
            } else {
                $query->where('client_id', $userId);
            }

            // Apply status filter if provided
            if ($status) {
                $query->where('status', $status);
            }

            // Apply date range filter if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->query('end_date'))->endOfDay();

                $query->whereBetween('date', [$startDate, $endDate]);
            }

            // Apply appointment type filter if provided
            if ($request->has('type')) {
                if ($request->query('type') === 'combo') {
                    $query->whereNotNull('combo_service_id');
                } elseif ($request->query('type') === 'service') {
                    $query->whereNotNull('service_id')->whereNull('combo_service_id');
                }
            }

            // Order by most recent first
            $query->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc');

            // Include relationships
            $query->with(['service', 'comboService.services', 'client', 'user', 'payment']);

            // Paginate results
            $appointments = $query->paginate(10);

            return $this->success(
                AppointmentResponse::collection($appointments),
                'Appointments retrieved successfully',
                200,
                [
                    'pagination' => [
                        'total' => $appointments->total(),
                        'per_page' => $appointments->perPage(),
                        'current_page' => $appointments->currentPage(),
                        'last_page' => $appointments->lastPage(),
                    ]
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving appointments: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve appointments', 500);
        }
    }

    /**
     * Store a newly created appointment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AppointmentRequest $request)
    {
        try {
            // Begin database transaction
            DB::beginTransaction();

            // Get client ID (current authenticated user)
            $clientId = Auth::id();
            $client = User::find($clientId);

            $providerId = null;
            if ($request->has('service_id')) {
                $service = Service::findOrFail($request->service_id);
                $providerId = $service->user_id; // The user_id of the service creator
                $serviceName = $service->name;
            }
            // If creating an appointment for a combo service
            else if ($request->has('combo_service_id')) {
                $comboService = ComboService::findOrFail($request->combo_service_id);
                $providerId = $comboService->user_id;
                $serviceName = $comboService->name;
            }

            if (!$providerId) {
                return $this->error([], "Error while processing", 422);
            }

            // Get provider details
            $provider = User::find($providerId);

            $timeSlot = TimeSlot::findOrFail($request->time_slot_id);

            // Block all requested time slots
            if ($request->has('time_slots_to_block') && is_array($request->time_slots_to_block)) {
                foreach ($request->time_slots_to_block as $slotId) {
                    $slot = TimeSlot::findOrFail($slotId);
                    $slot->update(['is_available' => false]);
                }
            } else {
                // At minimum, mark the selected time slot as unavailable
                $timeSlot->update(['is_available' => false]);
            }

            // Check if we have original_price, if not get it from service
            $originalPrice = $request->original_price;
            if (!$originalPrice && $request->has('service_id')) {
                $service = Service::findOrFail($request->service_id);
                $originalPrice = $service->price ?? 0;
            } elseif (!$originalPrice && $request->has('combo_service_id')) {
                $comboService = ComboService::findOrFail($request->combo_service_id);
                $originalPrice = $comboService->discounted_price ?? $comboService->total_price ?? 0;
            }

// Prepare payment calculation input data
            $paymentInputData = [
                'original_price' => $originalPrice,
                'home_visit_fee' => $request->home_visit_fee ?? 0,
                'discount_amount' => $request->discount_amount ?? 0
            ];

// Calculate payment breakdown using our formula
            $paymentCalculation = $this->calculatePaymentBreakdown($paymentInputData);

// For this example, let's assume we've created the appointment and now want to record payment
            $appointment = Appointment::create([
                'user_id' => $providerId,
                'client_id' => $clientId,
                'service_id' => $request->service_id,
                'combo_service_id' => $request->combo_service_id,
                'date' => $request->date,
                'start_time' => $timeSlot->start_time,
                'end_time' => $timeSlot->end_time,

                // Payment-related fields with calculated values
                'payment_status' => $request->payment_status ?? Appointment::PAYMENT_STATUS_PENDING,
                'payment_id' => $request->payment_id,
                'payment_method' => $request->payment_method,
                'payment_amount' => $paymentCalculation['payment_amount'],

                // Save pricing information with calculated values
                'original_price' => $paymentCalculation['original_price'],
                'discount_amount' => $paymentCalculation['discount_amount'],
                'discount_percentage' => $request->discount_percentage,
                'final_price' => $paymentCalculation['final_price'],

                // Payment breakdown fields with calculated values
                'booking_price' => $paymentCalculation['booking_price'],
                'platform_fees' => $paymentCalculation['platform_fees'],
                'other_charges' => $paymentCalculation['other_charges'],
                'gst' => $paymentCalculation['gst'],
                'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                'additional_services_fee' => $request->additional_services_fee,

                // Additional payment details
                'coupon_code' => $request->coupon_code,
                'offer_title' => $request->offer_title,
                'vendor_offer_id' => $request->vendor_offer_id,
                'admin_offer_id' => $request->admin_offer_id,
                'offer_type' => $request->offer_type,

                'notes' => $request->notes,
                'visit_type' => $request->visit_type ?? 'office',
            ]);

            $setting=AppointmentSettings::where('user_id',$providerId)->first();
            if(!empty($setting) && $setting->auto_confirm_appointments){
                $updateData['status']=Appointment::STATUS_CONFIRMED;
                $appointment->update($updateData);
            }

// If there's payment information, create a payment record
            if ($request->has('payment_status') && $request->payment_id) {
                // Create payment record with all details and calculated values
                Payment::create([
                    'appointment_id' => $appointment->id,
                    'user_id' => $clientId,
                    'provider_id' => $providerId,
                    'transaction_id' => $request->payment_id,
                    'payment_method' => $request->payment_method,
                    'payment_mode' => $request->payment_method, // Using method as mode by default
                    'amount' => $paymentCalculation['amount'],
                    'currency' => 'INR', // Default to INR or get from request
                    'status' => $request->payment_status,
                    'payment_details' => $request->payment_details,

                    // Detailed payment breakdown fields from calculation
                    'original_price' => $paymentCalculation['original_price'],
                    'booking_price' => $paymentCalculation['booking_price'],
                    'platform_fee' => $paymentCalculation['platform_fees'],
                    'other_charges' => $paymentCalculation['other_charges'],
                    'gst_amount' => $paymentCalculation['gst'],
                    'discount_amount' => $paymentCalculation['discount_amount'],
                    'discount_percentage' => $request->discount_percentage,
                    'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                    'additional_services_fee' => $request->additional_services_fee,
                    'net_amount' => $paymentCalculation['amount'],
                    'vendor_earnings' => $paymentCalculation['vendor_earnings'],
                    'admin_earnings' => $paymentCalculation['admin_earnings'],

                    // Discount and offer related fields
                    'coupon_code' => $request->coupon_code,
                    'offer_title' => $request->offer_title,
                    'vendor_offer_id' => $request->vendor_offer_id,
                    'admin_offer_id' => $request->admin_offer_id,
                    'offer_type' => $request->offer_type,

                    // Additional notes
                    'additional_notes' => $request->notes,
                ]);
            }

            // Format dates for notifications
            $appointmentDate = Carbon::parse($request->date)->format('D, M d, Y');
            $appointmentTime = Carbon::parse($timeSlot->start_time)->format('h:i A');

            // Send notification to provider
            $providerTitle = 'New Appointment Request';
            $providerBody = "New appointment request from {$client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime}.";
            $providerData = [
                'type' => 'appointment_created',
                'appointment_id' => $appointment->id,
                'appointment_date' => $request->date,
                'appointment_time' => $timeSlot->start_time,
                'client_id' => $clientId,
                'client_name' => $client->name,
                'service_name' => $serviceName
            ];

            $this->notificationService->sendPushNotification(
                $providerId,
                $providerTitle,
                $providerBody,
                $providerData
            );

            // Send notification to client
            $clientTitle = 'Appointment Booked';
            $clientBody = "Your appointment with {$provider->name} for {$serviceName} has been booked for {$appointmentDate} at {$appointmentTime}.";
            $clientData = [
                'type' => 'appointment_confirmed',
                'appointment_id' => $appointment->id,
                'appointment_date' => $request->date,
                'appointment_time' => $timeSlot->start_time,
                'provider_id' => $providerId,
                'provider_name' => $provider->name,
                'service_name' => $serviceName
            ];

            $this->notificationService->sendPushNotification(
                $clientId,
                $clientTitle,
                $clientBody,
                $clientData
            );

            // Commit transaction
            DB::commit();

            // Return success response with appointment details
            return $this->success(
                $appointment,
                'Appointment created successfully with payment details',
                201
            );
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error creating appointment with payment details: ' . $e->getMessage());
            return $this->error([], 'Failed to create appointment: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Display the specified appointment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Get appointment with appropriate relationships
            $appointment = Appointment::where('id', $id)->first();

            if (!$appointment) {
                return $this->error([], 'Appointment not found', 404);
            }

            // Check authorization
            if ($role === 'vendor' && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            } elseif ($role === 'client' && $appointment->client_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            }

            // Load relationships based on appointment type
            if ($appointment->isComboService()) {
                $appointment->load(['comboService.services', 'client', 'user', 'payment']);
            } else {
                $appointment->load(['service', 'client', 'user', 'payment']);
            }

            return $this->success(
                new AppointmentResponse($appointment),
                'Appointment retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving appointment: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve appointment', 500);
        }
    }

    /**
     * Update the specified appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,confirmed,cancelled,completed',
            'notes' => 'sometimes|string|max:500',
            'payment_status' => 'sometimes|string|in:pending,paid,failed,refunded',
            'payment_id' => 'sometimes|string',
            'payment_method' => 'sometimes|string',
            'payment_amount' => 'sometimes|numeric',
            'original_price' => 'sometimes|numeric',
            'discount_amount' => 'sometimes|numeric',
            'discount_percentage' => 'sometimes|numeric',
            'final_price' => 'sometimes|numeric',
            'booking_price' => 'sometimes|numeric',
            'platform_fees' => 'sometimes|numeric',
            'other_charges' => 'sometimes|numeric',
            'gst' => 'sometimes|numeric',
            'home_visit_fee' => 'sometimes|numeric',
            'additional_services_fee' => 'sometimes|numeric',
            'coupon_code' => 'sometimes|string',
            'offer_title' => 'sometimes|string',
            'vendor_offer_id' => 'sometimes|integer',
            'admin_offer_id' => 'sometimes|integer',
            'offer_type' => 'sometimes|string',
            'visit_type' => 'sometimes|string|in:office,home',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Get appointment
            $appointment = Appointment::findOrFail($id);

            // Check authorization
            if ($role === 'vendor' && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            } elseif ($role === 'client' && $appointment->client_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            }

            // Update only allowed fields based on role
            $updatableFields = [];

            // Store old values for notification comparison
            $oldStatus = $appointment->status;
            $oldPaymentStatus = $appointment->payment_status;

            if ($role === 'vendor') {
                // Service providers can update status, payment status, and other fields
                if ($request->has('status')) {
                    $updatableFields['status'] = $request->status;
                }

                if ($request->has('payment_status')) {
                    $updatableFields['payment_status'] = $request->payment_status;
                }

                // Payment-related fields
                if ($request->has('payment_id')) {
                    $updatableFields['payment_id'] = $request->payment_id;
                }

                if ($request->has('payment_method')) {
                    $updatableFields['payment_method'] = $request->payment_method;
                }

                if ($request->has('payment_amount')) {
                    $updatableFields['payment_amount'] = $request->payment_amount;
                }

                // Pricing information
                if ($request->has('original_price')) {
                    $updatableFields['original_price'] = $request->original_price;
                }

                if ($request->has('discount_amount')) {
                    $updatableFields['discount_amount'] = $request->discount_amount;
                }

                if ($request->has('discount_percentage')) {
                    $updatableFields['discount_percentage'] = $request->discount_percentage;
                }

                if ($request->has('final_price')) {
                    $updatableFields['final_price'] = $request->final_price;
                }

                // Payment breakdown fields
                if ($request->has('booking_price')) {
                    $updatableFields['booking_price'] = $request->booking_price;
                }

                if ($request->has('platform_fees')) {
                    $updatableFields['platform_fees'] = $request->platform_fees;
                }

                if ($request->has('other_charges')) {
                    $updatableFields['other_charges'] = $request->other_charges;
                }

                if ($request->has('gst')) {
                    $updatableFields['gst'] = $request->gst;
                }

                if ($request->has('home_visit_fee')) {
                    $updatableFields['home_visit_fee'] = $request->home_visit_fee;
                }

                if ($request->has('additional_services_fee')) {
                    $updatableFields['additional_services_fee'] = $request->additional_services_fee;
                }

                // Offer-related fields
                if ($request->has('coupon_code')) {
                    $updatableFields['coupon_code'] = $request->coupon_code;
                }

                if ($request->has('offer_title')) {
                    $updatableFields['offer_title'] = $request->offer_title;
                }

                if ($request->has('vendor_offer_id')) {
                    $updatableFields['vendor_offer_id'] = $request->vendor_offer_id;
                }

                if ($request->has('admin_offer_id')) {
                    $updatableFields['admin_offer_id'] = $request->admin_offer_id;
                }

                if ($request->has('offer_type')) {
                    $updatableFields['offer_type'] = $request->offer_type;
                }

                // Visit type
                if ($request->has('visit_type')) {
                    $updatableFields['visit_type'] = $request->visit_type;
                }

                // Notes
                if ($request->has('notes')) {
                    $updatableFields['notes'] = $request->notes;
                }
            } else {
                // Clients can update notes, cancel, and update visit_type
                if ($request->has('notes')) {
                    $updatableFields['notes'] = $request->notes;
                }

                // Only allow clients to cancel, not change to other statuses
                if ($request->has('status') && $request->status === Appointment::STATUS_CANCELLED) {
                    if ($appointment->canBeCancelled()) {
                        $updatableFields['status'] = Appointment::STATUS_CANCELLED;
                    } else {
                        return $this->error([], 'This appointment cannot be cancelled', 422);
                    }
                }

                // Visit type
                if ($request->has('visit_type')) {
                    $updatableFields['visit_type'] = $request->visit_type;
                }
            }

            // Update appointment
            if (!empty($updatableFields)) {
                $appointment->update($updatableFields);

                // Update related payment status if payment_status is changed
                if (isset($updatableFields['payment_status']) ||
                    isset($updatableFields['payment_amount']) ||
                    isset($updatableFields['payment_method']) ||
                    isset($updatableFields['payment_id'])) {

                    $payment = Payment::where('appointment_id', $appointment->id)->first();

                    if ($payment) {
                        $paymentUpdates = [];

                        if (isset($updatableFields['payment_status'])) {
                            $paymentUpdates['status'] = $updatableFields['payment_status'];
                        }

                        if (isset($updatableFields['payment_amount'])) {
                            $paymentUpdates['amount'] = $updatableFields['payment_amount'];
                            $paymentUpdates['net_amount'] = $updatableFields['payment_amount'];
                        }

                        if (isset($updatableFields['payment_method'])) {
                            $paymentUpdates['payment_method'] = $updatableFields['payment_method'];
                            $paymentUpdates['payment_mode'] = $updatableFields['payment_method'];
                        }

                        if (isset($updatableFields['payment_id'])) {
                            $paymentUpdates['transaction_id'] = $updatableFields['payment_id'];
                        }

                        // Update payment breakdown fields
                        if (isset($updatableFields['booking_price'])) {
                            $paymentUpdates['booking_price'] = $updatableFields['booking_price'];
                        }

                        if (isset($updatableFields['platform_fees'])) {
                            $paymentUpdates['platform_fee'] = $updatableFields['platform_fees'];
                        }

                        if (isset($updatableFields['other_charges'])) {
                            $paymentUpdates['other_charges'] = $updatableFields['other_charges'];
                        }

                        if (isset($updatableFields['gst'])) {
                            $paymentUpdates['gst_amount'] = $updatableFields['gst'];
                        }

                        if (isset($updatableFields['discount_amount'])) {
                            $paymentUpdates['discount_amount'] = $updatableFields['discount_amount'];
                        }

                        if (isset($updatableFields['discount_percentage'])) {
                            $paymentUpdates['discount_percentage'] = $updatableFields['discount_percentage'];
                        }

                        if (isset($updatableFields['home_visit_fee'])) {
                            $paymentUpdates['home_visit_fee'] = $updatableFields['home_visit_fee'];
                        }

                        if (isset($updatableFields['additional_services_fee'])) {
                            $paymentUpdates['additional_services_fee'] = $updatableFields['additional_services_fee'];
                        }

                        // Offer-related fields
                        if (isset($updatableFields['coupon_code'])) {
                            $paymentUpdates['coupon_code'] = $updatableFields['coupon_code'];
                        }

                        if (isset($updatableFields['offer_title'])) {
                            $paymentUpdates['offer_title'] = $updatableFields['offer_title'];
                        }

                        if (isset($updatableFields['vendor_offer_id'])) {
                            $paymentUpdates['vendor_offer_id'] = $updatableFields['vendor_offer_id'];
                        }

                        if (isset($updatableFields['admin_offer_id'])) {
                            $paymentUpdates['admin_offer_id'] = $updatableFields['admin_offer_id'];
                        }

                        if (isset($updatableFields['offer_type'])) {
                            $paymentUpdates['offer_type'] = $updatableFields['offer_type'];
                        }

                        if (isset($updatableFields['notes'])) {
                            $paymentUpdates['additional_notes'] = $updatableFields['notes'];
                        }

                        if (!empty($paymentUpdates)) {
                            $payment->update($paymentUpdates);
                        }
                    } else if (isset($updatableFields['payment_status']) && $updatableFields['payment_status'] === Appointment::PAYMENT_STATUS_PAID) {
                        // Create a new payment record if one doesn't exist and status is changing to paid
                        Payment::create([
                            'appointment_id' => $appointment->id,
                            'user_id' => $appointment->client_id,
                            'provider_id' => $appointment->user_id,
                            'transaction_id' => $updatableFields['payment_id'] ?? ('PAY_' . uniqid()),
                            'payment_method' => $updatableFields['payment_method'] ?? 'unknown',
                            'payment_mode' => $updatableFields['payment_method'] ?? 'unknown',
                            'amount' => $updatableFields['payment_amount'] ?? 0,
                            'currency' => 'INR',
                            'status' => Payment::STATUS_PAID,

                            // Payment breakdown fields
                            'booking_price' => $updatableFields['booking_price'] ?? null,
                            'platform_fee' => $updatableFields['platform_fees'] ?? null,
                            'other_charges' => $updatableFields['other_charges'] ?? null,
                            'gst_amount' => $updatableFields['gst'] ?? null,
                            'discount_amount' => $updatableFields['discount_amount'] ?? null,
                            'discount_percentage' => $updatableFields['discount_percentage'] ?? null,
                            'home_visit_fee' => $updatableFields['home_visit_fee'] ?? null,
                            'additional_services_fee' => $updatableFields['additional_services_fee'] ?? null,
                            'net_amount' => $updatableFields['payment_amount'] ?? $updatableFields['final_price'] ?? 0,

                            // Offer-related fields
                            'coupon_code' => $updatableFields['coupon_code'] ?? null,
                            'offer_title' => $updatableFields['offer_title'] ?? null,
                            'vendor_offer_id' => $updatableFields['vendor_offer_id'] ?? null,
                            'admin_offer_id' => $updatableFields['admin_offer_id'] ?? null,
                            'offer_type' => $updatableFields['offer_type'] ?? null,

                            // Notes
                            'additional_notes' => $updatableFields['notes'] ?? null,
                        ]);
                    }
                }

                // Load service and user data for notifications
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
                if (isset($updatableFields['status']) && $oldStatus !== $updatableFields['status']) {
                    // Status changed - send notifications
                    switch ($updatableFields['status']) {
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
                                'payment_status' => $appointment->payment_status
                            ];

                            $this->notificationService->sendPushNotification(
                                $appointment->client_id,
                                $clientTitle,
                                $clientBody,
                                $clientData
                            );
                            break;

                        case Appointment::STATUS_CANCELLED:
                            // Determine who cancelled
                            $cancelledBy = ($role === 'vendor') ? 'provider' : 'client';

                            if ($cancelledBy === 'provider') {
                                // Provider cancelled - notify client
                                $clientTitle = 'Appointment Cancelled';
                                $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the provider.";
                                $clientData = [
                                    'type' => 'appointment_cancelled_by_provider',
                                    'appointment_id' => $appointment->id,
                                    'appointment_date' => $appointment->date,
                                    'appointment_time' => $appointment->start_time,
                                    'provider_id' => $appointment->user_id,
                                    'provider_name' => $appointment->user->name,
                                    'service_name' => $serviceName,
                                    'visit_type' => $appointment->visit_type,
                                    'payment_status' => $appointment->payment_status,
                                    'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable'
                                ];

                                $this->notificationService->sendPushNotification(
                                    $appointment->client_id,
                                    $clientTitle,
                                    $clientBody,
                                    $clientData
                                );
                            } else {
                                // Client cancelled - notify provider
                                $providerTitle = 'Appointment Cancelled';
                                $providerBody = "Appointment for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by {$appointment->client->name}.";
                                $providerData = [
                                    'type' => 'appointment_cancelled_by_client',
                                    'appointment_id' => $appointment->id,
                                    'appointment_date' => $appointment->date,
                                    'appointment_time' => $appointment->start_time,
                                    'client_id' => $appointment->client_id,
                                    'client_name' => $appointment->client->name,
                                    'service_name' => $serviceName,
                                    'visit_type' => $appointment->visit_type,
                                    'payment_status' => $appointment->payment_status,
                                    'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable'
                                ];

                                $this->notificationService->sendPushNotification(
                                    $appointment->user_id,
                                    $providerTitle,
                                    $providerBody,
                                    $providerData
                                );
                            }
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
                                'amount' => $appointment->payment_amount
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
                if (isset($updatableFields['payment_status']) && $oldPaymentStatus !== $updatableFields['payment_status']) {
                    switch ($updatableFields['payment_status']) {
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
                                'amount' => $appointment->payment_amount,
                                'payment_method' => $appointment->payment_method,
                                'original_price' => $appointment->original_price,
                                'discount_amount' => $appointment->discount_amount,
                                'final_price' => $appointment->final_price
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
                                'amount' => $appointment->payment_amount,
                                'payment_method' => $appointment->payment_method,
                                'original_price' => $appointment->original_price,
                                'discount_amount' => $appointment->discount_amount,
                                'final_price' => $appointment->final_price
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
                                'refund_id' => $appointment->payment_id ? 'REF_' . $appointment->payment_id : null
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
                                'payment_method' => $appointment->payment_method
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

                // Check if visit_type changed
                if (isset($updatableFields['visit_type']) && $updatableFields['visit_type'] !== $appointment->visit_type) {
                    // Visit type changed - notify the other party
                    if ($role === 'vendor') {
                        // Provider changed visit type - notify client
                        $clientTitle = 'Appointment Location Changed';
                        $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} has been changed to " .
                            ($updatableFields['visit_type'] === 'home' ? 'a home visit' : 'in-office visit') . ".";
                        $clientData = [
                            'type' => 'visit_type_changed',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName,
                            'visit_type' => $updatableFields['visit_type'],
                            'old_visit_type' => $appointment->visit_type
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->client_id,
                            $clientTitle,
                            $clientBody,
                            $clientData
                        );
                    } else {
                        // Client changed visit type - notify provider
                        $providerTitle = 'Appointment Location Changed';
                        $providerBody = "The appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate} has been changed to " .
                            ($updatableFields['visit_type'] === 'home' ? 'a home visit' : 'in-office visit') . ".";
                        $providerData = [
                            'type' => 'visit_type_changed',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'client_id' => $appointment->client_id,
                            'client_name' => $appointment->client->name,
                            'service_name' => $serviceName,
                            'visit_type' => $updatableFields['visit_type'],
                            'old_visit_type' => $appointment->visit_type
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->user_id,
                            $providerTitle,
                            $providerBody,
                            $providerData
                        );
                    }
                }

                // Load relationships for the response
                $appointment->load(['service', 'client', 'user', 'payment']);

                return $this->success(
                    new AppointmentResponse($appointment),
                    'Appointment updated successfully'
                );
            }

            return $this->error([], 'No valid fields to update', 422);
        } catch (\Exception $e) {
            Log::error('Error updating appointment: ' . $e->getMessage());
            return $this->error([], 'Failed to update appointment', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Get appointment
            $appointment = Appointment::findOrFail($id);

            // Check authorization
            if ($role === 'vendor' && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            } elseif ($role === 'customer' && $appointment->client_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            }

            // Only allow deletion of pending or cancelled appointments
            if (!in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_CANCELLED])) {
                return $this->error([], 'Only pending or cancelled appointments can be deleted', 422);
            }

            // Load related data for notifications
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

            // Begin database transaction
            DB::beginTransaction();

            // Delete related payment if exists
            Payment::where('appointment_id', $appointment->id)->delete();

            // Make time slot available again
            $timeSlot = TimeSlot::where('user_id', $appointment->user_id)
                ->where('date', $appointment->date)
                ->where('start_time', $appointment->start_time)
                ->where('end_time', $appointment->end_time)
                ->first();

            if ($timeSlot) {
                $timeSlot->update(['is_available' => true]);
            }

            // Delete appointment
            $appointment->delete();

            // Send notification about appointment deletion
            if ($role === 'vendor') {
                // Provider deleted - notify client
                $clientTitle = 'Appointment Deleted';
                $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been deleted by the provider.";
                $clientData = [
                    'type' => 'appointment_deleted_by_provider',
                    'appointment_date' => $appointment->date,
                    'appointment_time' => $appointment->start_time,
                    'provider_id' => $appointment->user_id,
                    'provider_name' => $appointment->user->name,
                    'service_name' => $serviceName
                ];

                $this->notificationService->sendPushNotification(
                    $appointment->client_id,
                    $clientTitle,
                    $clientBody,
                    $clientData
                );
            } else {
                // Client deleted - notify provider
                $providerTitle = 'Appointment Deleted';
                $providerBody = "The appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been deleted by the client.";
                $providerData = [
                    'type' => 'appointment_deleted_by_client',
                    'appointment_date' => $appointment->date,
                    'appointment_time' => $appointment->start_time,
                    'client_id' => $appointment->client_id,
                    'client_name' => $appointment->client->name,
                    'service_name' => $serviceName
                ];

                $this->notificationService->sendPushNotification(
                    $appointment->user_id,
                    $providerTitle,
                    $providerBody,
                    $providerData
                );
            }

            // Commit transaction
            DB::commit();

            return $this->success([], 'Appointment deleted successfully');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error deleting appointment: ' . $e->getMessage());
            return $this->error([], 'Failed to delete appointment', 500);
        }
    }

    /**
     * Cancel an appointment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id)
    {
        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Get appointment
            $appointment = Appointment::findOrFail($id);

            // Check authorization
            if ($role === 'vendor' && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            } elseif ($role === 'customer' && $appointment->client_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            }

            // Check if appointment can be cancelled
            if (!$appointment->canBeCancelled()) {
                return $this->error([], 'This appointment cannot be cancelled', 422);
            }

            // Begin database transaction
            DB::beginTransaction();

            // Cancel appointment
            $appointment->cancel();

            // Make time slot available again
            $timeSlot = TimeSlot::where('user_id', $appointment->user_id)
                ->where('date', $appointment->date)
                ->where('start_time', $appointment->start_time)
                ->where('end_time', $appointment->end_time)
                ->first();

            if ($timeSlot) {
                $timeSlot->update(['is_available' => true]);
            }

            // Handle payment refund if needed
            if ($appointment->isPaid()) {
                // In production, you would initiate a refund with PhonePe here
                // For now, just mark as refunded
                $appointment->update(['payment_status' => Appointment::PAYMENT_STATUS_REFUNDED]);

                // Update payment record
                $payment = Payment::where('appointment_id', $appointment->id)->first();
                if ($payment) {
                    $payment->update([
                        'status' => Payment::STATUS_REFUNDED,
                        'payment_details' => array_merge(
                            (array) $payment->payment_details,
                            [
                                'refund' => [
                                    'reason' => 'Appointment cancelled by ' . ($role === 'vendor' ? 'provider' : 'client'),
                                    'initiated_by' => $userId,
                                    'initiated_at' => now()->toIso8601String(),
                                    'refund_id' => 'REF_' . uniqid()
                                ]
                            ]
                        )
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

            // Send notification about cancellation
            if ($role === 'vendor') {
                // Provider cancelled - notify client
                $clientTitle = 'Appointment Cancelled';
                $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the provider.";
                $clientData = [
                    'type' => 'appointment_cancelled_by_provider',
                    'appointment_id' => $appointment->id,
                    'appointment_date' => $appointment->date,
                    'appointment_time' => $appointment->start_time,
                    'provider_id' => $appointment->user_id,
                    'provider_name' => $appointment->user->name,
                    'service_name' => $serviceName,
                    'visit_type' => $appointment->visit_type,
                    'payment_status' => $appointment->payment_status,
                    'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable',
                    'payment_amount' => $appointment->payment_amount,
                    'payment_method' => $appointment->payment_method,
                    'coupon_code' => $appointment->coupon_code,
                    'offer_title' => $appointment->offer_title
                ];

                $this->notificationService->sendPushNotification(
                    $appointment->client_id,
                    $clientTitle,
                    $clientBody,
                    $clientData
                );
            } else {
                // Client cancelled - notify provider
                $providerTitle = 'Appointment Cancelled';
                $providerBody = "The appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the client.";
                $providerData = [
                    'type' => 'appointment_cancelled_by_client',
                    'appointment_id' => $appointment->id,
                    'appointment_date' => $appointment->date,
                    'appointment_time' => $appointment->start_time,
                    'client_id' => $appointment->client_id,
                    'client_name' => $appointment->client->name,
                    'service_name' => $serviceName,
                    'visit_type' => $appointment->visit_type,
                    'payment_status' => $appointment->payment_status,
                    'refund_status' => $appointment->isPaid() ? 'processing' : 'not_applicable',
                    'payment_amount' => $appointment->payment_amount,
                    'payment_method' => $appointment->payment_method,
                    'coupon_code' => $appointment->coupon_code,
                    'offer_title' => $appointment->offer_title
                ];

                $this->notificationService->sendPushNotification(
                    $appointment->user_id,
                    $providerTitle,
                    $providerBody,
                    $providerData
                );
            }

            // Commit transaction
            DB::commit();

            // Load relationships for the response based on appointment type
            if ($appointment->isComboService()) {
                $appointment->load(['comboService.services', 'client', 'user', 'payment']);
            } else {
                $appointment->load(['service', 'client', 'user', 'payment']);
            }

            return $this->success(
                new AppointmentResponse($appointment),
                'Appointment cancelled successfully'
            );
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error cancelling appointment: ' . $e->getMessage());
            return $this->error([], 'Failed to cancel appointment', 500);
        }
    }

    /**
     * Confirm an appointment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm($id)
    {
        try {
            $userId = Auth::id();

            // Only vendors can confirm appointments
            if (Auth::user()->role !== 'vendor') {
                return $this->error([], 'Unauthorized', 403);
            }

            // Get appointment
            $appointment = Appointment::where('user_id', $userId)
                ->where('id', $id)
                ->first();

            if (!$appointment) {
                return $this->error([], 'Appointment not found or unauthorized', 404);
            }

            // Check if appointment can be confirmed
            if ($appointment->status !== Appointment::STATUS_PENDING) {
                return $this->error([], 'Only pending appointments can be confirmed', 422);
            }

            // Confirm appointment
            $appointment->confirm();

            // Load appointment with relationships for notification
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

            // Send confirmation notification to client
            $clientTitle = 'Appointment Confirmed';
            $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been confirmed.";
            $clientData = [
                'type' => 'appointment_confirmed',
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->date,
                'appointment_time' => $appointment->start_time,
                'provider_id' => $appointment->user_id,
                'provider_name' => $appointment->user->name,
                'service_name' => $serviceName
            ];

            $this->notificationService->sendPushNotification(
                $appointment->client_id,
                $clientTitle,
                $clientBody,
                $clientData
            );

            // Load relationships for the response
            $appointment->load(['service', 'client', 'user', 'payment']);

            return $this->success(
                new AppointmentResponse($appointment),
                'Appointment confirmed successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error confirming appointment: ' . $e->getMessage());
            return $this->error([], 'Failed to confirm appointment', 500);
        }
    }

    /**
     * Mark an appointment as completed.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete($id)
    {
        try {
            $userId = Auth::id();

            // Only vendors can mark appointments as completed
            if (Auth::user()->role !== 'vendor') {
                return $this->error([], 'Unauthorized', 403);
            }

            // Get appointment
            $appointment = Appointment::where('user_id', $userId)
                ->where('id', $id)
                ->first();

            if (!$appointment) {
                return $this->error([], 'Appointment not found or unauthorized', 404);
            }

            // Check if appointment can be completed
            if (!$appointment->canBeCompleted()) {
                return $this->error([], 'This appointment cannot be marked as completed', 422);
            }

            // Begin database transaction
            DB::beginTransaction();

            // Complete appointment
            $appointment->complete();

            // Handle payment status update - if pending, change to paid
            if ($appointment->payment_status === Appointment::PAYMENT_STATUS_PENDING) {

                // Check if payment record already exists
                $payment = Payment::where('appointment_id', $appointment->id)->first();

                $paymentInputData = [
                    'original_price' => $appointment->original_price ??
                        ($appointment->service ? $appointment->service->price : 0),
                    'home_visit_fee' => $appointment->home_visit_fee ?? 0,
                    'discount_amount' => $appointment->discount_amount ?? 0
                ];

// Calculate payment breakdown using our formula
                $paymentCalculation = $this->calculatePaymentBreakdown($paymentInputData);

// Update appointment with calculated values
                $appointment->update([
                    'payment_status' => Appointment::PAYMENT_STATUS_PAID,
                    'payment_amount' => $paymentCalculation['payment_amount'],
                    'booking_price' => $paymentCalculation['booking_price'],
                    'platform_fees' => $paymentCalculation['platform_fees'],
                    'other_charges' => $paymentCalculation['other_charges'],
                    'gst' => $paymentCalculation['gst'],
                    'original_price' => $paymentCalculation['original_price'],
                    'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                    'discount_amount' => $paymentCalculation['discount_amount'],
                    'final_price' => $paymentCalculation['final_price']
                ]);

// When creating the payment record
                if ($payment) {
                    // Update existing payment record
                    $payment->update([
                        'status' => Payment::STATUS_PAID,
                        'payment_details' => array_merge(
                            (array) $payment->payment_details,
                            [
                                'completed_payment' => [
                                    'updated_by' => $userId,
                                    'updated_at' => now()->toIso8601String(),
                                    'notes' => 'Payment completed when appointment was marked as completed'
                                ]
                            ]
                        ),
                        // Update with calculated values
                        'amount' => $paymentCalculation['amount'],
                        'original_price' => $paymentCalculation['original_price'],
                        'booking_price' => $paymentCalculation['booking_price'],
                        'platform_fee' => $paymentCalculation['platform_fees'],
                        'other_charges' => $paymentCalculation['other_charges'],
                        'gst_amount' => $paymentCalculation['gst'],
                        'discount_amount' => $paymentCalculation['discount_amount'],
                        'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                        'net_amount' => $paymentCalculation['amount'],
                        'vendor_earnings' => $paymentCalculation['vendor_earnings'],
                        'admin_earnings' => $paymentCalculation['admin_earnings']
                    ]);
                } else {
                    // Create new payment record with calculated values
                    Payment::create([
                        'appointment_id' => $appointment->id,
                        'user_id' => $appointment->client_id,
                        'provider_id' => $appointment->user_id,
                        'transaction_id' => 'COMP_' . uniqid(),
                        'payment_method' => $appointment->payment_method ?? 'cash',
                        'payment_mode' => $appointment->payment_method ?? 'cash',
                        'amount' => $paymentCalculation['amount'],
                        'currency' => 'INR',
                        'status' => Payment::STATUS_PAID,
                        'payment_details' => [
                            'completed_payment' => [
                                'created_by' => $userId,
                                'created_at' => now()->toIso8601String(),
                                'notes' => 'Payment automatically marked as paid on completion'
                            ]
                        ],
                        // Add calculated payment breakdown fields
                        'original_price' => $paymentCalculation['original_price'],
                        'booking_price' => $paymentCalculation['booking_price'],
                        'platform_fee' => $paymentCalculation['platform_fees'],
                        'other_charges' => $paymentCalculation['other_charges'],
                        'gst_amount' => $paymentCalculation['gst'],
                        'discount_amount' => $paymentCalculation['discount_amount'],
                        'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                        'net_amount' => $paymentCalculation['amount'],
                        'vendor_earnings' => $paymentCalculation['vendor_earnings'],
                        'admin_earnings' => $paymentCalculation['admin_earnings'],

                        // Copy over discount and offer related fields
                        'discount_percentage' => $appointment->discount_percentage,
                        'coupon_code' => $appointment->coupon_code,
                        'offer_title' => $appointment->offer_title,
                        'vendor_offer_id' => $appointment->vendor_offer_id,
                        'admin_offer_id' => $appointment->admin_offer_id,
                        'offer_type' => $appointment->offer_type,
                    ]);
                }
            }

            // Load appointment with relationships for notification
            $appointment->load(['service', 'comboService', 'client', 'user']);

            // Get service name
            $serviceName = '';
            if ($appointment->service) {
                $serviceName = $appointment->service->name;
            } elseif ($appointment->comboService) {
                $serviceName = $appointment->comboService->name;
            }

            // Send completion notification to client
            $clientTitle = 'Appointment Completed';
            $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} has been marked as completed.";
            $clientData = [
                'type' => 'appointment_completed',
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->date,
                'appointment_time' => $appointment->start_time,
                'provider_id' => $appointment->user_id,
                'provider_name' => $appointment->user->name,
                'service_name' => $serviceName
            ];

            $this->notificationService->sendPushNotification(
                $appointment->client_id,
                $clientTitle,
                $clientBody,
                $clientData
            );

            // Commit transaction
            DB::commit();

            // Load relationships for the response
            $appointment->load(['service', 'client', 'user', 'payment']);

            return $this->success(
                new AppointmentResponse($appointment),
                'Appointment marked as completed successfully'
            );
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error completing appointment: ' . $e->getMessage());
            return $this->error([], 'Failed to complete appointment', 500);
        }
    }


    /**
     * Reschedule an appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reschedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Get appointment
            $appointment = Appointment::findOrFail($id);

            // Check authorization
            if ($role === 'vendor' && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            } elseif ($role === 'customer' && $appointment->client_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            }

            // Check if appointment can be rescheduled
            if (!$appointment->canBeRescheduled()) {
                return $this->error([], 'This appointment cannot be rescheduled', 422);
            }

            // Get new time slot
            $newTimeSlot = TimeSlot::findOrFail($request->time_slot_id);

            // Check if new time slot is available
            if (!$newTimeSlot->is_available) {
                return $this->error([], 'The selected time slot is not available', 422);
            }

            // Check if new time slot belongs to the same provider
            if ($newTimeSlot->user_id !== $appointment->user_id) {
                return $this->error([], 'The selected time slot does not belong to the service provider', 422);
            }

            // Store the old date and time for notification
            $oldDate = Carbon::parse($appointment->date)->format('D, M d, Y');
            $oldTime = Carbon::parse($appointment->start_time)->format('h:i A');

            // Begin database transaction
            DB::beginTransaction();

            // Make old time slot available again
            $oldTimeSlot = TimeSlot::where('user_id', $appointment->user_id)
                ->where('date', $appointment->date)
                ->where('start_time', $appointment->start_time)
                ->where('end_time', $appointment->end_time)
                ->first();

            if ($oldTimeSlot) {
                $oldTimeSlot->update(['is_available' => true]);
            }

            // Update appointment with new schedule
            $appointment->update([
                'date' => $request->date,
                'start_time' => $newTimeSlot->start_time,
                'end_time' => $newTimeSlot->end_time
            ]);

            // Mark new time slot as unavailable
            $newTimeSlot->update(['is_available' => false]);

            // Load appointment with relationships for notification
            $appointment->load(['service', 'comboService', 'client', 'user']);

            // Get service name
            $serviceName = '';
            if ($appointment->service) {
                $serviceName = $appointment->service->name;
            } elseif ($appointment->comboService) {
                $serviceName = $appointment->comboService->name;
            }

            // Format new date and time for notifications
            $newDate = Carbon::parse($request->date)->format('D, M d, Y');
            $newTime = Carbon::parse($newTimeSlot->start_time)->format('h:i A');

            // Send rescheduling notification
            if ($role === 'vendor') {
                // Provider rescheduled - notify client
                $clientTitle = 'Appointment Rescheduled';
                $clientBody = "Your appointment with {$appointment->user->name} for {$serviceName} has been rescheduled from {$oldDate} at {$oldTime} to {$newDate} at {$newTime}.";
                $clientData = [
                    'type' => 'appointment_rescheduled_by_provider',
                    'appointment_id' => $appointment->id,
                    'appointment_old_date' => $oldDate,
                    'appointment_old_time' => $oldTime,
                    'appointment_new_date' => $newDate,
                    'appointment_new_time' => $newTime,
                    'provider_id' => $appointment->user_id,
                    'provider_name' => $appointment->user->name,
                    'service_name' => $serviceName
                ];

                $this->notificationService->sendPushNotification(
                    $appointment->client_id,
                    $clientTitle,
                    $clientBody,
                    $clientData
                );
            } else {
                // Client rescheduled - notify provider
                $providerTitle = 'Appointment Rescheduled';
                $providerBody = "The appointment with {$appointment->client->name} for {$serviceName} has been rescheduled from {$oldDate} at {$oldTime} to {$newDate} at {$newTime}.";
                $providerData = [
                    'type' => 'appointment_rescheduled_by_client',
                    'appointment_id' => $appointment->id,
                    'appointment_old_date' => $oldDate,
                    'appointment_old_time' => $oldTime,
                    'appointment_new_date' => $newDate,
                    'appointment_new_time' => $newTime,
                    'client_id' => $appointment->client_id,
                    'client_name' => $appointment->client->name,
                    'service_name' => $serviceName
                ];

                $this->notificationService->sendPushNotification(
                    $appointment->user_id,
                    $providerTitle,
                    $providerBody,
                    $providerData
                );
            }

            // Commit transaction
            DB::commit();

            // Load relationships for the response based on appointment type
            if ($appointment->isComboService()) {
                $appointment->load(['comboService.services', 'client', 'user', 'payment']);
            } else {
                $appointment->load(['service', 'client', 'user', 'payment']);
            }

            return $this->success(
                new AppointmentResponse($appointment),
                'Appointment rescheduled successfully'
            );
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error rescheduling appointment: ' . $e->getMessage());
            return $this->error([], 'Failed to reschedule appointment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get appointment statistics for dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Base query
            $baseQuery = Appointment::query();

            // Filter by user role
            if ($role === 'vendor') {
                $baseQuery->where('user_id', $userId);
            } else {
                $baseQuery->where('client_id', $userId);
            }

            // Get counts by status using cloned queries
            $counts = [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status', Appointment::STATUS_PENDING)->count(),
                'confirmed' => (clone $baseQuery)->where('status', Appointment::STATUS_CONFIRMED)->count(),
                'cancelled' => (clone $baseQuery)->where('status', Appointment::STATUS_CANCELLED)->count(),
                'completed' => (clone $baseQuery)->where('status', Appointment::STATUS_COMPLETED)->count(),
            ];

            // Get appointment type counts
            $counts['services'] = (clone $baseQuery)->whereNotNull('service_id')
                ->whereNull('combo_service_id')
                ->count();
            $counts['combos'] = (clone $baseQuery)->whereNotNull('combo_service_id')->count();

            // Get upcoming appointments
            $today = Carbon::today();
            $counts['upcoming'] = (clone $baseQuery)->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                ->where('date', '>=', $today)
                ->count();

            // Get today's appointments
            $counts['today'] = (clone $baseQuery)->whereDate('date', $today)->count();

            // Get payment statistics if vendor
            if ($role === 'vendor') {
                // Get paid appointments with a new query to avoid modification issues
                $paidAppointments = (clone $baseQuery)
                    ->where('payment_status', Appointment::PAYMENT_STATUS_PAID)
                    ->get();

                // Calculate totals separately for regular and combo appointments
                $regularTotal = $paidAppointments
                    ->where('combo_service_id', null)
                    ->sum('payment_amount');

                $comboTotal = $paidAppointments
                    ->where('combo_service_id', '!=', null)
                    ->sum('payment_amount');

                $totalEarnings = $regularTotal + $comboTotal;
                $totalDiscount = $paidAppointments->sum('discount_amount');

                $counts['total_earnings'] = $totalEarnings;
                $counts['regular_service_earnings'] = $regularTotal;
                $counts['combo_service_earnings'] = $comboTotal;
                $counts['total_discounts'] = $totalDiscount;

                // Get monthly earnings for the current year
                $monthlyEarnings = [];
                $currentYear = Carbon::now()->year;

                for ($month = 1; $month <= 12; $month++) {
                    $startDate = Carbon::createFromDate($currentYear, $month, 1)->startOfMonth();
                    $endDate = Carbon::createFromDate($currentYear, $month, 1)->endOfMonth();

                    // Use a new query for each month
                    $monthAppointments = (clone $baseQuery)
                        ->where('payment_status', Appointment::PAYMENT_STATUS_PAID)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->get();

                    $regularEarnings = $monthAppointments
                        ->where('combo_service_id', null)
                        ->sum('payment_amount');

                    $comboEarnings = $monthAppointments
                        ->where('combo_service_id', '!=', null)
                        ->sum('payment_amount');

                    $totalMonthEarnings = $regularEarnings + $comboEarnings;
                    $monthDiscount = $monthAppointments->sum('discount_amount');

                    $monthlyEarnings[] = [
                        'month' => $startDate->format('M'),
                        'total_earnings' => $totalMonthEarnings,
                        'regular_service_earnings' => $regularEarnings,
                        'combo_service_earnings' => $comboEarnings,
                        'total_discounts' => $monthDiscount
                    ];
                }

                $counts['monthly_earnings'] = $monthlyEarnings;

                // Get most popular services and combos
                // These already create new queries, so we don't need to clone $baseQuery
                $serviceAppointments = Appointment::where('user_id', $userId)
                    ->whereNotNull('service_id')
                    ->whereNull('combo_service_id')
                    ->whereIn('status', [Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED])
                    ->get()
                    ->groupBy('service_id')
                    ->map(function ($group) {
                        return [
                            'count' => $group->count(),
                            'service' => $group->first()->service
                        ];
                    })
                    ->sortByDesc('count')
                    ->take(5)
                    ->values();

                $comboAppointments = Appointment::where('user_id', $userId)
                    ->whereNotNull('combo_service_id')
                    ->whereIn('status', [Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED])
                    ->with('comboService')
                    ->get()
                    ->groupBy('combo_service_id')
                    ->map(function ($group) {
                        return [
                            'count' => $group->count(),
                            'combo_service' => $group->first()->comboService
                        ];
                    })
                    ->sortByDesc('count')
                    ->take(5)
                    ->values();

                $counts['popular_services'] = $serviceAppointments;
                $counts['popular_combos'] = $comboAppointments;
            }

            return $this->success($counts, 'Appointment statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving appointment statistics: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve appointment statistics', 500);
        }
    }

    /**
     * Get appointments for a specific date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointmentsByDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $role = Auth::user()->role;
            $date = $request->date;

            // Base query
            $query = Appointment::query();

            // Filter by user role
            if ($role === 'vendor') {
                $query->where('user_id', $userId);
            } else {
                $query->where('client_id', $userId);
            }

            // Filter by date
            $query->whereDate('date', $date);

            // Include relationships
            $query->with(['service', 'client', 'user', 'payment']);

            // Order by time
            $query->orderBy('start_time');

            $appointments = $query->get();

            return $this->success(
                AppointmentResponse::collection($appointments),
                'Appointments for date retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving appointments by date: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve appointments for date', 500);
        }
    }

    /**
     * Update appointment payment status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
            'payment_id' => 'required_if:payment_status,paid|nullable|string',
            'payment_method' => 'required_if:payment_status,paid|nullable|string',
            'payment_amount' => 'required_if:payment_status,paid|nullable|numeric',
            'payment_details' => 'nullable|json',
            // New fields
            'original_price' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'discount_percentage' => 'nullable|numeric',
            'final_price' => 'nullable|numeric',
            'booking_price' => 'nullable|numeric',
            'platform_fees' => 'nullable|numeric',
            'other_charges' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'home_visit_fee' => 'nullable|numeric',
            'additional_services_fee' => 'nullable|numeric',
            'coupon_code' => 'nullable|string',
            'offer_title' => 'nullable|string',
            'vendor_offer_id' => 'nullable|integer',
            'admin_offer_id' => 'nullable|integer',
            'offer_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();

            // Get appointment
            $appointment = Appointment::findOrFail($id);

            // Check authorization (only clients or the service provider can update payment status)
            if ($appointment->client_id !== $userId && $appointment->user_id !== $userId) {
                return $this->error([], 'Unauthorized', 403);
            }

            // Store the old payment status for comparison
            $oldPaymentStatus = $appointment->payment_status;

            // Begin database transaction
            DB::beginTransaction();

            // Update appointment payment status and all payment-related fields
            $paymentInputData = [
                'original_price' => $request->original_price ?? $appointment->original_price,
                'home_visit_fee' => $request->home_visit_fee ?? $appointment->home_visit_fee ?? 0,
                'discount_amount' => $request->discount_amount ?? $appointment->discount_amount ?? 0
            ];

// Calculate payment breakdown using our formula
            $paymentCalculation = $this->calculatePaymentBreakdown($paymentInputData);

// Update appointment with all the collected data and calculated values
            $updateData = [
                'payment_status' => $request->payment_status,
                'payment_id' => $request->payment_id,
                'payment_method' => $request->payment_method,
                'payment_amount' => $paymentCalculation['payment_amount']
            ];

// Add calculated fields
            $updateData['original_price'] = $paymentCalculation['original_price'];
            $updateData['booking_price'] = $paymentCalculation['booking_price'];
            $updateData['platform_fees'] = $paymentCalculation['platform_fees'];
            $updateData['other_charges'] = $paymentCalculation['other_charges'];
            $updateData['gst'] = $paymentCalculation['gst'];
            $updateData['home_visit_fee'] = $paymentCalculation['home_visit_fee'];
            $updateData['discount_amount'] = $paymentCalculation['discount_amount'];
            $updateData['final_price'] = $paymentCalculation['final_price'];

// Add any other request fields
            if ($request->has('discount_percentage')) {
                $updateData['discount_percentage'] = $request->discount_percentage;
            }

            if ($request->has('additional_services_fee')) {
                $updateData['additional_services_fee'] = $request->additional_services_fee;
            }

// Add offer-related fields
            if ($request->has('coupon_code')) {
                $updateData['coupon_code'] = $request->coupon_code;
            }

            if ($request->has('offer_title')) {
                $updateData['offer_title'] = $request->offer_title;
            }

            if ($request->has('vendor_offer_id')) {
                $updateData['vendor_offer_id'] = $request->vendor_offer_id;
            }

            if ($request->has('admin_offer_id')) {
                $updateData['admin_offer_id'] = $request->admin_offer_id;
            }

            if ($request->has('offer_type')) {
                $updateData['offer_type'] = $request->offer_type;
            }

// Update appointment
            $appointment->update($updateData);

// Then when creating or updating the payment record:
            if ($request->payment_status === Appointment::PAYMENT_STATUS_PAID) {
                // Check if payment record already exists
                $payment = Payment::where('appointment_id', $appointment->id)->first();

                if ($payment) {
                    // Update existing payment with calculated values
                    $paymentUpdateData = [
                        'transaction_id' => $request->payment_id,
                        'payment_method' => $request->payment_method,
                        'payment_mode' => $request->payment_method, // Using method as mode
                        'amount' => $paymentCalculation['amount'],
                        'status' => Payment::STATUS_PAID,

                        // Add calculated breakdown fields
                        'original_price' => $paymentCalculation['original_price'],
                        'booking_price' => $paymentCalculation['booking_price'],
                        'platform_fee' => $paymentCalculation['platform_fees'],
                        'other_charges' => $paymentCalculation['other_charges'],
                        'gst_amount' => $paymentCalculation['gst'],
                        'discount_amount' => $paymentCalculation['discount_amount'],
                        'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                        'net_amount' => $paymentCalculation['amount'],
                        'vendor_earnings' => $paymentCalculation['vendor_earnings'],
                        'admin_earnings' => $paymentCalculation['admin_earnings'],
                    ];

                    // Add payment details if provided
                    if ($request->has('payment_details')) {
                        $paymentUpdateData['payment_details'] = $request->payment_details;
                    }

                    // Add other fields from request if provided
                    if ($request->has('discount_percentage')) {
                        $paymentUpdateData['discount_percentage'] = $request->discount_percentage;
                    }

                    if ($request->has('additional_services_fee')) {
                        $paymentUpdateData['additional_services_fee'] = $request->additional_services_fee;
                    }

                    // Add offer-related fields if provided
                    if ($request->has('coupon_code')) {
                        $paymentUpdateData['coupon_code'] = $request->coupon_code;
                    }

                    if ($request->has('offer_title')) {
                        $paymentUpdateData['offer_title'] = $request->offer_title;
                    }

                    if ($request->has('vendor_offer_id')) {
                        $paymentUpdateData['vendor_offer_id'] = $request->vendor_offer_id;
                    }

                    if ($request->has('admin_offer_id')) {
                        $paymentUpdateData['admin_offer_id'] = $request->admin_offer_id;
                    }

                    if ($request->has('offer_type')) {
                        $paymentUpdateData['offer_type'] = $request->offer_type;
                    }

                    $payment->update($paymentUpdateData);
                } else {
                    // Create new payment record with calculated values
                    $paymentData = [
                        'appointment_id' => $appointment->id,
                        'user_id' => $appointment->client_id,
                        'provider_id' => $appointment->user_id,
                        'transaction_id' => $request->payment_id,
                        'payment_method' => $request->payment_method,
                        'payment_mode' => $request->payment_method,
                        'amount' => $paymentCalculation['amount'],
                        'currency' => 'INR',
                        'status' => Payment::STATUS_PAID,

                        // Add calculated breakdown fields
                        'original_price' => $paymentCalculation['original_price'],
                        'booking_price' => $paymentCalculation['booking_price'],
                        'platform_fee' => $paymentCalculation['platform_fees'],
                        'other_charges' => $paymentCalculation['other_charges'],
                        'gst_amount' => $paymentCalculation['gst'],
                        'discount_amount' => $paymentCalculation['discount_amount'],
                        'home_visit_fee' => $paymentCalculation['home_visit_fee'],
                        'net_amount' => $paymentCalculation['amount'],
                        'vendor_earnings' => $paymentCalculation['vendor_earnings'],
                        'admin_earnings' => $paymentCalculation['admin_earnings'],
                    ];

                    // Add payment details if provided
                    if ($request->has('payment_details')) {
                        $paymentData['payment_details'] = $request->payment_details;
                    }

                    // Add other fields from request if provided
                    if ($request->has('discount_percentage')) {
                        $paymentData['discount_percentage'] = $request->discount_percentage;
                    }

                    if ($request->has('additional_services_fee')) {
                        $paymentData['additional_services_fee'] = $request->additional_services_fee;
                    }

                    // Add offer-related fields if provided
                    if ($request->has('coupon_code')) {
                        $paymentData['coupon_code'] = $request->coupon_code;
                    }

                    if ($request->has('offer_title')) {
                        $paymentData['offer_title'] = $request->offer_title;
                    }

                    if ($request->has('vendor_offer_id')) {
                        $paymentData['vendor_offer_id'] = $request->vendor_offer_id;
                    }

                    if ($request->has('admin_offer_id')) {
                        $paymentData['admin_offer_id'] = $request->admin_offer_id;
                    }

                    if ($request->has('offer_type')) {
                        $paymentData['offer_type'] = $request->offer_type;
                    }

                    Payment::create($paymentData);
                }
            } elseif ($request->payment_status === Appointment::PAYMENT_STATUS_FAILED) {
                // Update payment record if exists
                $payment = Payment::where('appointment_id', $appointment->id)->first();

                if ($payment) {
                    $payment->update([
                        'status' => Payment::STATUS_FAILED,
                        'payment_details' => array_merge(
                            (array) $payment->payment_details,
                            [
                                'failure' => [
                                    'reason' => $request->reason ?? 'Payment failed',
                                    'timestamp' => now()->toIso8601String()
                                ]
                            ]
                        )
                    ]);
                }
            } elseif ($request->payment_status === Appointment::PAYMENT_STATUS_REFUNDED) {
                // Update payment record if exists
                $payment = Payment::where('appointment_id', $appointment->id)->first();

                if ($payment) {
                    $payment->update([
                        'status' => Payment::STATUS_REFUNDED,
                        'payment_details' => array_merge(
                            (array) $payment->payment_details,
                            [
                                'refund' => [
                                    'reason' => $request->reason ?? 'Payment refunded',
                                    'initiated_by' => $userId,
                                    'initiated_at' => now()->toIso8601String(),
                                    'refund_id' => $request->refund_id ?? ('REF_' . uniqid())
                                ]
                            ]
                        )
                    ]);
                }
            }

            // Load appointment with relationships for notification
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

            // Only send notification if payment status has changed
            if ($oldPaymentStatus !== $request->payment_status) {
                switch ($request->payment_status) {
                    case Appointment::PAYMENT_STATUS_PAID:
                        // Payment made - notify both parties
                        // Notify provider
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
                            'amount' => $request->payment_amount,
                            'payment_method' => $request->payment_method,
                            'visit_type' => $appointment->visit_type,
                            'original_price' => $appointment->original_price,
                            'discount_amount' => $appointment->discount_amount,
                            'final_price' => $appointment->final_price
                        ];

                        $this->notificationService->sendPushNotification(
                            $appointment->user_id,
                            $providerTitle,
                            $providerBody,
                            $providerData
                        );

                        // Notify client
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
                            'amount' => $request->payment_amount,
                            'payment_method' => $request->payment_method,
                            'visit_type' => $appointment->visit_type,
                            'original_price' => $appointment->original_price,
                            'discount_amount' => $appointment->discount_amount,
                            'final_price' => $appointment->final_price
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
                            'amount' => $appointment->payment_amount,
                            'payment_method' => $appointment->payment_method,
                            'visit_type' => $appointment->visit_type,
                            'reason' => $request->reason ?? 'Payment processing error',
                            'retry_url' => "/appointments/{$appointment->id}/payment"
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
                            'amount' => $appointment->payment_amount,
                            'payment_method' => $appointment->payment_method,
                            'visit_type' => $appointment->visit_type,
                            'refund_id' => $request->refund_id ?? ('REF_' . uniqid())
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

            // Load relationships for the response
            $appointment->load(['service', 'client', 'user', 'payment']);

            return $this->success(
                new AppointmentResponse($appointment),
                'Appointment payment status updated successfully'
            );
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error updating appointment payment status: ' . $e->getMessage());
            return $this->error([], 'Failed to update appointment payment status', 500);
        }
    }

    /**
     * Bulk cancel appointments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_ids' => 'required|array',
            'appointment_ids.*' => 'required|exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Begin database transaction
            DB::beginTransaction();

            $successCount = 0;
            $failedIds = [];

            // Placeholder for notifications that need to be sent after transaction is committed
            $notificationsToSend = [];

            foreach ($request->appointment_ids as $appointmentId) {
                // Get appointment
                $appointment = Appointment::find($appointmentId);

                if (!$appointment) {
                    $failedIds[] = [
                        'id' => $appointmentId,
                        'reason' => 'Appointment not found'
                    ];
                    continue;
                }

                // Check authorization
                if ($role === 'vendor' && $appointment->user_id !== $userId) {
                    $failedIds[] = [
                        'id' => $appointmentId,
                        'reason' => 'Unauthorized'
                    ];
                    continue;
                } elseif ($role === 'customer' && $appointment->client_id !== $userId) {
                    $failedIds[] = [
                        'id' => $appointmentId,
                        'reason' => 'Unauthorized'
                    ];
                    continue;
                }

                // Check if appointment can be cancelled
                if (!$appointment->canBeCancelled()) {
                    $failedIds[] = [
                        'id' => $appointmentId,
                        'reason' => 'Cannot be cancelled'
                    ];
                    continue;
                }

                // Load appointment with relationships for notification
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

                // Cancel appointment
                $appointment->cancel();

                // Make time slot available again
                $timeSlot = TimeSlot::where('user_id', $appointment->user_id)
                    ->where('date', $appointment->date)
                    ->where('start_time', $appointment->start_time)
                    ->where('end_time', $appointment->end_time)
                    ->first();

                if ($timeSlot) {
                    $timeSlot->update(['is_available' => true]);
                }

                // Handle payment refund if needed
                if ($appointment->isPaid()) {
                    // In production, you would initiate a refund with PhonePe here
                    // For now, just mark as refunded
                    $appointment->update(['payment_status' => Appointment::PAYMENT_STATUS_REFUNDED]);

                    // Update payment record
                    $payment = Payment::where('appointment_id', $appointment->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => Payment::STATUS_REFUNDED,
                            'payment_details' => array_merge(
                                (array) $payment->payment_details,
                                [
                                    'refund' => [
                                        'reason' => 'Bulk cancellation',
                                        'initiated_by' => $userId,
                                        'initiated_at' => now()->toIso8601String(),
                                        'refund_id' => 'REF_BULK_' . uniqid()
                                    ]
                                ]
                            )
                        ]);
                    }
                }

                // Prepare notification data
                if ($role === 'vendor') {
                    // Provider cancelled - prepare notification for client
                    $notificationsToSend[] = [
                        'recipient_id' => $appointment->client_id,
                        'title' => 'Appointment Cancelled',
                        'body' => "Your appointment with {$appointment->user->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the provider.",
                        'data' => [
                            'type' => 'appointment_cancelled_by_provider',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'provider_id' => $appointment->user_id,
                            'provider_name' => $appointment->user->name,
                            'service_name' => $serviceName
                        ]
                    ];
                } else {
                    // Client cancelled - prepare notification for provider
                    $notificationsToSend[] = [
                        'recipient_id' => $appointment->user_id,
                        'title' => 'Appointment Cancelled',
                        'body' => "The appointment with {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime} has been cancelled by the client.",
                        'data' => [
                            'type' => 'appointment_cancelled_by_client',
                            'appointment_id' => $appointment->id,
                            'appointment_date' => $appointment->date,
                            'appointment_time' => $appointment->start_time,
                            'client_id' => $appointment->client_id,
                            'client_name' => $appointment->client->name,
                            'service_name' => $serviceName
                        ]
                    ];
                }

                $successCount++;
            }

            // Commit transaction
            DB::commit();

            // Send all notifications after transaction has been committed
            foreach ($notificationsToSend as $notification) {
                $this->notificationService->sendPushNotification(
                    $notification['recipient_id'],
                    $notification['title'],
                    $notification['body'],
                    $notification['data']
                );
            }

            return $this->success(
                [
                    'success_count' => $successCount,
                    'failed' => $failedIds
                ],
                'Bulk cancellation completed'
            );
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error in bulk cancellation: ' . $e->getMessage());
            return $this->error([], 'Failed to process bulk cancellation', 500);
        }
    }

    /**
     * Get upcoming appointments.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upcoming()
    {
        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Base query
            $query = Appointment::query();

            // Filter by user role
            if ($role === 'vendor') {
                $query->where('user_id', $userId);
            } else {
                $query->where('client_id', $userId);
            }

            // Get upcoming appointments (today and future dates)
            $today = Carbon::today();
            $query->where('date', '>=', $today)
                ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                ->orderBy('date')
                ->orderBy('start_time');

            // Include relationships
            $query->with(['service', 'client', 'user', 'payment']);

            // Get next 10 appointments
            $appointments = $query->limit(10)->get();

            return $this->success(
                AppointmentResponse::collection($appointments),
                'Upcoming appointments retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving upcoming appointments: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve upcoming appointments', 500);
        }
    }

    /**
     * Get today's appointments.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function today()
    {
        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // Base query
            $query = Appointment::query();

            // Filter by user role
            if ($role === 'vendor') {
                $query->where('user_id', $userId);
            } else {
                $query->where('client_id', $userId);
            }

            // Get today's appointments
            $today = Carbon::today();
            $query->whereDate('date', $today)
                ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                ->orderBy('start_time');

            // Include relationships
            $query->with(['service', 'client', 'user', 'payment']);

            $appointments = $query->get();

            return $this->success(
                AppointmentResponse::collection($appointments),
                'Today\'s appointments retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving today\'s appointments: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve today\'s appointments', 500);
        }
    }

    /**
     * Get appointments with specific payment status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByPaymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $role = Auth::user()->role;
            $paymentStatus = $request->payment_status;

            // Base query
            $query = Appointment::query();

            // Filter by user role
            if ($role === 'vendor') {
                $query->where('user_id', $userId);
            } else {
                $query->where('client_id', $userId);
            }

            // Filter by payment status
            $query->where('payment_status', $paymentStatus);

            // Order by date and time
            $query->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc');

            // Include relationships
            $query->with(['service', 'client', 'user', 'payment']);

            // Paginate results
            $appointments = $query->paginate(10);

            return $this->success(
                AppointmentResponse::collection($appointments),
                "Appointments with payment status '{$paymentStatus}' retrieved successfully",
                200,
                [
                    'pagination' => [
                        'total' => $appointments->total(),
                        'per_page' => $appointments->perPage(),
                        'current_page' => $appointments->currentPage(),
                        'last_page' => $appointments->lastPage(),
                    ]
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving appointments by payment status: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve appointments by payment status', 500);
        }
    }

    /**
     * Get payment summary for a date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $role = Auth::user()->role;

            // This endpoint is primarily for vendors
            if ($role !== 'vendor') {
                return $this->error([], 'Unauthorized', 403);
            }

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            // Get all appointments in date range
            $appointments = Appointment::where('user_id', $userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            // Calculate summary statistics
            $totalAppointments = $appointments->count();
            $totalPaid = $appointments->where('payment_status', Appointment::PAYMENT_STATUS_PAID)->count();
            $totalPending = $appointments->where('payment_status', Appointment::PAYMENT_STATUS_PENDING)->count();
            $totalFailed = $appointments->where('payment_status', Appointment::PAYMENT_STATUS_FAILED)->count();
            $totalRefunded = $appointments->where('payment_status', Appointment::PAYMENT_STATUS_REFUNDED)->count();

            $totalRevenue = $appointments->where('payment_status', Appointment::PAYMENT_STATUS_PAID)
                ->sum('payment_amount');

            $totalRefundAmount = $appointments->where('payment_status', Appointment::PAYMENT_STATUS_REFUNDED)
                ->sum('payment_amount');

            // Calculate day-by-day breakdown
            $dailyBreakdown = [];
            $currentDate = clone $startDate;

            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');
                $dayAppointments = $appointments->filter(function ($appointment) use ($dateString) {
                    return $appointment->date->format('Y-m-d') === $dateString;
                });

                $dailyBreakdown[] = [
                    'date' => $dateString,
                    'formatted_date' => $currentDate->format('M d, Y'),
                    'day_of_week' => $currentDate->format('l'),
                    'total_appointments' => $dayAppointments->count(),
                    'total_paid' => $dayAppointments->where('payment_status', Appointment::PAYMENT_STATUS_PAID)->count(),
                    'revenue' => $dayAppointments->where('payment_status', Appointment::PAYMENT_STATUS_PAID)
                        ->sum('payment_amount')
                ];

                $currentDate->addDay();
            }

            // Prepare summary response
            $summary = [
                'date_range' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'formatted_range' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y')
                ],
                'total_appointments' => $totalAppointments,
                'payment_status' => [
                    'paid' => $totalPaid,
                    'pending' => $totalPending,
                    'failed' => $totalFailed,
                    'refunded' => $totalRefunded
                ],
                'revenue' => [
                    'total' => $totalRevenue,
                    'formatted_total' => '₹' . number_format($totalRevenue, 2),
                    'refunded' => $totalRefundAmount,
                    'formatted_refunded' => '₹' . number_format($totalRefundAmount, 2),
                    'net' => $totalRevenue - $totalRefundAmount,
                    'formatted_net' => '₹' . number_format($totalRevenue - $totalRefundAmount, 2)
                ],
                'daily_breakdown' => $dailyBreakdown
            ];

            return $this->success($summary, 'Payment summary retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving payment summary: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve payment summary', 500);
        }
    }

    /**
     * Calculate payment breakdown according to the formula.
     *
     * Vendor earnings = Original price + Home visit fee - Discount
     * Admin earnings = Platform fee + Other charges + GST
     * Platform fee = Fixed 8 Rs
     * Other charges = 2% of Booking Price (which is Original price + Home visit fee - Discount)
     * GST = 18% of (Platform fee + Other charges)
     *
     * @param  array  $data Input data containing amounts
     * @return array Calculated payment breakdown
     */
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
            'platform_fees' => $platformFee,
            'other_charges' => $otherCharges,
            'gst' => $gstAmount,
            'admin_earnings' => $adminEarnings,
            'vendor_earnings' => $bookingPrice,
            'amount' => $totalAmount,
            'payment_amount' => $totalAmount,
            'final_price' => $totalAmount
        ];
    }
}
