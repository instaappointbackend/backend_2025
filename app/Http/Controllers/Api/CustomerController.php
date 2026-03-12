<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerAppointmentResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\CustomerStatsResource;
use App\Models\Appointment;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all customers for the vendor
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $vendor = Auth::user();

            // Ensure user is a vendor
            if ($vendor->role !== 'vendor') {
                return $this->error([], 'Unauthorized. Only vendors can access customer data.', 403);
            }

            // Get appointments where this vendor is the provider
            $appointmentsQuery = Appointment::where('user_id', $vendor->id);

            // Get unique client_ids from those appointments
            $clientIds = $appointmentsQuery->pluck('client_id')->unique()->toArray();

            // Query to get customers (users who are clients of this vendor)
            $customersQuery = User::whereIn('id', $clientIds);

            // Apply search filter if provided
            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->search;
                $customersQuery->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            }

            // Get the customers with additional information
            $customers = $customersQuery->get();

            // Enhance each customer with appointment history data
            $enhancedCustomers = $customers->map(function ($customer) use ($vendor) {
                // Count total appointments for this customer with this vendor
                $appointments = Appointment::where('user_id', $vendor->id)
                    ->where('client_id', $customer->id)
                    ->orderBy('date', 'desc')
                    ->orderBy('start_time', 'desc')
                    ->get();

                $totalVisits = $appointments->count();
                $lastAppointment = $appointments->first();

                // Format last visit date
                $lastVisitDate = null;
                if ($lastAppointment) {
                    $appointmentDate = Carbon::parse($lastAppointment->date);
                    $now = Carbon::now();

                    if ($appointmentDate->isToday()) {
                        $lastVisitDate = 'Today';
                    } elseif ($appointmentDate->isYesterday()) {
                        $lastVisitDate = 'Yesterday';
                    } elseif ($appointmentDate->diffInDays($now) < 7) {
                        $lastVisitDate = (int) $appointmentDate->diffInDays($now).' days ago';
                    } else {
                        $lastVisitDate = $appointmentDate->format('M d, Y');
                    }
                }

                // Add custom fields to customer
                $customer->totalVisits = $totalVisits;
                $customer->lastVisit = $lastVisitDate;
                $customer->lastAppointmentDate = $lastAppointment ? $lastAppointment->date : null;

                return $customer;
            });

            // Filter for recent customers if requested
            if ($request->has('recent') && $request->recent === 'true') {
                $enhancedCustomers = $enhancedCustomers->filter(function ($customer) {
                    if (! $customer->lastAppointmentDate) {
                        return false;
                    }

                    $lastAppointmentDate = Carbon::parse($customer->lastAppointmentDate);
                    $daysAgo = $lastAppointmentDate->diffInDays(Carbon::now());

                    return $daysAgo <= 30; // Consider customers from the last 30 days as recent
                });
            }

            // Filter for frequent customers if requested
            if ($request->has('frequent') && $request->frequent === 'true') {
                $enhancedCustomers = $enhancedCustomers->filter(function ($customer) {
                    return $customer->totalVisits >= 5; // Consider customers with 5+ visits as frequent
                });
            }

            // Sort by most recent visit by default
            $enhancedCustomers = $enhancedCustomers->sortByDesc('lastAppointmentDate')->values();

            // Transform using CustomerResource
            return $this->success(
                CustomerResource::collection($enhancedCustomers),
                'Customers retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving customers: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve customers: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get customer details
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $vendor = Auth::user();

            // Ensure user is a vendor
            if ($vendor->role !== 'vendor') {
                return $this->error([], 'Unauthorized. Only vendors can access customer data.', 403);
            }

            // Find the customer
            $customer = User::find($id);

            if (! $customer) {
                return $this->error([], 'Customer not found', 404);
            }

            // Verify this customer has appointments with this vendor
            $hasAppointments = Appointment::where('user_id', $vendor->id)
                ->where('client_id', $customer->id)
                ->exists();

            if (! $hasAppointments) {
                return $this->error([], 'Unauthorized. This customer is not associated with your business.', 403);
            }

            // Get detailed customer information
            // Count total appointments
            $appointments = Appointment::where('user_id', $vendor->id)
                ->where('client_id', $customer->id)
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();

            $totalVisits = $appointments->count();
            $lastAppointment = $appointments->first();

            // Format last visit date
            $lastVisitDate = null;
            if ($lastAppointment) {
                $appointmentDate = Carbon::parse($lastAppointment->date);
                $now = Carbon::now();

                if ($appointmentDate->isToday()) {
                    $lastVisitDate = 'Today';
                } elseif ($appointmentDate->isYesterday()) {
                    $lastVisitDate = 'Yesterday';
                } elseif ($appointmentDate->diffInDays($now) < 7) {
                    $lastVisitDate = $appointmentDate->diffInDays($now).' days ago';
                } else {
                    $lastVisitDate = $appointmentDate->format('M d, Y');
                }
            }

            // Add custom fields to customer
            $customer->totalVisits = $totalVisits;
            $customer->lastVisit = $lastVisitDate;
            $customer->lastAppointmentDate = $lastAppointment ? $lastAppointment->date : null;

            // Return customer details with CustomerResource
            return $this->success(
                new CustomerResource($customer),
                'Customer details retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving customer details: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve customer details: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get customer appointments
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerAppointments($id)
    {
        try {
            $vendor = Auth::user();

            // Ensure user is a vendor
            if ($vendor->role !== 'vendor') {
                return $this->error([], 'Unauthorized. Only vendors can access customer data.', 403);
            }

            // Find the customer
            $customer = User::find($id);

            if (! $customer) {
                return $this->error([], 'Customer not found', 404);
            }

            // Get appointments for this customer with this vendor
            $appointments = Appointment::with(['service', 'payment'])
                ->where('user_id', $vendor->id)
                ->where('client_id', $customer->id)
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();

            // Return appointments using CustomerAppointmentResource
            return $this->success(
                CustomerAppointmentResource::collection($appointments),
                'Customer appointments retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving customer appointments: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve customer appointments: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get customer statistics
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerStats($id)
    {
        try {
            $vendor = Auth::user();

            // Ensure user is a vendor
            if ($vendor->role !== 'vendor') {
                return $this->error([], 'Unauthorized. Only vendors can access customer data.', 403);
            }

            // Find the customer
            $customer = User::find($id);

            if (! $customer) {
                return $this->error([], 'Customer not found', 404);
            }

            // Get appointments for this customer with this vendor
            $appointments = Appointment::with(['payment', 'service'])
                ->where('user_id', $vendor->id)
                ->where('client_id', $customer->id)
                ->get();

            // Calculate statistics
            $totalAppointments = $appointments->count();
            $completedAppointments = $appointments->where('status', Appointment::STATUS_COMPLETED)->count();
            $cancelledAppointments = $appointments->where('status', Appointment::STATUS_CANCELLED)->count();

            // Calculate total spend
            $totalSpend = $appointments->sum(function ($appointment) {
                return $appointment->payment ? (float) $appointment->payment->vendor_earnings : 0;
            });

            // Calculate average spend per visit
            $averageSpend = $totalAppointments > 0 ? $totalSpend / $totalAppointments : 0;

            // Get most frequent service
            $serviceFrequency = [];
            foreach ($appointments as $appointment) {
                if ($appointment->service) {
                    $serviceId = $appointment->service->id;
                    if (! isset($serviceFrequency[$serviceId])) {
                        $serviceFrequency[$serviceId] = [
                            'count' => 0,
                            'name' => $appointment->service->name,
                        ];
                    }
                    $serviceFrequency[$serviceId]['count']++;
                }
            }

            $mostFrequentService = null;
            $maxCount = 0;
            foreach ($serviceFrequency as $serviceId => $info) {
                if ($info['count'] > $maxCount) {
                    $maxCount = $info['count'];
                    $mostFrequentService = $info['name'];
                }
            }

            // First and last visit dates
            $firstVisit = $appointments->sortBy('date')->first();
            $lastVisit = $appointments->sortByDesc('date')->first();

            $firstVisitDate = $firstVisit ? Carbon::parse($firstVisit->date)->format('M d, Y') : null;
            $lastVisitDate = $lastVisit ? Carbon::parse($lastVisit->date)->format('M d, Y') : null;

            // Calculate loyalty period
            $loyaltyPeriod = null;
            if ($firstVisit && $lastVisit) {
                $firstDate = Carbon::parse($firstVisit->date);
                $lastDate = Carbon::parse($lastVisit->date);
                $diffInMonths = $firstDate->diffInMonths($lastDate);

                if ($diffInMonths < 1) {
                    $loyaltyPeriod = 'New Client';
                } elseif ($diffInMonths < 6) {
                    $loyaltyPeriod = 'Regular Client';
                } elseif ($diffInMonths < 12) {
                    $loyaltyPeriod = 'Loyal Client';
                } else {
                    $loyaltyPeriod = 'Long-term Client';
                }
            }

            // Calculate no-show and cancellation rates
            $noShowRate = $totalAppointments > 0
                ? ($appointments->where('status', 'no_show')->count() / $totalAppointments) * 100
                : 0;

            $cancellationRate = $totalAppointments > 0
                ? ($cancelledAppointments / $totalAppointments) * 100
                : 0;

            // Calculate rebook rate (percentage of times customer comes back)
            $rebookRate = $totalAppointments > 1
                ? (($totalAppointments - 1) / $totalAppointments) * 100
                : 0;

            // Prepare stats response
            $stats = [
                'total_appointments' => $totalAppointments,
                'completed_appointments' => $completedAppointments,
                'cancelled_appointments' => $cancelledAppointments,
                'total_spend' => $totalSpend,
                'average_spend' => $averageSpend,
                'most_frequent_service' => $mostFrequentService,
                'first_visit' => $firstVisitDate,
                'last_visit' => $lastVisitDate,
                'loyalty_period' => $loyaltyPeriod,
                'no_show_rate' => round($noShowRate, 2),
                'cancellation_rate' => round($cancellationRate, 2),
                'rebook_rate' => round($rebookRate, 2),
            ];

            // Return stats with the CustomerStatsResource
            return $this->success(
                new CustomerStatsResource($stats),
                'Customer statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving customer statistics: '.$e->getMessage());

            return $this->error([], 'Failed to retrieve customer statistics: '.$e->getMessage(), 500);
        }
    }
}
