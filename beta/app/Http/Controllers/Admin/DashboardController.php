<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Refund;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        // Get counts for dashboard
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $totalVendors = User::where('role', 'vendor')->count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalServices = Service::count();

        // Get appointment statistics
        $totalAppointments = Appointment::count();
        $pendingAppointments = Appointment::where('status', Appointment::STATUS_PENDING)->count();
        $confirmedAppointments = Appointment::where('status', Appointment::STATUS_CONFIRMED)->count();
        $completedAppointments = Appointment::where('status', Appointment::STATUS_COMPLETED)->count();
        $cancelledAppointments = Appointment::where('status', Appointment::STATUS_CANCELLED)->count();

        // Get KYC pending count
        $pendingKyc = User::where('is_kyc_completed', false)
                        ->where('role', 'vendor')
                        ->count();

        // Get refund statistics
        $totalRefunds = Refund::count();
        $pendingRefunds = Refund::where('refund_status', Refund::STATUS_PENDING)->count();
        $processedRefunds = Refund::where('refund_status', Refund::STATUS_PROCESSED)->count();
        $totalRefundAmount = Refund::where('refund_status', Refund::STATUS_PROCESSED)->sum('refund_amount');
        $refundSuccessRate = $totalRefunds > 0 ? ($processedRefunds / $totalRefunds) * 100 : 0;

        // Get recent appointments (10 most recent)
        $recentAppointments = Appointment::with(['user', 'client', 'service','comboService'])
                                ->orderBy('created_at', 'desc')
                                ->take(10)
                                ->get();

        // Get recent refunds (5 most recent)
        $recentRefunds = Refund::with(['user', 'provider', 'appointment'])
                              ->orderBy('created_at', 'desc')
                              ->take(5)
                              ->get();

        // Get appointment chart data for the past 7 days
        $appointmentChartData = $this->getAppointmentChartData();

        // Get user registration chart data for the past 6 months
        $userRegistrationChartData = $this->getUserRegistrationChartData();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalVendors',
            'totalCustomers',
            'totalServices',
            'totalAppointments',
            'pendingAppointments',
            'confirmedAppointments',
            'completedAppointments',
            'cancelledAppointments',
            'pendingKyc',
            'totalRefunds',
            'pendingRefunds',
            'processedRefunds',
            'totalRefundAmount',
            'refundSuccessRate',
            'recentAppointments',
            'recentRefunds',
            'appointmentChartData',
            'userRegistrationChartData'
        ));
    }

    /**
     * Get appointment chart data for the past 7 days
     */
    private function getAppointmentChartData()
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $appointments = Appointment::whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(date) as appointment_date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "' . Appointment::STATUS_PENDING . '" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN status = "' . Appointment::STATUS_CONFIRMED . '" THEN 1 ELSE 0 END) as confirmed'),
                DB::raw('SUM(CASE WHEN status = "' . Appointment::STATUS_COMPLETED . '" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "' . Appointment::STATUS_CANCELLED . '" THEN 1 ELSE 0 END) as cancelled')
            )
            ->groupBy('appointment_date')
            ->get();

        // Create a collection with all dates
        $dates = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dateLabel = Carbon::now()->subDays($i)->format('D');
            $dates->put($date, [
                'date' => $date,
                'label' => $dateLabel,
                'total' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0
            ]);
        }

        // Fill with actual data
        foreach ($appointments as $appointment) {
            $date = $appointment->appointment_date;
            if ($dates->has($date)) {
                $dates[$date] = [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('D'),
                    'total' => $appointment->total,
                    'pending' => $appointment->pending,
                    'confirmed' => $appointment->confirmed,
                    'completed' => $appointment->completed,
                    'cancelled' => $appointment->cancelled
                ];
            }
        }

        return $dates->sortBy('date')->values();
    }

    /**
     * Get user registration chart data for the past 6 months
     */
    private function getUserRegistrationChartData()
    {
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $registrations = User::where('role', '!=', 'admin')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN role = "vendor" THEN 1 ELSE 0 END) as vendors'),
                DB::raw('SUM(CASE WHEN role = "customer" THEN 1 ELSE 0 END) as customers')
            )
            ->groupBy('year', 'month')
            ->get();

        // Create a collection with all months
        $months = collect();
        for ($i = 0; $i < 6; $i++) {
            $date = Carbon::now()->subMonths($i);
            $yearMonth = $date->format('Y-m');
            $monthLabel = $date->format('M Y');
            $months->put($yearMonth, [
                'year_month' => $yearMonth,
                'label' => $monthLabel,
                'total' => 0,
                'vendors' => 0,
                'customers' => 0
            ]);
        }

        // Fill with actual data
        foreach ($registrations as $registration) {
            $yearMonth = sprintf('%04d-%02d', $registration->year, $registration->month);
            if ($months->has($yearMonth)) {
                $months[$yearMonth] = [
                    'year_month' => $yearMonth,
                    'label' => Carbon::createFromDate($registration->year, $registration->month, 1)->format('M Y'),
                    'total' => $registration->total,
                    'vendors' => $registration->vendors,
                    'customers' => $registration->customers
                ];
            }
        }

        return $months->sortKeys()->values();
    }
}
