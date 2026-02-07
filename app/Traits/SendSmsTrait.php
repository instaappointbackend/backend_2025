<?php

namespace App\Traits;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait SendSmsTrait
{
    /**
     * Send SMS OTP to the user's mobile.
     *
     * @param  string  $mobile
     * @param  string  $otp
     * @param  int  $otpExpireMinutes
     * @return bool
     */
    private function sendSms($mobile, $type, array $params = [])
    {
        try {
            // Get SMS settings from the database or cache
            $apiKey = $this->getSetting('sms_api_key');
            $senderId = $this->getSetting('sms_sender_id');
            $apiUrl = $this->getSetting('sms_api_url');

            // Get template ID and message based on the type
            $templateId = $this->getSetting("{$type}_template_id");
            $message = $this->getSetting("{$type}_template");
            // dd($type, $message);
            // $templateId = $this->getSetting('sms_api_template_id');
            // $message = $this->getSetting('otp_verification_sms_template');

            // Replace placeholders with actual values
            // $message = str_replace('[OTP]', $otp, $message);
            // $message = str_replace('[MINUTES]', $otpExpireMinutes, $message);

            // Replace placeholders in the message (e.g. [OTP], [NAME], [MINUTES])
            if (count($params) > 0) {
                foreach ($params as $key => $value) {
                    $message = str_replace("[$key]", $value, $message);
                }
            }

            // Prepare API request
            $queryParams = [
                'apikey' => $apiKey,
                'senderid' => $senderId,
                'templateid' => $templateId,
                'number' => $mobile,
                'message' => $message,
            ];

            $url = $apiUrl.'?'.http_build_query($queryParams);
            Log::info('SMS sent url', [
                'url' => $url,
            ]);
            // Send the SMS via API
            $response = Http::get($url);

            // Log the response
            Log::info('SMS sent', [
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
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    private function getSetting($key, $default = null)
    {
        // Try to get from cache first
        // $settings = Cache::remember('app_settings', 3600, function () {
        //     return AppSetting::pluck('value', 'key')->toArray();
        // });

        $settings = AppSetting::pluck('value', 'key')->toArray();

        return $settings[$key] ?? $default;
    }
}
