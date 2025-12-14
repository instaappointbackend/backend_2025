<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Newsletter;
use App\Models\AppSetting;
use App\Mail\NewSubscriberNotification;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    /**
     * Process the newsletter subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function subscribe(Request $request)
    {
        // Validate the form data
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            // Check if email already exists
            $exists = Newsletter::where('email', $validated['email'])->first();
            
            if ($exists) {
                // If already unsubscribed, resubscribe them
                if ($exists->status === 'unsubscribed') {
                    $exists->resubscribe();
                    return redirect()->back()->with('success', 'Welcome back! You have been resubscribed to our newsletter.');
                }
                
                // Already subscribed
                return redirect()->back()->with('info', 'You are already subscribed to our newsletter.');
            }
            
            // Create new subscription
            $subscriber = Newsletter::create([
                'email' => $validated['email'],
                'name' => $validated['name'] ?? null,
                'status' => 'subscribed',
                'subscribed_at' => now(),
                'source' => $request->get('source', 'website'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Send notification to admin
            $this->sendAdminNotification($subscriber);

            return redirect()->back()->with('success', 'Thank you for subscribing to our newsletter!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Sorry, there was an error processing your subscription. Please try again later.')
                ->withInput();
        }
    }

    /**
     * Unsubscribe from the newsletter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $email
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsubscribe(Request $request, $email = null)
    {
        // If email is not provided in the URL, get it from the form
        if (!$email) {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
            ]);
            
            $email = $validated['email'];
        }
        
        try {
            // Find the subscription
            $subscriber = Newsletter::where('email', $email)->first();
            
            if (!$subscriber) {
                return redirect()->back()->with('error', 'Email not found in our newsletter database.');
            }
            
            // Update status
            $subscriber->unsubscribe();
            
            return redirect()->back()->with('success', 'You have been successfully unsubscribed from our newsletter.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sorry, there was an error processing your request. Please try again later.');
        }
    }

    /**
     * Send notification to admin about new subscriber.
     *
     * @param  \App\Models\Newsletter  $subscriber
     * @return void
     */
    private function sendAdminNotification(Newsletter $subscriber)
    {
        try {
            // Get admin email from app settings
            $adminEmail = AppSetting::where('key', 'admin_email')->value('value');
            
            
            if (!$adminEmail) {
                // Fallback to a default email if not set in app settings
                $adminEmail = config('mail.from.address');
            }
            
            // Send email notification
            Mail::to($adminEmail)->queue(new NewSubscriberNotification($subscriber));
        } catch (\Exception $e) {
            // Log the error but don't affect the user experience
            \Log::error('Failed to send admin notification: ' . $e->getMessage());
        }
    }
}