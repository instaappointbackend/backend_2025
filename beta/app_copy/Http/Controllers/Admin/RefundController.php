<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use App\Services\RefundService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RefundController extends Controller
{
    use ApiResponseTrait;

    protected $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Display the refund management page
     */
    public function index(Request $request)
    {
        $query = Refund::with(['appointment', 'payment', 'user', 'provider'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('refund_status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('refund_type', $request->type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('refund_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('provider', function ($providerQuery) use ($search) {
                        $providerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $refunds = $query->paginate(15);

        return view('admin.refunds.index', compact('refunds'));
    }

    /**
     * Get all refunds with pagination and filters (API)
     */
    public function getRefunds(Request $request)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                return $this->error([], 'Unauthorized', 403);
            }

            $query = Refund::with(['appointment', 'payment', 'user', 'provider'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status') && $request->status) {
                $query->where('refund_status', $request->status);
            }

            if ($request->has('type') && $request->type) {
                $query->where('refund_type', $request->type);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('refund_reference', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('provider', function ($providerQuery) use ($search) {
                            $providerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $refunds = $query->paginate(20);

            return $this->success([
                'data' => RefundResource::collection($refunds->items()),
                'pagination' => [
                    'current_page' => $refunds->currentPage(),
                    'last_page' => $refunds->lastPage(),
                    'per_page' => $refunds->perPage(),
                    'total' => $refunds->total(),
                    'from' => $refunds->firstItem(),
                    'to' => $refunds->lastItem(),
                ]
            ], 'Refunds retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving refunds: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve refunds', 500);
        }
    }

    /**
     * Get specific refund details (Web view)
     */
    public function show(Refund $refund)
    {
        $refund->load(['appointment', 'payment', 'user', 'provider']);
        return view('admin.refunds.show', compact('refund'));
    }

    /**
     * Get specific refund details (API)
     */
    public function getRefund($id)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                return $this->error([], 'Unauthorized', 403);
            }

            $refund = Refund::with(['appointment', 'payment', 'user', 'provider'])
                ->findOrFail($id);

            return $this->success(new RefundResource($refund), 'Refund details retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving refund details: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve refund details', 500);
        }
    }

    /**
     * Process a pending refund
     */
    public function processRefund(Request $request, Refund $refund)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                if ($request->expectsJson()) {
                    return $this->error([], 'Unauthorized', 403);
                }
                return redirect()->back()->with('error', 'Unauthorized access');
            }

            if ($refund->refund_status !== Refund::STATUS_PENDING) {
                $message = 'Refund is not in pending status';
                if ($request->expectsJson()) {
                    return $this->error([], $message, 422);
                }
                return redirect()->back()->with('error', $message);
            }

            // Update refund status to processed
            $refund->update([
                'refund_status' => Refund::STATUS_PROCESSED,
                'processed_at' => now(),
                'refund_details' => array_merge($refund->refund_details ?? [], [
                    'processed_by_admin' => $user->id,
                    'processed_by_admin_name' => $user->name,
                    'admin_processed_at' => now()->toIso8601String(),
                    'processing_method' => 'manual_admin_approval'
                ])
            ]);

            Log::info('Refund processed by admin', [
                'refund_id' => $refund->id,
                'admin_id' => $user->id,
                'admin_name' => $user->name
            ]);

            if ($request->expectsJson()) {
                return $this->success(new RefundResource($refund), 'Refund processed successfully');
            }

            return redirect()->route('admin.refunds.show', $refund->id)
                ->with('success', 'Refund processed successfully');

        } catch (\Exception $e) {
            Log::error('Error processing refund: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return $this->error([], 'Failed to process refund', 500);
            }
            
            return redirect()->back()->with('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Get refund statistics for dashboard
     */
    public function getStatistics(Request $request)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                return $this->error([], 'Unauthorized', 403);
            }

            $period = $request->query('period', 'month');
            $statistics = $this->refundService->getRefundStatistics($period);

            // Add additional admin-specific statistics
            $additionalStats = $this->getAdditionalStatistics($period);
            $statistics = array_merge($statistics, $additionalStats);

            return $this->success($statistics, 'Refund statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving refund statistics: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve refund statistics', 500);
        }
    }

    /**
     * Export refunds to CSV
     */
    public function exportRefunds(Request $request)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                return $this->error([], 'Unauthorized', 403);
            }

            $query = Refund::with(['appointment', 'payment', 'user', 'provider'])
                ->orderBy('created_at', 'desc');

            // Apply same filters as getRefunds
            if ($request->has('status') && $request->status) {
                $query->where('refund_status', $request->status);
            }

            if ($request->has('type') && $request->type) {
                $query->where('refund_type', $request->type);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $refunds = $query->get();

            // Generate CSV
            $filename = 'refunds_export_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($refunds) {
                $file = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($file, [
                    'ID',
                    'Reference',
                    'Customer Name',
                    'Customer Email',
                    'Provider Name',
                    'Provider Email',
                    'Appointment ID',
                    'Refund Type',
                    'Refund Amount',
                    'Vendor Amount',
                    'Admin Amount',
                    'Status',
                    'Reason',
                    'Cancellation Hours',
                    'Original Amount',
                    'Service Charges',
                    'Platform Fee',
                    'Other Charges',
                    'GST Amount',
                    'Created At',
                    'Processed At'
                ]);

                // CSV Data
                foreach ($refunds as $refund) {
                    fputcsv($file, [
                        $refund->id,
                        $refund->refund_reference,
                        $refund->user->name ?? 'N/A',
                        $refund->user->email ?? 'N/A',
                        $refund->provider->name ?? 'N/A',
                        $refund->provider->email ?? 'N/A',
                        $refund->appointment_id,
                        $refund->refund_type,
                        $refund->refund_amount,
                        $refund->vendor_amount,
                        $refund->admin_amount,
                        $refund->refund_status,
                        $refund->refund_reason,
                        $refund->cancellation_time_hours,
                        $refund->original_amount,
                        $refund->service_charges,
                        $refund->platform_fee,
                        $refund->other_charges,
                        $refund->gst_amount,
                        $refund->created_at->format('Y-m-d H:i:s'),
                        $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : 'Not Processed'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting refunds: ' . $e->getMessage());
            return $this->error([], 'Failed to export refunds', 500);
        }
    }

    /**
     * Generate refund receipt
     */
    public function generateReceipt(Refund $refund)
    {
        try {
            $refund->load(['appointment', 'payment', 'user', 'provider']);

            // Generate PDF receipt (you can use a PDF library like TCPDF or DomPDF)
            $html = $this->generateReceiptHTML($refund);
            
            // For now, return HTML (you can convert to PDF)
            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'inline; filename="refund_receipt_' . $refund->refund_reference . '.html"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating refund receipt: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate refund receipt');
        }
    }

    /**
     * Get additional statistics for admin dashboard
     */
    private function getAdditionalStatistics($period)
    {
        $startDate = match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };

        $refunds = Refund::where('created_at', '>=', $startDate)->get();

        return [
            'total_refund_requests' => $refunds->count(),
            'success_rate' => $refunds->count() > 0 ? 
                round(($refunds->where('refund_status', Refund::STATUS_PROCESSED)->count() / $refunds->count()) * 100, 2) : 0,
            'failed_refunds_amount' => $refunds->where('refund_status', Refund::STATUS_FAILED)->sum('refund_amount'),
            'pending_refunds_amount' => $refunds->where('refund_status', Refund::STATUS_PENDING)->sum('refund_amount'),
            'processed_refunds_amount' => $refunds->where('refund_status', Refund::STATUS_PROCESSED)->sum('refund_amount'),
            'total_vendor_earnings_from_cancellations' => $refunds->sum('vendor_amount'),
            'total_admin_earnings_from_cancellations' => $refunds->sum('admin_amount'),
            'refunds_by_day' => $this->getRefundsByDay($refunds),
            'top_cancellation_reasons' => $this->getTopCancellationReasons($refunds),
        ];
    }

    /**
     * Get refunds grouped by day for chart
     */
    private function getRefundsByDay($refunds)
    {
        return $refunds->groupBy(function ($refund) {
            return $refund->created_at->format('Y-m-d');
        })->map(function ($dayRefunds) {
            return [
                'count' => $dayRefunds->count(),
                'amount' => $dayRefunds->sum('refund_amount')
            ];
        });
    }

    /**
     * Get top cancellation reasons
     */
    private function getTopCancellationReasons($refunds)
    {
        return $refunds->whereNotNull('refund_reason')
            ->groupBy('refund_reason')
            ->map(function ($reasonRefunds) {
                return $reasonRefunds->count();
            })
            ->sortDesc()
            ->take(5);
    }

    /**
     * Generate HTML for refund receipt
     */
    private function generateReceiptHTML($refund)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Refund Receipt - {$refund->refund_reference}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .details { margin-bottom: 20px; }
                .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f2f2f2; }
                .total { font-weight: bold; background-color: #e8f5e8; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Refund Receipt</h1>
                <h2>Reference: {$refund->refund_reference}</h2>
                <p>Generated on: " . now()->format('d/m/Y H:i:s') . "</p>
            </div>
            
            <div class='details'>
                <h3>Refund Details</h3>
                <table class='table'>
                    <tr><td><strong>Refund Type:</strong></td><td>{$refund->refund_type_display}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>{$refund->human_status}</td></tr>
                    <tr><td><strong>Reason:</strong></td><td>{$refund->refund_reason}</td></tr>
                    <tr><td><strong>Created:</strong></td><td>{$refund->created_at->format('d/m/Y H:i:s')}</td></tr>
                    <tr><td><strong>Processed:</strong></td><td>" . ($refund->processed_at ? $refund->processed_at->format('d/m/Y H:i:s') : 'Not Processed') . "</td></tr>
                </table>
            </div>
            
            <div class='details'>
                <h3>Customer Information</h3>
                <table class='table'>
                    <tr><td><strong>Name:</strong></td><td>{$refund->user->name}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>{$refund->user->email}</td></tr>
                    <tr><td><strong>Mobile:</strong></td><td>{$refund->user->mobile}</td></tr>
                </table>
            </div>
            
            <div class='details'>
                <h3>Amount Breakdown</h3>
                <table class='table'>
                    <tr><td>Original Amount</td><td>₹" . number_format($refund->original_amount, 2) . "</td></tr>
                    <tr><td>Service Charges</td><td>₹" . number_format($refund->service_charges, 2) . "</td></tr>
                    <tr><td>Platform Fee</td><td>₹" . number_format($refund->platform_fee, 2) . "</td></tr>
                    <tr><td>Other Charges</td><td>₹" . number_format($refund->other_charges, 2) . "</td></tr>
                    <tr><td>GST Amount</td><td>₹" . number_format($refund->gst_amount, 2) . "</td></tr>
                    <tr class='total'><td><strong>Refund to Customer</strong></td><td><strong>₹" . number_format($refund->refund_amount, 2) . "</strong></td></tr>
                    <tr><td>Vendor Amount</td><td>₹" . number_format($refund->vendor_amount, 2) . "</td></tr>
                    <tr><td>Admin Amount</td><td>₹" . number_format($refund->admin_amount, 2) . "</td></tr>
                </table>
            </div>
            
            <div class='details'>
                <p><em>This is a computer-generated receipt and does not require a signature.</em></p>
            </div>
        </body>
        </html>
        ";
    }
}
