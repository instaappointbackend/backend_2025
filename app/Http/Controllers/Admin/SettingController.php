<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // General settings
        $generalSettings = [
            'app_name' => $this->getSetting('app_name', config('app.name')),
            'app_logo' => $this->getSetting('app_logo'),
            'app_logo_dark' => $this->getSetting('app_logo_dark'),
            'app_favicon' => $this->getSetting('app_favicon'),
            'admin_email' => $this->getSetting('admin_email', 'admin@example.com'),
            'support_email' => $this->getSetting('support_email', 'support@example.com'),
            'address' => $this->getSetting('address', ''),
            'social_facebook' => $this->getSetting('social_facebook', ''),
            'social_twitter' => $this->getSetting('social_twitter', ''),
            'social_instagram' => $this->getSetting('social_instagram', ''),
            'social_linkedin' => $this->getSetting('social_linkedin', ''),

            'support_phone' => $this->getSetting('support_phone', '+1234567890'),
            'currency' => $this->getSetting('currency', 'INR'),
            'timezone' => $this->getSetting('timezone', 'Asia/Kolkata'),
            'date_format' => $this->getSetting('date_format', 'Y-m-d'),
            'time_format' => $this->getSetting('time_format', 'H:i'),
        ];
        $feeSettings = [
            'platform_fee' => (float) $this->getSetting('platform_fee', 8),
            'other_charges_percentage' => (float) $this->getSetting('other_charges_percentage', 0.02),
            'gst_percentage' => (float) $this->getSetting('gst_percentage', 0.18),
            'enable_platform_fee' => (bool) $this->getSetting('enable_platform_fee', true),
            'enable_other_charges' => (bool) $this->getSetting('enable_other_charges', true),
            'enable_gst' => (bool) $this->getSetting('enable_gst', true),
            'fee_description' => $this->getSetting('fee_description', 'Platform processing fee'),
        ];

        // Booking settings
        $bookingSettings = [
            'min_booking_time' => (int) $this->getSetting('min_booking_time', 1), // in hours
            'max_booking_days' => (int) $this->getSetting('max_booking_days', 30), // in days
            'cancel_before_hours' => (int) $this->getSetting('cancel_before_hours', 24), // in hours
            'reschedule_before_hours' => (int) $this->getSetting('reschedule_before_hours', 12), // in hours
            'default_appointment_duration' => (int) $this->getSetting('default_appointment_duration', 60), // in minutes
            'buffer_time_between_appointments' => (int) $this->getSetting('buffer_time_between_appointments', 15), // in minutes
            'allow_same_day_booking' => (bool) $this->getSetting('allow_same_day_booking', true),
            'enable_weekend_booking' => (bool) $this->getSetting('enable_weekend_booking', true),
        ];

        // Commission settings
        $commissionSettings = [
            'commission_percentage' => (float) $this->getSetting('commission_percentage', 10),
            'minimum_payout_amount' => (float) $this->getSetting('minimum_payout_amount', 1000),
            'payout_schedule' => $this->getSetting('payout_schedule', 'monthly'), // weekly, bi-weekly, monthly
            'enable_automatic_payouts' => (bool) $this->getSetting('enable_automatic_payouts', false),
        ];

        // Notification settings
        $notificationSettings = [
            'enable_email_notifications' => (bool) $this->getSetting('enable_email_notifications', true),
            'enable_sms_notifications' => (bool) $this->getSetting('enable_sms_notifications', true),
            'enable_push_notifications' => (bool) $this->getSetting('enable_push_notifications', false),
            'booking_confirmation_email' => (bool) $this->getSetting('booking_confirmation_email', true),
            'booking_reminder_email' => (bool) $this->getSetting('booking_reminder_email', true),
            'booking_reminder_hours' => (int) $this->getSetting('booking_reminder_hours', 24),
            'admin_new_booking_notification' => (bool) $this->getSetting('admin_new_booking_notification', true),
            'booking_confirmation_sms' => (bool) $this->getSetting('booking_confirmation_sms', true),
            'booking_reminder_sms' => (bool) $this->getSetting('booking_reminder_sms', true),
            'admin_booking_cancellation_notification' => (bool) $this->getSetting('admin_booking_cancellation_notification', true),
        ];

        // API settings
        $apiSettings = [
            'google_maps_api_key' => $this->getSetting('google_maps_api_key'),
            'sms_api_key' => $this->getSetting('sms_api_key', 'mUK2DwqtHuN3rEcR'),
            'sms_sender_id' => $this->getSetting('sms_sender_id', 'INSTPT'),
            'enable_sms_api' => $this->getSetting('enable_sms_api', false),
            'otp_expired' => $this->getSetting('otp_expired', 10),
            'sms_api_url' => $this->getSetting('sms_api_url', 'https://manage.txly.in/vb/apikey.php'),
            'sms_api_template_id' => $this->getSetting('sms_api_template_id', '1707174142743104785'),
            'payment_gateway' => $this->getSetting('payment_gateway', 'phonepe'),
            'phonepe_merchant_id' => $this->getSetting('phonepe_merchant_id'),
            'phonepe_salt_key' => $this->getSetting('phonepe_salt_key'),
            'phonepe_salt_index' => $this->getSetting('phonepe_salt_index', '1'),
            'phonepe_production_mode' => (bool) $this->getSetting('phonepe_production_mode', false),
            'phonepe_callback_url' => $this->getSetting('phonepe_callback_url', url('/payment/phonepe/callback')),
            'payment_api_key' => $this->getSetting('payment_api_key'),
            'payment_api_secret' => $this->getSetting('payment_api_secret'),
            'payment_sandbox_mode' => (bool) $this->getSetting('payment_sandbox_mode', true),
            'mail_mailer' => $this->getSetting('mail_mailer', 'smtp'),
            'mail_host' => $this->getSetting('mail_host', 'smtp.gmail.com'),
            'mail_port' => $this->getSetting('mail_port', '587'),
            'mail_username' => $this->getSetting('mail_username'),
            'mail_password' => $this->getSetting('mail_password'),
            'mail_encryption' => $this->getSetting('mail_encryption', 'tls'),
            'mail_from_address' => $this->getSetting('mail_from_address', 'noreply@instaappoint.com'),
            'mail_from_name' => $this->getSetting('mail_from_name', 'InstaAppoint'),
        ];

        // SMS Templates
        $smsTemplates = [
            'otp_verification' => $this->getSetting('otp_verification_sms_template', '[OTP] is your OTP to login to Insta Appoint. DO NOT share with anyone. We never calls to ask for OTP. The otp expires in 10 mins.'),
            'booking_confirmation' => $this->getSetting('booking_confirmation_sms_template', 'Your appointment for [SERVICE] is confirmed for [DATE] at [TIME]. Booking ID: [BOOKING_ID]. Thank you for choosing InstaAppoint!'),
            'booking_reminder' => $this->getSetting('booking_reminder_sms_template', 'Reminder: Your appointment for [SERVICE] is tomorrow at [TIME]. We look forward to seeing you! If you need to reschedule, please contact us.'),
            'booking_cancellation' => $this->getSetting('booking_cancellation_sms_template', 'Your appointment for [SERVICE] on [DATE] at [TIME] has been cancelled. Booking ID: [BOOKING_ID]. If you wish to reschedule, please book again.'),
            'booking_rescheduled' => $this->getSetting('booking_rescheduled_sms_template', 'Your appointment has been rescheduled to [DATE] at [TIME] for [SERVICE]. Booking ID: [BOOKING_ID]. If this time doesn\'t work for you, please contact us.'),
            'payment_confirmation' => $this->getSetting('payment_confirmation_sms_template', 'Thank you! Your payment of [AMOUNT] for [SERVICE] has been received. Your appointment is confirmed for [DATE] at [TIME]. Booking ID: [BOOKING_ID].'),
            'booking_confirmation_template_id' => $this->getSetting('booking_confirmation_template_id'),
            'booking_reminder_template_id' => $this->getSetting('booking_reminder_template_id'),
            'booking_cancellation_template_id' => $this->getSetting('booking_cancellation_template_id'),
            'booking_rescheduled_template_id' => $this->getSetting('booking_rescheduled_template_id'),
            'payment_confirmation_template_id' => $this->getSetting('payment_confirmation_template_id'),
        ];

        // Email Templates
        $emailTemplates = [
            'booking_confirmation' => [
                'subject' => $this->getSetting('booking_confirmation_email_subject', 'Your appointment has been confirmed'),
                'body' => $this->getSetting('booking_confirmation_email_template', '<p>Hello {name},</p>
<p>Your appointment has been confirmed for {service} with {provider} on {date} at {time}.</p>
<p>Location: {location}</p>
<p>Booking ID: {booking_id}</p>
<p>If you need to make any changes to your appointment, please contact us at least {cancel_hours} hours in advance.</p>
<p>Thank you for choosing us!</p>
<p>Regards,<br>{company_name} Team</p>'),
            ],
            'booking_reminder' => [
                'subject' => $this->getSetting('booking_reminder_email_subject', 'Reminder: Your appointment tomorrow'),
                'body' => $this->getSetting('booking_reminder_email_template', '<p>Hello {name},</p>
<p>This is a friendly reminder about your upcoming appointment:</p>
<p><strong>Service:</strong> {service}<br>
<strong>Provider:</strong> {provider}<br>
<strong>Date:</strong> {date}<br>
<strong>Time:</strong> {time}<br>
<strong>Location:</strong> {location}</p>
<p>If you need to reschedule or cancel, please contact us as soon as possible.</p>
<p>We look forward to seeing you!</p>
<p>Regards,<br>{company_name} Team</p>'),
            ],
            'booking_cancellation' => [
                'subject' => $this->getSetting('booking_cancellation_email_subject', 'Your appointment has been cancelled'),
                'body' => $this->getSetting('booking_cancellation_email_template', '<p>Hello {name},</p>
<p>We are writing to confirm that your appointment for {service} on {date} at {time} has been cancelled.</p>
<p>Booking ID: {booking_id}</p>
<p>If this cancellation was in error or you would like to reschedule, please visit our website or contact our customer support team.</p>
<p>Thank you for your understanding.</p>
<p>Regards,<br>{company_name} Team</p>'),
            ],
            'payment_confirmation' => [
                'subject' => $this->getSetting('payment_confirmation_email_subject', 'Payment Confirmation - Receipt'),
                'body' => $this->getSetting('payment_confirmation_email_template', '<p>Hello {name},</p>
<p>Thank you for your payment. This email confirms that your payment has been processed successfully.</p>
<p><strong>Payment Details:</strong></p>
<ul>
    <li>Amount: {amount}</li>
    <li>Payment Method: {payment_method}</li>
    <li>Transaction ID: {transaction_id}</li>
    <li>Date: {payment_date}</li>
</ul>
<p><strong>Appointment Details:</strong></p>
<ul>
    <li>Service: {service}</li>
    <li>Date: {date}</li>
    <li>Time: {time}</li>
    <li>Provider: {provider}</li>
    <li>Location: {location}</li>
</ul>
<p>If you have any questions about this payment or your appointment, please contact our support team.</p>
<p>Thank you for choosing our services!</p>
<p>Regards,<br>{company_name} Team</p>'),
            ],
            'otp_verification' => [
                'subject' => $this->getSetting('otp_verification_email_subject', 'Your Verification Code'),
                'body' => $this->getSetting('otp_verification_email_template', '<p>Hello,</p>
<p>Your verification code for Insta Appoint is: <strong>{otp}</strong></p>
<p>This code is valid for 10 minutes. Please do not share this code with anyone.</p>
<p>If you did not request this code, please ignore this email.</p>
<p>Regards,<br>{company_name} Team</p>'),
            ],
        ];

        // SEO settings
        $seoSettings = [
            'meta_title' => $this->getSetting('meta_title', 'Appointment Booking System'),
            'meta_description' => $this->getSetting('meta_description', 'Book your appointments online with our easy to use booking system.'),
            'meta_keywords' => $this->getSetting('meta_keywords', 'appointment, booking, online booking'),
            'google_analytics_id' => $this->getSetting('google_analytics_id'),
            'facebook_pixel_id' => $this->getSetting('facebook_pixel_id'),
            'social_facebook' => $this->getSetting('social_facebook'),
            'social_twitter' => $this->getSetting('social_twitter'),
            'social_instagram' => $this->getSetting('social_instagram'),
            'social_linkedin' => $this->getSetting('social_linkedin'),
        ];

        // dd($apiSettings);
        return view('admin.settings.index', compact(
            'generalSettings',
            'bookingSettings',
            'commissionSettings',
            'notificationSettings',
            'apiSettings',
            'smsTemplates',
            'emailTemplates',
            'seoSettings',
            'feeSettings'
        ));
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $rules = [
            // General settings
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|max:5120',
            'app_logo_dark' => 'nullable|image|max:5120',
            'app_favicon' => 'nullable|image|max:1024',
            'admin_email' => 'required|email|max:255',
            'support_email' => 'required|email|max:255',
            'address' => 'required',
            'support_phone' => 'required|string|max:20',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|string|max:255',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',

            // Booking settings
            'min_booking_time' => 'required|integer|min:0',
            'max_booking_days' => 'required|integer|min:1',
            'cancel_before_hours' => 'required|integer|min:0',
            'reschedule_before_hours' => 'required|integer|min:0',
            'default_appointment_duration' => 'required|integer|min:5',
            'buffer_time_between_appointments' => 'required|integer|min:0',
            'allow_same_day_booking' => 'nullable|boolean',
            'enable_weekend_booking' => 'nullable|boolean',

            'platform_fee' => 'nullable|numeric|min:0',
            'other_charges_percentage' => 'nullable|numeric|min:0|max:100',
            'gst_percentage' => 'nullable|numeric|min:0|max:100',
            'enable_platform_fee' => 'nullable|boolean',
            'enable_other_charges' => 'nullable|boolean',
            'enable_gst' => 'nullable|boolean',
            'fee_description' => 'nullable|string|max:255',

            // Commission settings - Made optional
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'minimum_payout_amount' => 'nullable|numeric|min:0',
            'payout_schedule' => 'nullable|in:weekly,bi-weekly,monthly',
            'enable_automatic_payouts' => 'nullable|boolean',

            // Notification settings
            'enable_email_notifications' => 'nullable|boolean',
            'enable_sms_notifications' => 'nullable|boolean',
            'enable_push_notifications' => 'nullable|boolean',
            'booking_confirmation_email' => 'nullable|boolean',
            'booking_reminder_email' => 'nullable|boolean',
            'booking_reminder_hours' => 'nullable|integer|min:1|max:72',
            'admin_new_booking_notification' => 'nullable|boolean',
            'booking_confirmation_sms' => 'nullable|boolean',
            'booking_reminder_sms' => 'nullable|boolean',
            'admin_booking_cancellation_notification' => 'nullable|boolean',

            // API settings - Made optional
            'google_maps_api_key' => 'nullable|string',
            'sms_api_key' => 'nullable|string',
            'sms_sender_id' => 'nullable|string|max:6',
            'otp_expired' => 'nullable|integer',
            'enable_sms_api' => 'nullable|boolean',
            'sms_api_url' => 'nullable|string|url',
            'sms_api_template_id' => 'nullable|string',
            'payment_gateway' => 'nullable|string|in:phonepe,razorpay,stripe,paypal',
            'phonepe_merchant_id' => 'nullable|string',
            'phonepe_salt_key' => 'nullable|string',
            'phonepe_salt_index' => 'nullable|string',
            'phonepe_production_mode' => 'nullable|boolean',
            'payment_api_key' => 'nullable|string',
            'payment_api_secret' => 'nullable|string',
            'payment_sandbox_mode' => 'nullable|boolean',
            'mail_mailer' => 'nullable|string|in:smtp,sendmail,mailgun,ses',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|string',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string|in:tls,ssl,',
            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string',

            // SMS Templates - Made optional
            'otp_verification_sms_template' => 'nullable|string',
            'booking_confirmation_sms_template' => 'nullable|string',
            'booking_reminder_sms_template' => 'nullable|string',
            'booking_cancellation_sms_template' => 'nullable|string',
            'booking_rescheduled_sms_template' => 'nullable|string',
            'payment_confirmation_sms_template' => 'nullable|string',
            'booking_confirmation_template_id' => 'nullable|string',
            'booking_reminder_template_id' => 'nullable|string',
            'booking_cancellation_template_id' => 'nullable|string',
            'booking_rescheduled_template_id' => 'nullable|string',
            'payment_confirmation_template_id' => 'nullable|string',

            // Email Templates - Made optional
            'booking_confirmation_email_subject' => 'nullable|string',
            'booking_confirmation_email_template' => 'nullable|string',
            'booking_reminder_email_subject' => 'nullable|string',
            'booking_reminder_email_template' => 'nullable|string',
            'booking_cancellation_email_subject' => 'nullable|string',
            'booking_cancellation_email_template' => 'nullable|string',
            'payment_confirmation_email_subject' => 'nullable|string',
            'payment_confirmation_email_template' => 'nullable|string',
            'otp_verification_email_subject' => 'nullable|string',
            'otp_verification_email_template' => 'nullable|string',

            // SEO settings
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'google_analytics_id' => 'nullable|string|max:20',
            'facebook_pixel_id' => 'nullable|string|max:20',
            'social_facebook' => 'nullable|url',
            'social_twitter' => 'nullable|url',
            'social_instagram' => 'nullable|url',
            'social_linkedin' => 'nullable|url',
        ];

        $validated = $request->validate($rules);

        // Set default values for missing fields
        $defaults = [
            'enable_platform_fee' => false,
            'enable_other_charges' => false,
            'enable_gst' => false,
            'enable_email_notifications' => false,
            'enable_sms_notifications' => false,
            'enable_push_notifications' => false,
            'booking_confirmation_email' => false,
            'booking_reminder_email' => false,
            'booking_confirmation_sms' => false,
            'booking_reminder_sms' => false,
            'admin_new_booking_notification' => false,
            'admin_booking_cancellation_notification' => false,
            'allow_same_day_booking' => false,
            'enable_weekend_booking' => false,
            'enable_automatic_payouts' => false,
            'enable_sms_api' => false,
            'phonepe_production_mode' => false,
            'payment_sandbox_mode' => true,
            'commission_percentage' => 0,
            'platform_fee' => 0,
            'other_charges_percentage' => 0,
            'gst_percentage' => 0,
            'booking_reminder_hours' => 24,
            'minimum_payout_amount' => 100,
            'payout_schedule' => 'monthly',
        ];

        // Merge defaults with validated data
        foreach ($defaults as $key => $defaultValue) {
            if (! isset($validated[$key])) {
                $validated[$key] = $defaultValue;
            }
        }

        // Convert percentages
        if (isset($validated['other_charges_percentage'])) {
            $validated['other_charges_percentage'] = $validated['other_charges_percentage'] / 100;
        }

        if (isset($validated['gst_percentage'])) {
            $validated['gst_percentage'] = $validated['gst_percentage'] / 100;
        }

        // Handle file uploads
        if ($request->hasFile('app_logo')) {
            $oldLogo = $this->getSetting('app_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $validated['app_logo'] = $request->file('app_logo')->store('settings', 'public');
        }

        if ($request->hasFile('app_logo_dark')) {
            $oldLogo = $this->getSetting('app_logo_dark');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $validated['app_logo_dark'] = $request->file('app_logo_dark')->store('settings', 'public');
        }

        if ($request->hasFile('app_favicon')) {
            $oldFavicon = $this->getSetting('app_favicon');
            if ($oldFavicon) {
                Storage::disk('public')->delete($oldFavicon);
            }
            $validated['app_favicon'] = $request->file('app_favicon')->store('settings', 'public');
        }

        // Handle checkbox values properly
        $validated['enable_sms_api'] = ! empty($validated['enable_sms_api']);

        // Update settings
        foreach ($validated as $key => $value) {
            $this->updateSetting($key, $value);
        }

        // Clear settings cache
        Cache::forget('app_settings');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Test SMS configuration by sending a test message.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSms(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string',
            'api_key' => 'required|string',
            'sender_id' => 'required|string',
            'template_id' => 'required|string',
            'api_url' => 'required|string',
        ]);

        try {
            // Construct the API URL with query parameters
            $url = $validated['api_url'];
            $queryParams = [
                'apikey' => $validated['api_key'],
                'senderid' => $validated['sender_id'],
                'templateid' => $validated['template_id'],
                'number' => $validated['phone_number'],
                'message' => $validated['message'],
            ];

            $fullUrl = $url.'?'.http_build_query($queryParams);
            //            print_r($fullUrl);die;
            // Make the HTTP request to the SMS API
            $response = Http::get($fullUrl);

            // Log the request and response
            Log::info('SMS Test Request', [
                'url' => $fullUrl,
                'response' => $response->body(),
            ]);

            // Check if the request was successful
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'SMS API returned an error: '.$response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS Test Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Test Email configuration by sending a test email.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testEmail(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'mail_driver' => 'required|string',
            'mail_host' => 'required|string',
            'mail_port' => 'required|string',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        try {
            // Set temporary mail configuration
            config([
                'mail.default' => $validated['mail_driver'],
                'mail.mailers.smtp.host' => $validated['mail_host'],
                'mail.mailers.smtp.port' => $validated['mail_port'],
                'mail.mailers.smtp.username' => $validated['mail_username'],
                'mail.mailers.smtp.password' => $validated['mail_password'],
                'mail.mailers.smtp.encryption' => $validated['mail_encryption'],
                'mail.from.address' => $validated['mail_from_address'],
                'mail.from.name' => $validated['mail_from_name'],
            ]);

            // Send test email
            \Mail::raw($validated['message'], function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject($validated['subject']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Email Test Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Get a setting value by key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    private function getSetting($key, $default = null)
    {
        // Try to get from cache first
        $settings = Cache::remember('app_settings', 3600, function () {
            return AppSetting::pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    /**
     * Update a setting value by key.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    private function updateSetting($key, $value)
    {
        AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
