<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of payout requests.
     */
    public function index(Request $request)
    {
        $query = PayoutRequest::with(['user', 'bankAccount']);

        // Filter by status
        if ($request->filled('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date.' 00:00:00', $request->end_date.' 23:59:59']);
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

        // Filter by provider (user)
        if ($request->filled('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Default sorting
        $query->orderBy('created_at', 'desc');

        $payoutRequests = $query->paginate(15)->withQueryString();

        // Get all providers for filters
        $providers = User::where('role', 'vendor')->get();

        // Get payout statuses for filters
        $statuses = [
            PayoutRequest::STATUS_PENDING => 'Pending',
            PayoutRequest::STATUS_PROCESSING => 'Processing',
            PayoutRequest::STATUS_COMPLETED => 'Completed',
            PayoutRequest::STATUS_REJECTED => 'Rejected',
            PayoutRequest::STATUS_CANCELLED => 'Cancelled',
        ];

        return view('admin.payouts.index', compact(
            'payoutRequests',
            'providers',
            'statuses'
        ));
    }

    /**
     * Display the payout request details.
     */
    public function show(PayoutRequest $payoutRequest)
    {
        $payoutRequest->load(['user', 'bankAccount']);

        // Get user's earnings summary
        $earningsSummary = $this->getProviderEarningsSummary($payoutRequest->user_id);

        return view('admin.payouts.show', compact('payoutRequest', 'earningsSummary'));
    }

    /**
     * Update the payout request status.
     */
    public function updateStatus(Request $request, PayoutRequest $payoutRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,rejected',
            'transaction_id' => 'required_if:status,completed',
            'rejection_reason' => 'required_if:status,rejected',
            'transaction_date' => 'required_if:status,completed|date',
            'notes' => 'nullable|string',
        ]);

        // Begin database transaction
        DB::beginTransaction();

        try {
            // Store old status for notification comparison
            $oldStatus = $payoutRequest->status;

            // Update payout request details
            $updateData = [
                'status' => $validated['status'],
            ];

            // Set additional fields based on status
            if ($validated['status'] === PayoutRequest::STATUS_COMPLETED) {
                $updateData['transaction_id'] = $validated['transaction_id'];
                $updateData['transaction_date'] = Carbon::parse($validated['transaction_date']);
            } elseif ($validated['status'] === PayoutRequest::STATUS_REJECTED) {
                $updateData['rejection_reason'] = $validated['rejection_reason'];
            }

            // Apply updates to payout request
            $payoutRequest->update($updateData);

            // Send notifications if status changed
            if ($oldStatus !== $validated['status']) {
                $this->sendPayoutStatusNotifications($payoutRequest, $oldStatus, $validated['status'], $validated);
            }

            // Commit transaction
            DB::commit();

            return redirect()->route('admin.payouts.show', $payoutRequest->id)
                ->with('success', 'Payout request status updated successfully.');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error updating payout request: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to update payout request: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process batch payouts.
     */
    public function batchProcess(Request $request)
    {
        $validated = $request->validate([
            'payout_ids' => 'required|array',
            'payout_ids.*' => 'exists:payout_requests,id',
            'action' => 'required|in:mark_processing,mark_completed,mark_rejected',
            'transaction_details' => 'nullable|array',
            'transaction_details.*' => 'nullable|string',
            'transaction_prefix' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'rejection_reason' => 'required_if:action,mark_rejected|string',
        ]);

        // Begin database transaction
        DB::beginTransaction();

        try {
            $processed = 0;

            foreach ($validated['payout_ids'] as $index => $id) {
                $payoutRequest = PayoutRequest::find($id);

                if (! $payoutRequest) {
                    continue;
                }

                // Store old status for notification
                $oldStatus = $payoutRequest->status;
                $updateData = [];

                // Apply batch action
                switch ($validated['action']) {
                    case 'mark_processing':
                        $updateData['status'] = PayoutRequest::STATUS_PROCESSING;
                        break;

                    case 'mark_completed':
                        $updateData['status'] = PayoutRequest::STATUS_COMPLETED;

                        // Use transaction details if provided for this specific payout
                        if (isset($validated['transaction_details'][$id])) {
                            $updateData['transaction_id'] = $validated['transaction_details'][$id];
                        } else {
                            $updateData['transaction_id'] = 'BATCH_TXN_'.uniqid().'_'.$id;
                        }

                        // Set transaction date if provided
                        if (isset($validated['transaction_date'])) {
                            $updateData['transaction_date'] = Carbon::parse($validated['transaction_date']);
                        } else {
                            $updateData['transaction_date'] = now();
                        }
                        break;

                    case 'mark_rejected':
                        $updateData['status'] = PayoutRequest::STATUS_REJECTED;
                        $updateData['rejection_reason'] = $validated['rejection_reason'];
                        break;
                }

                // Update payout request
                $payoutRequest->update($updateData);

                // Send notification about status change
                $this->sendPayoutStatusNotifications($payoutRequest, $oldStatus, $updateData['status'], $validated);

                $processed++;
            }

            // Commit transaction
            DB::commit();

            return redirect()->route('admin.payouts.index')
                ->with('success', "Successfully processed $processed payout requests.");
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error processing batch payouts: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to process batch payouts: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Export payout requests data.
     */
    public function export(Request $request)
    {
        $query = PayoutRequest::with(['user', 'bankAccount']);

        // Apply filters (same as index method)
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date.' 00:00:00', $request->end_date.' 23:59:59']);
        } elseif ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date.' 00:00:00');
        } elseif ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date.' 23:59:59');
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Get all payout requests
        $payoutRequests = $query->get();

        // Create CSV content
        $csvData = [];

        // Add CSV headers
        $csvData[] = [
            'ID',
            'Request Date',
            'Provider',
            'Bank Account',
            'Amount',
            'Status',
            'Transaction ID',
            'Transaction Date',
            'Rejection Reason',
        ];

        // Add payout request rows
        foreach ($payoutRequests as $payout) {
            $bankDetails = $payout->bankAccount ?
                ($payout->bankAccount->bank_name.' - '.
                    $payout->bankAccount->account_number) : 'N/A';

            $csvData[] = [
                $payout->id,
                $payout->created_at->format('Y-m-d H:i:s'),
                $payout->user ? $payout->user->name : 'N/A',
                $bankDetails,
                $payout->amount,
                $payout->getHumanStatusAttribute(),
                $payout->transaction_id ?? 'N/A',
                $payout->transaction_date ? $payout->transaction_date->format('Y-m-d H:i:s') : 'N/A',
                $payout->rejection_reason ?? 'N/A',
            ];
        }

        // Create unique filename
        $filename = 'payout_requests_export_'.date('Y-m-d_H-i-s').'.csv';
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
     * Display payout reports.
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Convert to Carbon instances
        $startDateCarbon = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();

        // Get payout statistics
        $totalRequests = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])->count();
        $totalAmount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_COMPLETED)
            ->sum('amount');

        // Get payout counts by status
        $pendingCount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_PENDING)
            ->count();

        $processingCount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_PROCESSING)
            ->count();

        $completedCount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_COMPLETED)
            ->count();

        $rejectedCount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_REJECTED)
            ->count();

        $cancelledCount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_CANCELLED)
            ->count();

        // Get pending payout amounts
        $pendingAmount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_PENDING)
            ->sum('amount');

        $processingAmount = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_PROCESSING)
            ->sum('amount');

        // Get daily payout statistics for chart
        $dailyStats = [];
        $currentDate = clone $startDateCarbon;

        while ($currentDate <= $endDateCarbon) {
            $dayStart = (clone $currentDate)->startOfDay();
            $dayEnd = (clone $currentDate)->endOfDay();

            $dayAmount = PayoutRequest::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', PayoutRequest::STATUS_COMPLETED)
                ->sum('amount');

            $dailyStats[] = [
                'date' => $currentDate->format('Y-m-d'),
                'formatted_date' => $currentDate->format('M d'),
                'amount' => $dayAmount,
                'formatted_amount' => '₹'.number_format($dayAmount, 2),
            ];

            $currentDate->addDay();
        }

        // Get top providers by payout amount
        $topProviders = PayoutRequest::whereBetween('created_at', [$startDateCarbon, $endDateCarbon])
            ->where('status', PayoutRequest::STATUS_COMPLETED)
            ->select('user_id', DB::raw('sum(amount) as total_amount'), DB::raw('count(*) as request_count'))
            ->with('user')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->take(10)
            ->get();

        // Get monthly payout amounts for chart
        $monthlyStats = [];
        $startMonth = (clone $startDateCarbon)->startOfMonth();
        $endMonth = (clone $endDateCarbon)->startOfMonth();

        while ($startMonth->lte($endMonth)) {
            $nextMonth = (clone $startMonth)->addMonth();

            $monthAmount = PayoutRequest::whereBetween('created_at', [$startMonth, $nextMonth])
                ->where('status', PayoutRequest::STATUS_COMPLETED)
                ->sum('amount');

            $monthlyStats[] = [
                'month' => $startMonth->format('M Y'),
                'amount' => $monthAmount,
                'formatted_amount' => '₹'.number_format($monthAmount, 2),
            ];

            $startMonth = $nextMonth;
        }

        return view('admin.payouts.reports', compact(
            'startDate',
            'endDate',
            'totalRequests',
            'totalAmount',
            'pendingCount',
            'processingCount',
            'completedCount',
            'rejectedCount',
            'cancelledCount',
            'pendingAmount',
            'processingAmount',
            'dailyStats',
            'monthlyStats',
            'topProviders'
        ));
    }

    /**
     * Display provider earnings and payout history.
     */
    public function providerEarnings(Request $request, $userId)
    {
        $provider = User::findOrFail($userId);

        // Get earnings summary
        $earningsSummary = $this->getProviderEarningsSummary($userId);

        // Get payout history
        $payoutHistory = PayoutRequest::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get payment history
        $paymentHistory = Payment::where('provider_id', $userId)
            ->where('status', Payment::STATUS_PAID)
            ->with(['appointment', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Get bank accounts
        $bankAccounts = BankAccount::where('user_id', $userId)->get();

        return view('admin.payouts.provider_earnings', compact(
            'provider',
            'earningsSummary',
            'payoutHistory',
            'paymentHistory',
            'bankAccounts'
        ));
    }

    /**
     * Create a manual payout for a provider.
     */
    public function createManualPayout(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:1',
            'transaction_id' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Begin database transaction
        DB::beginTransaction();

        try {
            $userId = $validated['user_id'];

            // Get earnings summary to validate the amount
            $earningsSummary = $this->getProviderEarningsSummary($userId);

            // Check if amount is within available balance
            if ($validated['amount'] > $earningsSummary['available_balance']) {
                return redirect()->back()
                    ->with('error', 'Payout amount exceeds available balance.')
                    ->withInput();
            }

            // Create the payout request with completed status
            $payoutRequest = PayoutRequest::create([
                'user_id' => $userId,
                'bank_account_id' => $validated['bank_account_id'],
                'amount' => $validated['amount'],
                'status' => PayoutRequest::STATUS_COMPLETED,
                'transaction_id' => $validated['transaction_id'],
                'transaction_date' => Carbon::parse($validated['transaction_date']),
            ]);

            // Send notification about the manual payout
            $this->sendManualPayoutNotification($payoutRequest);

            // Commit transaction
            DB::commit();

            return redirect()->route('admin.payouts.provider_earnings', $userId)
                ->with('success', 'Manual payout created successfully.');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            Log::error('Error creating manual payout: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to create manual payout: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get a provider's earnings summary.
     */
    private function getProviderEarningsSummary($userId)
    {
        // Get total earnings (all successful payments)
        $totalEarnings = Payment::where('provider_id', $userId)
            ->where('status', Payment::STATUS_PAID)
            ->sum('vendor_earnings');

        // If vendor_earnings is 0, fallback to booking_price
        if ($totalEarnings == 0) {
            $totalEarnings = Payment::where('provider_id', $userId)
                ->where('status', Payment::STATUS_PAID)
                ->sum('booking_price');
        }

        // Get total amount already withdrawn
        $withdrawnAmount = PayoutRequest::where('user_id', $userId)
            ->whereIn('status', [PayoutRequest::STATUS_COMPLETED])
            ->sum('amount');

        // Get pending withdrawal requests amount
        $pendingAmount = PayoutRequest::where('user_id', $userId)
            ->whereIn('status', [PayoutRequest::STATUS_PENDING, PayoutRequest::STATUS_PROCESSING])
            ->sum('amount');

        // Calculate available balance
        $availableBalance = $totalEarnings - $withdrawnAmount - $pendingAmount;

        // Minimum withdrawal amount (can be configured in settings)
        $minimumWithdrawalAmount = config('app.minimum_withdrawal_amount', 500);

        return [
            'total_earnings' => $totalEarnings,
            'formatted_total_earnings' => '₹'.number_format($totalEarnings, 2),
            'available_balance' => $availableBalance,
            'formatted_available_balance' => '₹'.number_format($availableBalance, 2),
            'pending_amount' => $pendingAmount,
            'formatted_pending_amount' => '₹'.number_format($pendingAmount, 2),
            'withdrawn_amount' => $withdrawnAmount,
            'formatted_withdrawn_amount' => '₹'.number_format($withdrawnAmount, 2),
            'minimum_withdrawal_amount' => $minimumWithdrawalAmount,
            'formatted_minimum_withdrawal_amount' => '₹'.number_format($minimumWithdrawalAmount, 2),
        ];
    }

    /**
     * Send payout status change notifications.
     */
    private function sendPayoutStatusNotifications(PayoutRequest $payoutRequest, $oldStatus, $newStatus, $data = [])
    {
        // Load user relation if not already loaded
        if (! $payoutRequest->relationLoaded('user')) {
            $payoutRequest->load('user');
        }

        // Load bank account relation if not already loaded
        if (! $payoutRequest->relationLoaded('bankAccount')) {
            $payoutRequest->load('bankAccount');
        }

        $provider = $payoutRequest->user;
        if (! $provider) {
            return;
        }

        // Format amount for notifications
        $formattedAmount = '₹'.number_format($payoutRequest->amount, 2);

        // Get bank details
        $bankDetails = $payoutRequest->bankAccount ?
            ($payoutRequest->bankAccount->bank_name.' - '.
                substr($payoutRequest->bankAccount->account_number, -4)) : 'your bank account';

        switch ($newStatus) {
            case PayoutRequest::STATUS_PROCESSING:
                $title = 'Payout Request Processing';
                $body = "Your payout request for {$formattedAmount} is now being processed. The funds will be transferred to {$bankDetails} shortly.";
                $notificationData = [
                    'type' => 'payout_processing',
                    'payout_request_id' => $payoutRequest->id,
                    'amount' => $payoutRequest->amount,
                    'status' => $payoutRequest->status,
                    'screenName' => 'PayoutDetails',
                ];
                break;

            case PayoutRequest::STATUS_COMPLETED:
                $title = 'Payout Completed';
                $body = "Your payout of {$formattedAmount} has been completed. The funds have been transferred to {$bankDetails}.";
                $notificationData = [
                    'type' => 'payout_completed',
                    'payout_request_id' => $payoutRequest->id,
                    'amount' => $payoutRequest->amount,
                    'transaction_id' => $payoutRequest->transaction_id,
                    'transaction_date' => $payoutRequest->transaction_date ? $payoutRequest->transaction_date->format('Y-m-d H:i:s') : null,
                    'status' => $payoutRequest->status,
                    'screenName' => 'PayoutDetails',
                ];
                break;

            case PayoutRequest::STATUS_REJECTED:
                $title = 'Payout Request Rejected';
                $body = "Your payout request for {$formattedAmount} has been rejected. Reason: {$payoutRequest->rejection_reason}";
                $notificationData = [
                    'type' => 'payout_rejected',
                    'payout_request_id' => $payoutRequest->id,
                    'amount' => $payoutRequest->amount,
                    'rejection_reason' => $payoutRequest->rejection_reason,
                    'status' => $payoutRequest->status,
                    'screenName' => 'PayoutDetails',
                ];
                break;

            default:
                return; // Don't send a notification for other status changes
        }

        // Send the notification
        $this->notificationService->sendPushNotification(
            $payoutRequest->user_id,
            $title,
            $body,
            $notificationData
        );
    }

    /**
     * Send notification about manual payout.
     */
    private function sendManualPayoutNotification(PayoutRequest $payoutRequest)
    {
        // Load needed relations if not already loaded
        if (! $payoutRequest->relationLoaded('user') || ! $payoutRequest->relationLoaded('bankAccount')) {
            $payoutRequest->load(['user', 'bankAccount']);
        }

        if (! $payoutRequest->user) {
            return;
        }

        // Format amount for notification
        $formattedAmount = '₹'.number_format($payoutRequest->amount, 2);

        // Get bank details
        $bankDetails = $payoutRequest->bankAccount ?
            ($payoutRequest->bankAccount->bank_name.' - '.
                substr($payoutRequest->bankAccount->account_number, -4)) : 'your bank account';

        $title = 'Payout Processed';
        $body = "A payout of {$formattedAmount} has been processed and transferred to {$bankDetails}.";
        $notificationData = [
            'type' => 'manual_payout_completed',
            'payout_request_id' => $payoutRequest->id,
            'amount' => $payoutRequest->amount,
            'transaction_id' => $payoutRequest->transaction_id,
            'transaction_date' => $payoutRequest->transaction_date ? $payoutRequest->transaction_date->format('Y-m-d H:i:s') : null,
            'status' => $payoutRequest->status,
            'screenName' => 'PayoutDetails',
        ];

        // Send the notification
        $this->notificationService->sendPushNotification(
            $payoutRequest->user_id,
            $title,
            $body,
            $notificationData
        );
    }

    /**
     * Find pending payout requests for vendors
     */
    public function findPendingPayouts()
    {
        // Get all vendors with pending/processing payouts
        $vendors = User::where('role', 'vendor')
            ->whereHas('payoutRequests', function ($query) {
                $query->whereIn('status', [
                    PayoutRequest::STATUS_PENDING,
                    PayoutRequest::STATUS_PROCESSING,
                ]);
            })
            ->withCount(['payoutRequests as pending_payout_count' => function ($query) {
                $query->whereIn('status', [
                    PayoutRequest::STATUS_PENDING,
                    PayoutRequest::STATUS_PROCESSING,
                ]);
            }])
            ->withSum(['payoutRequests as pending_payout_amount' => function ($query) {
                $query->whereIn('status', [
                    PayoutRequest::STATUS_PENDING,
                    PayoutRequest::STATUS_PROCESSING,
                ]);
            }], 'amount')
            ->orderBy('pending_payout_amount', 'desc')
            ->get();

        // Get earnings data for each vendor
        foreach ($vendors as $vendor) {
            $vendor->earnings_data = $this->getProviderEarningsSummary($vendor->id);
        }

        return view('admin.payouts.pending', compact('vendors'));
    }
}
