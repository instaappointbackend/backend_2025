<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class NewsletterController extends Controller
{
    /**
     * Display a listing of the newsletter subscribers.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        
        $query = Newsletter::query();
        
        // Filter by status if provided
        if ($status === 'subscribed') {
            $query->active();
        } elseif ($status === 'unsubscribed') {
            $query->inactive();
        } elseif ($status === 'pending') {
            $query->pending();
        }
        
        // Search by email or name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        
        $subscribers = $query->orderBy('created_at', 'desc')
                            ->paginate(20)
                            ->withQueryString();
                            
        $counts = [
            'all' => Newsletter::count(),
            'subscribed' => Newsletter::active()->count(),
            'unsubscribed' => Newsletter::inactive()->count(),
            'pending' => Newsletter::pending()->count(),
        ];
        
        return view('admin.newsletters.index', compact('subscribers', 'status', 'counts'));
    }

    /**
     * Show the form for creating a new subscriber.
     */
    public function create()
    {
        return view('admin.newsletters.create');
    }

    /**
     * Store a newly created subscriber in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:newsletters,email',
            'name' => 'nullable|string|max:255',
            'status' => 'required|in:subscribed,unsubscribed,pending',
        ]);
        
        $validated['subscribed_at'] = now();
        $validated['source'] = 'admin';
        
        Newsletter::create($validated);
        
        return redirect()->route('admin.newsletters.index')
            ->with('success', 'Subscriber added successfully');
    }

    /**
     * Show the form for editing the specified subscriber.
     */
    public function edit(Newsletter $newsletter)
    {
        return view('admin.newsletters.edit', compact('newsletter'));
    }

    /**
     * Update the specified subscriber in storage.
     */
    public function update(Request $request, Newsletter $newsletter)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:newsletters,email,' . $newsletter->id,
            'name' => 'nullable|string|max:255',
            'status' => 'required|in:subscribed,unsubscribed,pending',
        ]);
        
        // Update timestamps based on status change
        if ($newsletter->status !== $validated['status']) {
            if ($validated['status'] === 'subscribed') {
                $validated['subscribed_at'] = now();
                $validated['unsubscribed_at'] = null;
            } elseif ($validated['status'] === 'unsubscribed') {
                $validated['unsubscribed_at'] = now();
            }
        }
        
        $newsletter->update($validated);
        
        return redirect()->route('admin.newsletters.index')
            ->with('success', 'Subscriber updated successfully');
    }

    /**
     * Remove the specified subscriber from storage.
     */
    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();
        
        return redirect()->route('admin.newsletters.index')
            ->with('success', 'Subscriber deleted successfully');
    }
    
    /**
     * Export subscribers to CSV.
     */
    public function export(Request $request)
    {
        $status = $request->get('status', 'subscribed');
        
        $query = Newsletter::query();
        
        // Filter by status if provided
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $subscribers = $query->orderBy('created_at', 'desc')->get();
        
        // Create CSV
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=newsletter_subscribers.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        
        $columns = ['Email', 'Name', 'Status', 'Subscribed Date', 'Unsubscribed Date', 'Source', 'IP Address'];
        
        $callback = function() use ($subscribers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach ($subscribers as $subscriber) {
                fputcsv($file, [
                    $subscriber->email,
                    $subscriber->name ?? 'N/A',
                    $subscriber->status,
                    $subscriber->subscribed_at ? $subscriber->subscribed_at->format('Y-m-d H:i:s') : 'N/A',
                    $subscriber->unsubscribed_at ? $subscriber->unsubscribed_at->format('Y-m-d H:i:s') : 'N/A',
                    $subscriber->source ?? 'N/A',
                    $subscriber->ip_address ?? 'N/A',
                ]);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    /**
     * Bulk change status of subscribers.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:subscribe,unsubscribe,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:newsletters,id',
        ]);
        
        $count = count($validated['ids']);
        $action = $validated['action'];
        
        if ($action === 'subscribe') {
            Newsletter::whereIn('id', $validated['ids'])->update([
                'status' => 'subscribed',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
            $message = "{$count} subscribers have been subscribed.";
        } elseif ($action === 'unsubscribe') {
            Newsletter::whereIn('id', $validated['ids'])->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);
            $message = "{$count} subscribers have been unsubscribed.";
        } else { // delete
            Newsletter::whereIn('id', $validated['ids'])->delete();
            $message = "{$count} subscribers have been deleted.";
        }
        
        return redirect()->route('admin.newsletters.index')
            ->with('success', $message);
    }
}