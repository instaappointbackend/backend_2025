<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormSubmission;
use App\Models\Contact;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Process the contact form submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submit(Request $request)
    {
        // Validate the form data
        $validated = $request->validate([
            'name' => Auth::check() ? 'nullable|string|max:255' : 'required|string|max:255',
            'email' => Auth::check() ? 'nullable|email|max:255' : 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            // Add IP address and user agent
            $validated['ip_address'] = $request->ip();
            $validated['user_agent'] = $request->userAgent();
            
            // If user is authenticated, associate with user
            if (Auth::check()) {
                $validated['user_id'] = Auth::id();
                
                // Use user's name and email if not provided
                if (empty($validated['name'])) {
                    $validated['name'] = Auth::user()->name;
                }
                
                if (empty($validated['email'])) {
                    $validated['email'] = Auth::user()->email;
                }
            }
            
            // Save to database
            $contact = Contact::create($validated);

            // Get admin email from app_settings
            $adminEmail = AppSetting::where('key', 'admin_email')->value('value');
            
            // If admin email is not found, fall back to the config value
            if (!$adminEmail) {
                $adminEmail = config('mail.from.address');
            }

            // Send email notification
            Mail::to($adminEmail)->queue(new ContactFormSubmission($validated));

            return redirect()->back()->with('success', 'Thank you for your message. We will get back to you soon!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Sorry, there was an error sending your message. Please try again later.')
                ->withInput();
        }
    }
    
    /**
     * Display a listing of the contact messages (Admin).
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $source = $request->get('source');
        
        $query = Contact::query();
        
        // Filter by status if provided
        if ($status === 'unread') {
            $query->unread();
        } elseif ($status === 'read') {
            $query->read();
        } elseif ($status === 'replied') {
            $query->replied();
        } elseif ($status === 'spam') {
            $query->spam();
        } elseif ($status === 'open') {
            $query->where('status', 'unread')->orWhere('status', 'read');
        } elseif ($status === 'resolved') {
            $query->replied();
        }
        
        // Filter by source if provided
        if ($source === 'users') {
            $query->fromUsers();
        } elseif ($source === 'guests') {
            $query->fromGuests();
        }
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }
        
        // Load user relationship if needed
        if ($source === 'users' || $source === null) {
            $query->with('user');
        }
        
        $contacts = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        
        $counts = [
            'all' => Contact::count(),
            'unread' => Contact::unread()->count(),
            'read' => Contact::read()->count(),
            'replied' => Contact::replied()->count(),
            'spam' => Contact::spam()->count(),
            'users' => Contact::fromUsers()->count(),
            'guests' => Contact::fromGuests()->count(),
            'open' => Contact::unread()->orWhere('status', 'read')->count(),
            'resolved' => Contact::replied()->count()
        ];
        
        return view('admin.contacts.index', compact('contacts', 'counts', 'status', 'source'));
    }
    
    /**
     * Display the specified contact message (Admin).
     */
    public function show(Contact $contact)
    {
        // Mark as read if currently unread
        if ($contact->status === 'unread') {
            $contact->markAsRead();
        }
        
        // Load associated user if applicable
        if ($contact->user_id) {
            $contact->load('user');
        }
        
        return view('admin.contacts.show', compact('contact'));
    }
    
    /**
     * Update the status of the contact message (Admin).
     */
    public function updateStatus(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'status' => 'required|in:read,unread,replied,spam',
        ]);
        
        $contact->status = $validated['status'];
        
        if ($validated['status'] === 'read' && !$contact->read_at) {
            $contact->read_at = now();
        }
        
        $contact->save();
        
        return redirect()->back()->with('success', 'Contact status updated successfully.');
    }
    
    /**
     * Toggle the status of a support ticket between open and resolved.
     */
    public function toggleStatus(Contact $contact)
    {
        if ($contact->status === 'replied') {
            $contact->status = 'read';
            $contact->save();
            $message = 'Support ticket marked as open.';
        } else {
            $contact->markAsReplied();
            $message = 'Support ticket marked as resolved.';
        }
        
        return redirect()->back()->with('success', $message);
    }
    
    /**
     * Remove the specified contact message from storage (Admin).
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();
        
        return redirect()->route('admin.contacts.index')
            ->with('success', 'Contact message deleted successfully.');
    }
    
    /**
     * Bulk actions for contact messages (Admin).
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:mark_read,mark_unread,mark_replied,mark_spam,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:contacts,id',
        ]);
        
        $count = count($validated['ids']);
        $action = $validated['action'];
        
        switch ($action) {
            case 'mark_read':
                Contact::whereIn('id', $validated['ids'])->update([
                    'status' => 'read',
                    'read_at' => now(),
                ]);
                $message = "{$count} messages marked as read.";
                break;
                
            case 'mark_unread':
                Contact::whereIn('id', $validated['ids'])->update([
                    'status' => 'unread',
                    'read_at' => null,
                ]);
                $message = "{$count} messages marked as unread.";
                break;
                
            case 'mark_replied':
                Contact::whereIn('id', $validated['ids'])->update([
                    'status' => 'replied',
                ]);
                $message = "{$count} messages marked as replied.";
                break;
                
            case 'mark_spam':
                Contact::whereIn('id', $validated['ids'])->update([
                    'status' => 'spam',
                ]);
                $message = "{$count} messages marked as spam.";
                break;
                
            case 'delete':
                Contact::whereIn('id', $validated['ids'])->delete();
                $message = "{$count} messages deleted.";
                break;
        }
        
        return redirect()->route('admin.contacts.index')
            ->with('success', $message);
    }
}