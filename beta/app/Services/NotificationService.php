<?php

namespace App\Services;

use App\Models\NotificationToken;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // Expo push notification service URL
    protected $expoUrl = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to user
     *
     * @param int $userId User ID to send notification to
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data to send with notification
     * @return bool Success status
     */
    public function sendPushNotification($userId, $title, $body, $data = [])
    {
        try {
            // Get all active tokens for the user
            $tokens = NotificationToken::where('user_id', $userId)
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            if (empty($tokens)) {
                Log::info('No active notification tokens found for user', ['user_id' => $userId]);
                return false;
            }

            // Prepare notification messages
            $messages = [];
            foreach ($tokens as $token) {
                $messages[] = [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
//                    'sound' => 'default',
//                    'badge' => 1,
//                    'channelId' => 'default', // For Android
                ];
            }

            // Send the push notification via Expo
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->expoUrl, $messages);

            // Log the response
            Log::info('Push notification sent', [
                'user_id' => $userId,
                'tokens' => count($tokens),
                'response' => $response->json(),
            ]);

            // Save notification to database
            $this->saveNotification($userId, $title, $body, $data);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Still save notification to database even if push fails
            $this->saveNotification($userId, $title, $body, $data);

            return false;
        }
    }

    /**
     * Save notification to database
     *
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @return void
     */
    protected function saveNotification($userId, $title, $body, $data = [])
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data),
                'is_read' => false,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save notification to database', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
