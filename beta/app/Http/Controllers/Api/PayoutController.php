<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\PayoutRequest;
use App\Models\Payment;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PayoutController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all bank accounts for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBankAccounts()
    {
        try {
            $userId = Auth::id();
            $bankAccounts = BankAccount::where('user_id', $userId)->get();

            return $this->success($bankAccounts, 'Bank accounts retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error fetching bank accounts: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve bank accounts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add a new bank account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addBankAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_holder_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'ifsc_code' => 'required|string|max:20',
            'account_type' => 'required|string|in:savings,current',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();

            // If this is the first account, set it as default
            $isDefault = BankAccount::where('user_id', $userId)->count() === 0;

            $bankAccount = BankAccount::create([
                'user_id' => $userId,
                'account_holder_name' => $request->account_holder_name,
                'account_number' => $request->account_number,
                'bank_name' => $request->bank_name,
                'ifsc_code' => $request->ifsc_code,
                'account_type' => $request->account_type,
                'is_default' => $isDefault,
            ]);

            return $this->success($bankAccount, 'Bank account added successfully.', 201);
        } catch (\Exception $e) {
            Log::error('Error adding bank account: ' . $e->getMessage());
            return $this->error([], 'Failed to add bank account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing bank account
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBankAccount(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'account_holder_name' => 'sometimes|string|max:255',
            'account_number' => 'sometimes|string|max:255',
            'bank_name' => 'sometimes|string|max:255',
            'ifsc_code' => 'sometimes|string|max:20',
            'account_type' => 'sometimes|string|in:savings,current',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $bankAccount = BankAccount::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$bankAccount) {
                return $this->error([], 'Bank account not found or you do not have permission to update it.', 404);
            }

            $bankAccount->update($request->only([
                'account_holder_name',
                'account_number',
                'bank_name',
                'ifsc_code',
                'account_type',
            ]));

            return $this->success($bankAccount, 'Bank account updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating bank account: ' . $e->getMessage());
            return $this->error([], 'Failed to update bank account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a bank account
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBankAccount($id)
    {
        try {
            $userId = Auth::id();
            $bankAccount = BankAccount::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$bankAccount) {
                return $this->error([], 'Bank account not found or you do not have permission to delete it.', 404);
            }

            // Check if this is the default account and there are other accounts
            if ($bankAccount->is_default) {
                $otherAccounts = BankAccount::where('user_id', $userId)
                    ->where('id', '!=', $id)
                    ->first();

                if ($otherAccounts) {
                    return $this->error([], 'Cannot delete the default bank account. Please set another account as default first.', 422);
                }
            }

            // Check if there are pending payout requests using this account
            $pendingRequests = PayoutRequest::where('bank_account_id', $id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if ($pendingRequests) {
                return $this->error([], 'Cannot delete bank account with pending payout requests.', 422);
            }

            $bankAccount->delete();

            return $this->success(null, 'Bank account deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting bank account: ' . $e->getMessage());
            return $this->error([], 'Failed to delete bank account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Set a bank account as default
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setDefaultBankAccount($id)
    {
        try {
            $userId = Auth::id();
            $bankAccount = BankAccount::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$bankAccount) {
                return $this->error([], 'Bank account not found or you do not have permission to update it.', 404);
            }

            // Start a transaction
            DB::beginTransaction();

            // Reset all bank accounts to non-default
            BankAccount::where('user_id', $userId)
                ->update(['is_default' => false]);

            // Set the selected bank account as default
            $bankAccount->update(['is_default' => true]);

            // Commit the transaction
            DB::commit();

            return $this->success($bankAccount, 'Bank account set as default successfully.');
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
            Log::error('Error setting default bank account: ' . $e->getMessage());
            return $this->error([], 'Failed to set default bank account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all payout requests for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayoutRequests()
    {
        try {
            $userId = Auth::id();
            $payoutRequests = PayoutRequest::with('bankAccount')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $payoutRequests->each(function ($request) {
                $request->formatted_amount = '₹' . number_format($request->amount, 2);
            });

            return $this->success($payoutRequests, 'Payout requests retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error fetching payout requests: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve payout requests: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new payout request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayoutRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();

            // Get bank account and check if it belongs to the user
            $bankAccount = BankAccount::where('id', $request->bank_account_id)
                ->where('user_id', $userId)
                ->first();

            if (!$bankAccount) {
                return $this->error([], 'Invalid bank account or you do not have permission to use it.', 422);
            }

            // Get earnings summary to validate the amount
            $earningsSummary = $this->getEarningsSummaryData($userId);

            // Check if amount is within available balance
            if ($request->amount > $earningsSummary['available_balance']) {
                return $this->error([], 'Withdrawal amount exceeds your available balance.', 422);
            }

            // Check if amount meets minimum withdrawal requirement
            if ($request->amount < $earningsSummary['minimum_withdrawal_amount']) {
                return $this->error([], 'Withdrawal amount is less than the minimum required amount.', 422);
            }

            // Create the payout request
            $payoutRequest = PayoutRequest::create([
                'user_id' => $userId,
                'bank_account_id' => $request->bank_account_id,
                'amount' => $request->amount,
                'status' => 'pending',
            ]);

            // Add formatted amount
            $payoutRequest->formatted_amount = '₹' . number_format($payoutRequest->amount, 2);

            return $this->success($payoutRequest, 'Payout request created successfully.', 201);
        } catch (\Exception $e) {
            Log::error('Error creating payout request: ' . $e->getMessage());
            return $this->error([], 'Failed to create payout request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a pending payout request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelPayoutRequest($id)
    {
        try {
            $userId = Auth::id();
            $payoutRequest = PayoutRequest::where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$payoutRequest) {
                return $this->error([], 'Payout request not found, already processed, or you do not have permission to cancel it.', 404);
            }

            $payoutRequest->update([
                'status' => 'cancelled',
                'cancellation_date' => now(),
            ]);

            return $this->success($payoutRequest, 'Payout request cancelled successfully.');
        } catch (\Exception $e) {
            Log::error('Error cancelling payout request: ' . $e->getMessage());
            return $this->error([], 'Failed to cancel payout request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get earnings summary for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEarningsSummary()
    {
        try {
            $userId = Auth::id();
            $earningsSummary = $this->getEarningsSummaryData($userId);

            return $this->success($earningsSummary, 'Earnings summary retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Error fetching earnings summary: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve earnings summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get earnings summary data (helper method)
     *
     * @param int $userId
     * @return array
     */
    private function getEarningsSummaryData($userId)
    {
        // Get total earnings (all successful payments)
        $totalEarnings = Payment::where('provider_id', $userId)
            ->where('status', Payment::STATUS_PAID)
            ->sum('vendor_earnings');

        // Get total amount already withdrawn
        $withdrawnAmount = PayoutRequest::where('user_id', $userId)
            ->whereIn('status', ['completed'])
            ->sum('amount');

        // Get pending withdrawal requests amount
        $pendingAmount = PayoutRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('amount');

        // Get processing withdrawal requests amount
        $processingAmount = PayoutRequest::where('user_id', $userId)
            ->where('status', 'processing')
            ->sum('amount');

        // Calculate available balance
        $availableBalance = $totalEarnings - $withdrawnAmount - $pendingAmount - $processingAmount;

        // Minimum withdrawal amount (can be configured in settings)
        $minimumWithdrawalAmount = config('app.minimum_withdrawal_amount', 500);

        return [
            'total_earnings' => $totalEarnings,
            'formatted_total_earnings' => '₹' . number_format($totalEarnings, 2),
            'available_balance' => $availableBalance,
            'formatted_available_balance' => '₹' . number_format($availableBalance, 2),
            'pending_amount' => $pendingAmount,
            'formatted_pending_amount' => '₹' . number_format($pendingAmount, 2),
            'processing_amount' => $processingAmount,
            'formatted_processing_amount' => '₹' . number_format($processingAmount, 2),
            'withdrawn_amount' => $withdrawnAmount,
            'formatted_withdrawn_amount' => '₹' . number_format($withdrawnAmount, 2),
            'minimum_withdrawal_amount' => $minimumWithdrawalAmount,
            'formatted_minimum_withdrawal_amount' => '₹' . number_format($minimumWithdrawalAmount, 2),
        ];
    }
}
