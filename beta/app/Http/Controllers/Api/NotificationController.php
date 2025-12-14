<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationToken;
use App\Services\NotificationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotifications(Request $request)
    {
        $limit = $request->get('limit', 50);
        $userId = Auth::id();

        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return $this->success(
            $notifications,
            'Notifications retrieved successfully'
        );
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id Notification ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $userId = Auth::id();

        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return $this->error([], 'Notification not found', 404);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $this->success(
            $notification,
            'Notification marked as read'
        );
    }

    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        $userId = Auth::id();

        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->success(
            [],
            'All notifications marked as read'
        );
    }

    /**
     * Get unread notification count.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        $userId = Auth::id();

        $count = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return $this->success(
            ['count' => $count],
            'Unread notification count retrieved'
        );
    }

    /**
     * Register a device token for push notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_info' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $userId = Auth::id();

        try {
            Log::info('Registering notification token', [
                'user_id' => $userId,
                'token' => substr($request->token, 0, 10) . '...' // Log only part of token for security
            ]);

            // Update or create notification token
            NotificationToken::updateOrCreate(
                [
                    'token' => $request->token,
                    'user_id' => $userId
                ],
                [
                    'user_role' => Auth::user()->role,
                    'device_info' => $request->device_info,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]
            );

            return $this->success([], 'Notification token registered successfully');
        } catch (\Exception $e) {
            Log::error('Failed to register notification token', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error([], 'Failed to register notification token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test sending a notification.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testNotification()
    {
        try {
            $userId = 67; // Use authenticated user instead of hardcoded ID

            if (!$userId) {
                return $this->error([], 'Not authenticated', 401);
            }

            Log::info('Testing notification for user', ['user_id' => $userId]);

            // Create a test notification
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => 'Test Notification',
                'body' => 'This is a test notification from the API.',
                'data' => json_encode(['type' => 'test']),
                'is_read' => false,
                'sent_at' => now(),
            ]);

            // Send push notification
            $result = $this->notificationService->sendPushNotification(
                $userId,
                'Test Notification',
                'This is a test notification from the API.',
                [
                    'appointmentId' => 88,
                    'screenName'=>'appointmentDetails'
                    ]
            );

            return $this->success(
                [
                    'notification' => $notification,
                    'push_result' => $result
                ],
                'Test notification sent successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error([], 'Failed to send test notification: ' . $e->getMessage(), 500);
        }
    }

    public function getAllToken()
    {
        try {
            $data = [];
            $tokens = NotificationToken::where('is_active', true)->orderBy('id','desc')->get();
            $i = 0;
            foreach ($tokens as $token) {
                $data[$i]['token'] = $token->token;
                $data[$i]['user_id'] = $token->user_id;
                $data[$i]['updated_at'] = date('Y-m-d H:i:s', strtotime($token->updated_at));
                $i++;
            }
            
            return $this->success($data, 'Active tokens retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to get tokens', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error([], 'Failed to get tokens: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get notification token status for current user
     */
    public function getTokenStatus()
    {
        try {
            $userId = Auth::id();
            
            $tokens = NotificationToken::where('user_id', $userId)
                ->where('is_active', true)
                ->get();

            $recentNotifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return $this->success([
                'user_id' => $userId,
                'active_tokens_count' => $tokens->count(),
                'tokens' => $tokens->map(function($token) {
                    return [
                        'id' => $token->id,
                        'token' => substr($token->token, 0, 20) . '...',
                        'last_used_at' => $token->last_used_at,
                        'device_info' => $token->device_info
                    ];
                }),
                'recent_notifications' => $recentNotifications
            ], 'Token status retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get token status', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return $this->error([], 'Failed to get token status', 500);
        }
    }

    
    
    
      
}
