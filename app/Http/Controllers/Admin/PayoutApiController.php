<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayoutApiController extends Controller
{
    /**
     * Get pending payout requests for a vendor.
     */
    public function getPendingPayouts(Request $request)
    {
        try {
            $vendorId = $request->input('vendor_id');

            if (! $vendorId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vendor ID is required',
                ], 400);
            }

            $payouts = PayoutRequest::where('user_id', $vendorId)
                ->whereIn('status', [
                    PayoutRequest::STATUS_PENDING,
                    PayoutRequest::STATUS_PROCESSING,
                ])
                ->select('id', 'amount', 'status', 'created_at', 'bank_account_id')
                ->get();

            // Map each payout to include formatted values
            $mappedPayouts = $payouts->map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'amount' => $payout->amount,
                    'formatted_amount' => '₹'.number_format($payout->amount, 2),
                    'status' => $payout->status,
                    'human_status' => ucfirst($payout->status),
                    'created_at' => $payout->created_at,
                    'formatted_date' => $payout->created_at->format('M d, Y'),
                    'bank_account_id' => $payout->bank_account_id,
                ];
            });

            return response()->json([
                'success' => true,
                'payouts' => $mappedPayouts,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pending payouts: '.$e->getMessage(), [
                'vendor_id' => $request->input('vendor_id'),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error fetching pending payouts. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
