<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show admin login form
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * Send OTP to admin user
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'login_field' => 'required',
        ]);

        // Determine if input is email or mobile
        $loginField = filter_var($request->login_field, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        
        $query = User::where($loginField, $request->login_field)
                     ->where('role', 'admin');

        $user = $query->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login_field' => ['The provided credentials are incorrect or you do not have admin access.'],
            ]);
        }

        // Get settings from the database
        $enableSmsApi = $this->getSetting('enable_sms_api', false);
        $otpExpireMinutes = (int) $this->getSetting('otp_expired', 10);

        // Generate OTP - either random or static based on settings
        if ($enableSmsApi) {
            // Generate a random 6-digit OTP
            $otp = mt_rand(100000, 999999);
            
            // Send OTP via SMS
            $this->sendSmsOtp($user->mobile, $otp, $otpExpireMinutes);
        } else {
            // Use static OTP when SMS API is disabled or when using email
            $otp = '123456';
            
            if ($loginField === 'email') {
                // Here you could add email sending logic if needed
                Log::info('Email OTP would be sent here', ['email' => $user->email, 'otp' => $otp]);
            } else {
                Log::info('Using static OTP for admin', ['mobile' => $user->mobile, 'otp' => $otp]);
            }
        }
        
        // Store OTP and set expiration time
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes($otpExpireMinutes);
        $user->save();

        // Store login field in session to use on OTP verification page
        Session::put('admin_login_field', $request->login_field);
        Session::put('admin_login_type', $loginField);

        // For development only - show the OTP if it's static
        if (!$enableSmsApi || $loginField === 'email') {
            Session::flash('dev_otp', $otp);
        }

        return redirect()->route('admin.otp.form')
            ->with('success', 'An OTP has been sent to your ' . 
                ($loginField === 'email' ? 'email address' : 'mobile number') . 
                ($enableSmsApi ? '' : ' (Using test OTP: 123456)') . '.');
    }

    /**
     * Send SMS OTP to the user's mobile.
     */
    private function sendSmsOtp($mobile, $otp, $otpExpireMinutes)
    {
        try {
            // Get SMS settings from the database
            $apiKey = $this->getSetting('sms_api_key');
            $senderId = $this->getSetting('sms_sender_id');
            $templateId = $this->getSetting('sms_api_template_id');
            $apiUrl = $this->getSetting('sms_api_url');
            $message = $this->getSetting('otp_verification_sms_template');
            
            // Get message template and replace [OTP] with actual OTP
        
            $message = str_replace('[OTP]', $otp, $message);
            $message = str_replace('[MINUTES]', $otpExpireMinutes, $message);
            

            
            // Prepare API request
            $queryParams = [
                'apikey' => $apiKey,
                'senderid' => $senderId,
                'templateid' => $templateId,
                'number' => $mobile,
                'message' => $message,
            ];
            
            $url = $apiUrl . '?' . http_build_query($queryParams);
            
            
            // Send the SMS via API
            $response = Http::get($url);
            
            // Log the response
            Log::info('Admin SMS sent', [
                'mobile' => $mobile,
                'response' => $response->body(),
            ]);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send admin SMS', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get a setting value by key.
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
     * Show OTP verification form
     */
    public function showOtpForm()
    {
        if (!Session::has('admin_login_field')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify-otp');
    }

    /**
     * Verify OTP and login
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
        ]);

        if (!Session::has('admin_login_field') || !Session::has('admin_login_type')) {
            return redirect()->route('admin.login');
        }

        $loginField = Session::get('admin_login_type');
        $loginValue = Session::get('admin_login_field');
        
        $user = User::where($loginField, $loginValue)
                    ->where('role', 'admin')
                    ->first();

        if (!$user) {
            return redirect()->route('admin.login')
                ->with('error', 'User not found.');
        }

        // Check if OTP has expired
        if (!$user->otp_expires_at || $user->otp_expires_at->lt(now())) {
            return back()->with('error', 'The OTP has expired. Please request a new one.');
        }

        // Verify OTP
        if ($user->otp !== $request->otp) {
            return back()->with('error', 'The OTP entered is invalid.');
        }

        // Clear OTP after successful verification
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        Auth::login($user);
        Session::forget(['admin_login_field', 'admin_login_type']);
        
        return redirect()->route('admin.dashboard')
            ->with('success', 'Welcome to the admin dashboard!');
    }

    /**
     * Logout admin
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login')
            ->with('success', 'You have been successfully logged out.');
    }
}