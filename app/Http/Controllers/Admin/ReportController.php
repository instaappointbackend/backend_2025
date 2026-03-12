<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\PayoutRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Display the main reports dashboard
     */
    public function index()
    {
        // Get counts for dashboard cards
        $totalUsers = User::count();
        $totalAppointments = Appointment::count();
        $totalPayouts = PayoutRequest::count();
        $totalRevenue = Payment::where('status', Payment::STATUS_PAID)->sum('amount');

        // Get recent data for quick views
        $recentAppointments = Appointment::with(['client', 'user', 'service'])
            ->latest()
            ->take(5)
            ->get();

        $recentPayouts = PayoutRequest::with(['user', 'bankAccount'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.reports.index', compact(
            'totalUsers',
            'totalAppointments',
            'totalPayouts',
            'totalRevenue',
            'recentAppointments',
            'recentPayouts'
        ));
    }

    /**
     * Display appointment reports
     */
    public function appointments(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // Get appointments within date range
        $appointments = Appointment::with(['client', 'user', 'service'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->input('status'));
            })
            ->latest()
            ->paginate(15);

        // Calculate statistics
        $totalAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Appointment::STATUS_COMPLETED)
            ->count();
        $cancelledAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Appointment::STATUS_CANCELLED)
            ->count();
        $pendingAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Appointment::STATUS_PENDING)
            ->count();

        // Get statistics for chart
        $appointmentStats = $this->getAppointmentChartData($startDate, $endDate);

        return view('admin.reports.appointments', compact(
            'appointments',
            'totalAppointments',
            'completedAppointments',
            'cancelledAppointments',
            'pendingAppointments',
            'appointmentStats',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display user reports
     */
    public function users(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // Get users within date range
        $users = User::whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('role'), function ($query) use ($request) {
                return $query->where('role', $request->input('role'));
            })
            ->latest()
            ->paginate(15);

        // Calculate statistics
        $totalUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $vendorsCount = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('role', 'vendor')
            ->count();
        $customersCount = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('role', 'customer')
            ->count();
        $kycCompletedCount = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('is_kyc_completed', true)
            ->count();

        // Get statistics for chart
        $userStats = $this->getUserChartData($startDate, $endDate);

        // Business categories analytics
        $businessCategories = DB::table('users')
            ->join('business_categories', 'users.business_category_id', '=', 'business_categories.id')
            ->select('business_categories.name', DB::raw('count(*) as total'))
            ->whereBetween('users.created_at', [$startDate, $endDate])
            ->where('users.role', 'vendor')
            ->groupBy('business_categories.name')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        return view('admin.reports.users', compact(
            'users',
            'totalUsers',
            'vendorsCount',
            'customersCount',
            'kycCompletedCount',
            'userStats',
            'businessCategories',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display revenue reports
     */
    public function revenue(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // Get payments within date range
        $payments = Payment::with(['user', 'provider', 'appointment'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->input('status'));
            })
            ->latest()
            ->paginate(15);

        // Calculate statistics
        $totalRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');
        $platformRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('admin_earnings');
        $vendorRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('vendor_earnings');
        $totalTransactions = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->count();

        // Get statistics for chart
        $revenueStats = $this->getRevenueChartData($startDate, $endDate);

        // Payment methods analytics
        $paymentMethods = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();

        return view('admin.reports.revenue', compact(
            'payments',
            'totalRevenue',
            'platformRevenue',
            'vendorRevenue',
            'totalTransactions',
            'revenueStats',
            'paymentMethods',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display payout reports
     */
    public function payouts(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // Get payouts within date range
        $payouts = PayoutRequest::with(['user', 'bankAccount'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->input('status'));
            })
            ->latest()
            ->paginate(15);

        // Calculate statistics
        $totalPayouts = PayoutRequest::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        $completedPayouts = PayoutRequest::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', PayoutRequest::STATUS_COMPLETED)
            ->sum('amount');
        $pendingPayouts = PayoutRequest::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', PayoutRequest::STATUS_PENDING)
            ->sum('amount');
        $payoutRequests = PayoutRequest::whereBetween('created_at', [$startDate, $endDate])->count();

        // Get statistics for chart
        $payoutStats = $this->getPayoutChartData($startDate, $endDate);

        // Top vendors by payout amount
        $topVendors = PayoutRequest::join('users', 'payout_requests.user_id', '=', 'users.id')
            ->whereBetween('payout_requests.created_at', [$startDate, $endDate])
            ->where('payout_requests.status', PayoutRequest::STATUS_COMPLETED)
            ->select('users.id', 'users.name', DB::raw('sum(payout_requests.amount) as total'))
            ->groupBy('users.id', 'users.name')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        return view('admin.reports.payouts', compact(
            'payouts',
            'totalPayouts',
            'completedPayouts',
            'pendingPayouts',
            'payoutRequests',
            'payoutStats',
            'topVendors',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get appointment chart data
     */
    public function getAppointmentChartData($startDate = null, $endDate = null)
    {
        if (! $startDate) {
            $startDate = Carbon::now()->subDays(30);
        }

        if (! $endDate) {
            $endDate = Carbon::now();
        }

        $dateRange = $this->getDatesInRange($startDate, $endDate);

        $appointments = Appointment::whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(date) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartData = [];

        foreach ($dateRange as $date) {
            $formattedDate = $date->format('Y-m-d');
            $chartData[] = [
                'date' => $date->format('M d'),
                'total' => $appointments->has($formattedDate) ? $appointments[$formattedDate]->total : 0,
                'completed' => $appointments->has($formattedDate) ? $appointments[$formattedDate]->completed : 0,
                'cancelled' => $appointments->has($formattedDate) ? $appointments[$formattedDate]->cancelled : 0,
            ];
        }

        return $chartData;
    }

    /**
     * Get user chart data
     */
    public function getUserChartData($startDate = null, $endDate = null)
    {
        if (! $startDate) {
            $startDate = Carbon::now()->subDays(30);
        }

        if (! $endDate) {
            $endDate = Carbon::now();
        }

        $dateRange = $this->getDatesInRange($startDate, $endDate);

        $users = User::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN role = "vendor" THEN 1 ELSE 0 END) as vendors'),
                DB::raw('SUM(CASE WHEN role = "customer" THEN 1 ELSE 0 END) as customers')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartData = [];

        foreach ($dateRange as $date) {
            $formattedDate = $date->format('Y-m-d');
            $chartData[] = [
                'date' => $date->format('M d'),
                'total' => $users->has($formattedDate) ? $users[$formattedDate]->total : 0,
                'vendors' => $users->has($formattedDate) ? $users[$formattedDate]->vendors : 0,
                'customers' => $users->has($formattedDate) ? $users[$formattedDate]->customers : 0,
            ];
        }

        return $chartData;
    }

    /**
     * Get revenue chart data
     */
    public function getRevenueChartData($startDate = null, $endDate = null)
    {
        if (! $startDate) {
            $startDate = Carbon::now()->subDays(30);
        }

        if (! $endDate) {
            $endDate = Carbon::now();
        }

        $dateRange = $this->getDatesInRange($startDate, $endDate);

        $revenues = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('SUM(admin_earnings) as admin'),
                DB::raw('SUM(vendor_earnings) as vendor')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartData = [];

        foreach ($dateRange as $date) {
            $formattedDate = $date->format('Y-m-d');
            $chartData[] = [
                'date' => $date->format('M d'),
                'total' => $revenues->has($formattedDate) ? $revenues[$formattedDate]->total : 0,
                'admin' => $revenues->has($formattedDate) ? $revenues[$formattedDate]->admin : 0,
                'vendor' => $revenues->has($formattedDate) ? $revenues[$formattedDate]->vendor : 0,
            ];
        }

        return $chartData;
    }

    /**
     * Get payout chart data
     */
    public function getPayoutChartData($startDate = null, $endDate = null)
    {
        if (! $startDate) {
            $startDate = Carbon::now()->subDays(30);
        }

        if (! $endDate) {
            $endDate = Carbon::now();
        }

        $dateRange = $this->getDatesInRange($startDate, $endDate);

        $payouts = PayoutRequest::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartData = [];

        foreach ($dateRange as $date) {
            $formattedDate = $date->format('Y-m-d');
            $chartData[] = [
                'date' => $date->format('M d'),
                'total' => $payouts->has($formattedDate) ? $payouts[$formattedDate]->total : 0,
                'completed' => $payouts->has($formattedDate) ? $payouts[$formattedDate]->completed : 0,
                'pending' => $payouts->has($formattedDate) ? $payouts[$formattedDate]->pending : 0,
            ];
        }

        return $chartData;
    }

    /**
     * Export appointments data
     */
    public function exportAppointments(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $appointments = Appointment::with(['client', 'user', 'service'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->input('status'));
            })
            ->latest()
            ->get();

        $csvData = [];
        $csvData[] = [
            'ID',
            'Service',
            'Customer',
            'Vendor',
            'Date',
            'Start Time',
            'End Time',
            'Status',
            'Payment Status',
            'Payment Amount',
            'Created At',
        ];

        foreach ($appointments as $appointment) {
            $csvData[] = [
                $appointment->id,
                $appointment->service ? $appointment->service->name : ($appointment->comboService ? $appointment->comboService->name : ''),
                $appointment->client ? $appointment->client->name : '',
                $appointment->user ? $appointment->user->name : '',
                $appointment->date ? $appointment->date->format('Y-m-d') : '',
                $appointment->start_time,
                $appointment->end_time,
                $appointment->status,
                $appointment->payment_status,
                $appointment->payment_amount,
                $appointment->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'appointments_report_'.date('Y-m-d').'.csv';

        return $this->generateCsv($csvData, $filename);
    }

    /**
     * Export users data
     */
    public function exportUsers(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $users = User::whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('role'), function ($query) use ($request) {
                return $query->where('role', $request->input('role'));
            })
            ->latest()
            ->get();

        $csvData = [];
        $csvData[] = [
            'ID',
            'Name',
            'Email',
            'Mobile',
            'Role',
            'Status',
            'KYC Status',
            'Created At',
        ];

        foreach ($users as $user) {
            $csvData[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->mobile,
                $user->role,
                $user->status ? 'Active' : 'Inactive',
                $user->is_kyc_completed ? 'Completed' : 'Pending',
                $user->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'users_report_'.date('Y-m-d').'.csv';

        return $this->generateCsv($csvData, $filename);
    }

    /**
     * Export revenue data
     */
    public function exportRevenue(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $payments = Payment::with(['user', 'provider', 'appointment'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->input('status'));
            })
            ->latest()
            ->get();

        $csvData = [];
        $csvData[] = [
            'ID',
            'Transaction ID',
            'Customer',
            'Vendor',
            'Amount',
            'Admin Earnings',
            'Vendor Earnings',
            'Payment Method',
            'Status',
            'Created At',
        ];

        foreach ($payments as $payment) {
            $csvData[] = [
                $payment->id,
                $payment->transaction_id,
                $payment->user ? $payment->user->name : '',
                $payment->provider ? $payment->provider->name : '',
                $payment->amount,
                $payment->admin_earnings,
                $payment->vendor_earnings,
                $payment->payment_method,
                $payment->status,
                $payment->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'revenue_report_'.date('Y-m-d').'.csv';

        return $this->generateCsv($csvData, $filename);
    }

    /**
     * Export payouts data
     */
    public function exportPayouts(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $payouts = PayoutRequest::with(['user', 'bankAccount'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->input('status'));
            })
            ->latest()
            ->get();

        $csvData = [];
        $csvData[] = [
            'ID',
            'Vendor',
            'Bank Account',
            'Amount',
            'Status',
            'Transaction ID',
            'Transaction Date',
            'Created At',
        ];

        foreach ($payouts as $payout) {
            $bankAccountDetails = $payout->bankAccount ?
                $payout->bankAccount->bank_name.' - '.
                $payout->bankAccount->account_number : '';

            $csvData[] = [
                $payout->id,
                $payout->user ? $payout->user->name : '',
                $bankAccountDetails,
                $payout->amount,
                $payout->status,
                $payout->transaction_id,
                $payout->transaction_date ? $payout->transaction_date->format('Y-m-d') : '',
                $payout->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'payouts_report_'.date('Y-m-d').'.csv';

        return $this->generateCsv($csvData, $filename);
    }

    /**
     * Helper method to generate a date range for charts
     */
    private function getDatesInRange($startDate, $endDate)
    {
        $dateRange = [];
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate->lte($endDate)) {
            $dateRange[] = $currentDate->copy();
            $currentDate->addDay();
        }

        return $dateRange;
    }

    /**
     * Helper method to generate CSV file
     */
    private function generateCsv($data, $filename)
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return Response::make($content, 200, $headers);
    }
}
