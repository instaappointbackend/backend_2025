<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of contact messages.
     */
    public function index(Request $request)
    {
        $query = Contact::with('user');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by source (users or guests)
        if ($request->has('source') && $request->source) {
            if ($request->source === 'users') {
                $query->fromUsers();
            } elseif ($request->source === 'guests') {
                $query->fromGuests();
            }
        }

        // Filter by search query
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('subject', 'like', "%{$searchTerm}%")
                    ->orWhere('message', 'like', "%{$searchTerm}%");
            });
        }

        $contacts = $query->latest()->paginate(15)->withQueryString();

        // Get counts for different categories
        $counts = [
            'all' => Contact::count(),
            'unread' => Contact::unread()->count(),
            'read' => Contact::read()->count(),
            'replied' => Contact::replied()->count(),
            'spam' => Contact::spam()->count(),
            'users' => Contact::fromUsers()->count(),
            'guests' => Contact::fromGuests()->count(),
        ];

        return view('admin.contact.index', compact('contacts', 'counts'));
    }

    /**
     * Show the specified contact message.
     */
    public function show(Contact $contact)
    {
        // Mark as read if it's unread
        if ($contact->status === 'unread') {
            $contact->markAsRead();
        }

        return view('admin.contact.show', compact('contact'));
    }

    /**
     * Update the status of a contact message.
     */
    public function updateStatus(Contact $contact, Request $request)
    {
        $request->validate([
            'status' => 'required|in:unread,read,replied,spam',
        ]);

        $contact->update([
            'status' => $request->status,
            'read_at' => $request->status !== 'unread' ? now() : null,
        ]);

        $statusLabels = [
            'unread' => 'Unread',
            'read' => 'Read',
            'replied' => 'Replied',
            'spam' => 'Spam',
        ];

        return redirect()->back()
            ->with('success', "Contact message marked as {$statusLabels[$request->status]} successfully.");
    }

    /**
     * Toggle the status of a contact message between resolved and open.
     */
    public function toggleStatus(Contact $contact)
    {
        $newStatus = $contact->status === 'replied' ? 'read' : 'replied';

        $contact->update([
            'status' => $newStatus,
            'read_at' => now(),
        ]);

        $message = $newStatus === 'replied'
            ? 'Contact message marked as resolved successfully.'
            : 'Contact message reopened successfully.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Handle bulk actions on contact messages.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:mark_read,mark_unread,mark_replied,mark_spam,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:contacts,id',
        ]);

        $contacts = Contact::whereIn('id', $request->ids);
        $count = $contacts->count();

        switch ($request->action) {
            case 'mark_read':
                $contacts->update([
                    'status' => 'read',
                    'read_at' => now(),
                ]);
                $message = "{$count} messages marked as read successfully.";
                break;

            case 'mark_unread':
                $contacts->update([
                    'status' => 'unread',
                    'read_at' => null,
                ]);
                $message = "{$count} messages marked as unread successfully.";
                break;

            case 'mark_replied':
                $contacts->update([
                    'status' => 'replied',
                    'read_at' => now(),
                ]);
                $message = "{$count} messages marked as replied successfully.";
                break;

            case 'mark_spam':
                $contacts->update([
                    'status' => 'spam',
                    'read_at' => now(),
                ]);
                $message = "{$count} messages marked as spam successfully.";
                break;

            case 'delete':
                $contacts->delete();
                $message = "{$count} messages deleted successfully.";
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Show the reply form for a contact message.
     */
    public function reply(Contact $contact)
    {
        // Mark as read if it's unread
        if ($contact->status === 'unread') {
            $contact->markAsRead();
        }

        return view('admin.contact.reply', compact('contact'));
    }

    /**
     * Send reply to a contact message.
     */
    public function sendReply(Contact $contact, Request $request)
    {
        $request->validate([
            'reply_subject' => 'required|string|max:255',
            'reply_message' => 'required|string',
            'send_copy_to_admin' => 'nullable|boolean',
        ]);

        try {
            // Get recipient email
            $recipientEmail = $contact->getSenderEmail();
            $recipientName = $contact->getSenderName();

            // Send email reply
            \Mail::send('emails.contact-reply', [
                'contact' => $contact,
                'replySubject' => $request->reply_subject,
                'replyMessage' => $request->reply_message,
                'recipientName' => 'gurjantkamboj20@gmail.com',
                'adminName' => auth()->user()->name ?? 'Support Team',
            ], function ($message) use ($recipientEmail, $recipientName, $request) {
                $message->to($recipientEmail, $recipientName)
                    ->subject($request->reply_subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            // Send copy to admin if requested
            if ($request->send_copy_to_admin) {
                \Mail::send('emails.contact-reply-copy', [
                    'contact' => $contact,
                    'replySubject' => $request->reply_subject,
                    'replyMessage' => $request->reply_message,
                    'recipientName' => $recipientName,
                    'recipientEmail' => $recipientEmail,
                    'adminName' => auth()->user()->name ?? 'Support Team',
                ], function ($message) use ($request) {
                    $message->to(auth()->user()->email ?? config('mail.from.address'))
                        ->subject('Copy: '.$request->reply_subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            }

            // Mark contact as replied
            $contact->markAsReplied();

            // Log the reply
            \Log::info('Contact reply sent', [
                'contact_id' => $contact->id,
                'recipient_email' => $recipientEmail,
                'subject' => $request->reply_subject,
                'admin_user' => auth()->user()->name ?? 'Unknown',
            ]);

            return redirect()->route('admin.contacts.index')
                ->with('success', 'Reply sent successfully to '.$recipientName.' ('.$recipientEmail.')');

        } catch (\Exception $e) {
            \Log::error('Failed to send contact reply', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to send reply. Please try again. Error: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified contact message from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Contact message deleted successfully.');
    }
}
