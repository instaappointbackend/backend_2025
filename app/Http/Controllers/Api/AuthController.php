<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResponse;
use App\Models\User;
use App\Models\AppSetting;
use App\Models\NotificationToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\AuthRequest;
use App\Services\User\UserRegistrationService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Send OTP to the user's mobile.
     */
    public function sendOtp(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|digits:10',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $user = User::firstOrCreate(['mobile' => $request->mobile]);

            // Get settings from the database
            $enableSmsApi = $this->getSetting('enable_sms_api', false);
            $otpExpireMinutes = (int) $this->getSetting('otp_expired', 10);

            // Generate OTP - either random or static based on settings
            if ($enableSmsApi) {
                // Generate a random 6-digit OTP
                $otp = mt_rand(100000, 999999);

                // Send OTP via SMS
                $smsSent = $this->sendSmsOtp($user->mobile, $otp, $otpExpireMinutes);

                if (!$smsSent) {
                    Log::warning('SMS failed, falling back to static OTP', ['mobile' => $user->mobile]);
                    $otp = '123456'; // Fallback to static OTP if SMS fails
                }
            } else {
                // Use static OTP when SMS API is disabled
                $otp = '123456';
                Log::info('Using static OTP for development', ['mobile' => $user->mobile]);
            }

            // Store OTP and set expiration time
            $user->otp = $otp;
            $user->otp_expires_at = now()->addMinutes($otpExpireMinutes);
            $user->save();

            Log::info('OTP generated successfully', [
                'mobile' => $user->mobile,
                'otp' => $otp, // Remove this in production
                'expires_at' => $user->otp_expires_at
            ]);

            return $this->success([
                'minutes_left' => $otpExpireMinutes,
                'message' => $enableSmsApi ? 'OTP sent to your mobile number' : 'OTP generated for development'
            ], 'OTP ' . ($enableSmsApi ? 'sent' : 'generated') . ' successfully.');
        } catch (\Exception $e) {
            Log::error('Error in sendOtp', [
                'mobile' => $request->mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error([], 'Failed to send OTP. Please try again.', 500);
        }
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

            // Validate SMS settings
            if (!$apiKey || !$senderId || !$apiUrl || !$message) {
                Log::error('SMS settings incomplete', [
                    'has_api_key' => !empty($apiKey),
                    'has_sender_id' => !empty($senderId),
                    'has_api_url' => !empty($apiUrl),
                    'has_message' => !empty($message)
                ]);
                return false;
            }

            // Get message template and replace placeholders
            $message = str_replace('[OTP]', $otp, $message);
            $message = str_replace('[MINUTES]', $otpExpireMinutes, $message);

            // Prepare API request
            $queryParams = [
                'apikey' => $apiKey,
                'senderid' => $senderId,
                'number' => $mobile,
                'message' => $message,
            ];

            // Add template ID if provided
            if ($templateId) {
                $queryParams['templateid'] = $templateId;
            }

            $url = $apiUrl . '?' . http_build_query($queryParams);

            // Send the SMS via API with timeout
            $response = Http::timeout(30)->get($url);

            // Log the response
            Log::info('SMS API Response', [
                'mobile' => $mobile,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'success' => $response->successful()
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get a setting value by key.
     */
    private function getSetting($key, $default = null)
    {
        try {
            // Try to get from cache first
            $settings = Cache::remember('app_settings', 3600, function () {
                return AppSetting::pluck('value', 'key')->toArray();
            });

            return $settings[$key] ?? $default;
        } catch (\Exception $e) {
            Log::error('Error getting app setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Verify OTP and authenticate user.
     */
    public function verifyOtp(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|digits:10',
            'otp' => 'required|string|digits:6',
            'token' => 'nullable|string', // Push notification token
            'device_info' => 'nullable|array', // Device information
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $user = User::where('mobile', $request->mobile)->first();

            if (!$user) {
                return $this->error([], 'User not found. Please request OTP first.', 404);
            }

            // Check if OTP exists
            if (!$user->otp) {
                return $this->error([], 'No OTP found. Please request a new OTP.', 400);
            }

            // Check if OTP has expired
            if (!$user->otp_expires_at || $user->otp_expires_at->lt(now())) {
                return $this->error([], 'OTP has expired. Please request a new one.', 400);
            }

            // Verify OTP
            if ($user->otp !== $request->otp) {
                Log::warning('Invalid OTP attempt', [
                    'mobile' => $request->mobile,
                    'provided_otp' => $request->otp,
                    'stored_otp' => $user->otp
                ]);
                return $this->error([], 'Invalid OTP. Please check and try again.', 400);
            }

            DB::beginTransaction();

            // Clear OTP after successful verification
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();

            // Generate access token (2 hours)
            $tokenExpiry = now()->addHours(2);
            $token = $user->createToken('auth_token', ['*'], $tokenExpiry);

            // Generate refresh token (30 days)
            $refreshToken = Str::random(64);
            $refreshTokenExpiry = now()->addDays(30);

            // Store refresh token in the database
            $user->refresh_token = $refreshToken;
            $user->refresh_token_expires_at = $refreshTokenExpiry;
            $user->save();

            // Reload user from database to ensure we have the latest data
            $user->refresh();

            // DEBUG: Verify token consistency
            Log::info('Token Generation Debug', [
                'user_id' => $user->id,
                'generated_refresh_token' => $refreshToken,
                'saved_refresh_token' => $user->refresh_token,
                'tokens_match' => ($refreshToken === $user->refresh_token),
                'access_token' => $token->plainTextToken
            ]);

            // Register push notification token if provided
            if ($request->has('token') && !empty($request->token)) {
                $this->registerNotificationToken(
                    $user->id,
                    $request->token,
                    $user->role,
                    $request->device_info
                );
            }

            DB::commit();

            Log::info('OTP verified successfully', [
                'user_id' => $user->id,
                'mobile' => $user->mobile,
                'token_expiry' => $tokenExpiry
            ]);

            // Create response WITHOUT using ProfileResponse to avoid token conflicts
            return $this->createDirectAuthResponse(
                $user,
                $token->plainTextToken,
                $refreshToken,
                $tokenExpiry->toDateTimeString(),
                'OTP verified successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in verifyOtp', [
                'mobile' => $request->mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error([], 'Verification failed. Please try again.', 500);
        }
    }

    /**
     * Register or update a push notification token
     */
    private function registerNotificationToken($userId, $token, $userRole, $deviceInfo = null)
    {
        try {
            // Basic validation to prevent storing empty tokens
            if (empty($token)) {
                Log::warning('Attempted to register empty push notification token', [
                    'user_id' => $userId
                ]);
                return false;
            }

            Log::info('Registering push notification token', [
                'user_id' => $userId,
                'token_length' => strlen($token),
                'user_role' => $userRole
            ]);

            // Check if this token already exists
            $existingToken = NotificationToken::where('token', $token)->first();

            if ($existingToken) {
                if ($existingToken->user_id != $userId) {
                    // Token exists but belongs to a different user - reassign
                    Log::info('Token exists for different user, reassigning', [
                        'old_user_id' => $existingToken->user_id,
                        'new_user_id' => $userId
                    ]);

                    $existingToken->update([
                        'user_id' => $userId,
                        'user_role' => $userRole,
                        'device_info' => $deviceInfo ? (is_array($deviceInfo) ? json_encode($deviceInfo) : $deviceInfo) : null,
                        'is_active' => true,
                        'last_used_at' => now()
                    ]);
                } else {
                    // Token already belongs to current user - update last_used_at
                    $existingToken->update(['last_used_at' => now()]);
                }
            } else {
                // Create new token record
                NotificationToken::create([
                    'token' => $token,
                    'user_id' => $userId,
                    'user_role' => $userRole,
                    'device_info' => $deviceInfo ? (is_array($deviceInfo) ? json_encode($deviceInfo) : $deviceInfo) : null,
                    'is_active' => true,
                    'last_used_at' => now()
                ]);
            }

            Log::info('Push notification token registered successfully', [
                'user_id' => $userId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error registering push notification token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * Register a push notification token separately
     */
    public function registerToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $userId = Auth::id();
            $user = Auth::user();

            $success = $this->registerNotificationToken(
                $userId,
                $request->token,
                $user->role,
                $request->device_info
            );

            if ($success) {
                return $this->success([], 'Notification token registered successfully');
            } else {
                return $this->error([], 'Failed to register notification token', 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in registerToken endpoint', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return $this->error([], 'Failed to register notification token', 500);
        }
    }

    /**
     * Refresh the authentication token using a refresh token.
     */
    public function refreshToken(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            // Find user by refresh token
            $user = User::where('refresh_token', $request->refresh_token)
                ->where('refresh_token_expires_at', '>', now())
                ->first();

            if (!$user) {
                Log::warning('Invalid or expired refresh token attempt', [
                    'token' => substr($request->refresh_token, 0, 10) . '...'
                ]);
                return $this->error([], 'Invalid or expired refresh token.', 401);
            }

            DB::beginTransaction();

            // Revoke all existing access tokens for this user
            $user->tokens()->delete();

            // Generate a new access token (2 hours)
            $tokenExpiry = now()->addHours(2);
            $token = $user->createToken('auth_token', ['*'], $tokenExpiry);

            // Generate a new refresh token (30 days)
            $refreshToken = Str::random(64);
            $refreshTokenExpiry = now()->addDays(30);

            // Store new refresh token
            $user->refresh_token = $refreshToken;
            $user->refresh_token_expires_at = $refreshTokenExpiry;
            $user->save();

            DB::commit();

            Log::info('Token refreshed successfully', [
                'user_id' => $user->id,
                'new_token_expiry' => $tokenExpiry
            ]);

            // Return new tokens
            return $this->success([
                'auth_token' => $token->plainTextToken,
                'refresh_token' => $refreshToken,
                'token_expiry' => $tokenExpiry->toDateTimeString(),
                'is_registered' => (bool) $user->is_registered,
                'is_kyc_uploaded' => (bool) $user->is_kyc_uploaded,
                'is_kyc_completed' => (bool) $user->is_kyc_completed,
                'role' => $user->role,
            ], 'Token refreshed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Token refresh error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error([], 'Failed to refresh token. Please login again.', 500);
        }
    }

    /**
     * Register user details after OTP verification.
     */
    // public function register(AuthRequest $request)
    // {
    //     try {
    //         $user = User::where('mobile', $request->mobile)->first();

    //         if (!$user) {
    //             return $this->error([], 'User not found. Please verify OTP first.', 404);
    //         }

    //         // $user1 = User::where('email', $request->email)->first();

    //         // if ($user1) {
    //         //     return $this->error([], 'Duplicate Email Found.', 400);
    //         // }

    //         // Check if user is already registered
    //         if ($user->is_registered) {
    //             return $this->error([], 'User is already registered.', 400);
    //         }

    //         DB::beginTransaction();

    //         // Handle profile picture upload
    //         if ($request->hasFile('profile_picture')) {
    //             $path = $request->file('profile_picture')->store('profile_pictures', 'public');
    //             $user->profile_picture = $path;
    //         }

    //         // Generate a unique referral code if not already set
    //         if (empty($user->referral_code)) {
    //             $user->referral_code = $this->generateUniqueReferralCode();
    //         }

    //         // Convert reference_code to reference_user_id
    //         if ($request->filled('reference_code')) {
    //             $referrer = User::where('referral_code', $request->reference_code)->first();
    //             if ($referrer) {
    //                 $user->reference_id = $referrer->id;
    //                 Log::info('User linked to referrer', [
    //                     'user_id' => $user->id,
    //                     'referrer_id' => $referrer->id,
    //                     'reference_code' => $request->reference_code
    //                 ]);
    //             } else {
    //                 Log::warning('Invalid reference code provided', [
    //                     'user_id' => $user->id,
    //                     'reference_code' => $request->reference_code
    //                 ]);
    //             }
    //         }

    //         // Update user with registration data
    //         $updateData = $request->only([
    //             'name',
    //             'email',
    //             'gender',
    //             'dob',
    //             'address',
    //             'full_address',
    //             'street',
    //             'city',
    //             'state',
    //             'country',
    //             'postal_code',
    //             'latitude',
    //             'longitude',
    //             'terms_accepted'
    //         ]);

    //         // Add role-specific fields
    //         if ($request->role === 'vendor') {
    //             if ($request->has('business_category_id')) {
    //                 $updateData['business_category_id'] = $request->business_category_id;
    //             }
    //             if ($request->has('experience')) {
    //                 $updateData['experience'] = $request->experience;
    //             }
    //         }

    //         // Set role if provided
    //         if ($request->has('role')) {
    //             $updateData['role'] = $request->role;
    //         }

    //         // Mark as registered
    //         $updateData['is_registered'] = true;

    //         // Update user
    //         //dd($updateData);
    //         $user->update($updateData);

    //         // Register push notification token if provided
    //         if ($request->has('token') && !empty($request->token)) {
    //             $this->registerNotificationToken(
    //                 $user->id,
    //                 $request->token,
    //                 $user->role,
    //                 $request->device_info
    //             );
    //         }

    //         DB::commit();

    //         Log::info('User registered successfully', [
    //             'user_id' => $user->id,
    //             'mobile' => $user->mobile,
    //             'role' => $user->role
    //         ]);

    //         return $this->success(new ProfileResponse($user), 'User registered successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         Log::error('Registration error', [
    //             'mobile' => $request->mobile,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return $this->error([
    //             'mobile' => $request->mobile,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ], 'Registration failed. Please try again.', 500);
    //     }
    // }
    public function register(AuthRequest $request, UserRegistrationService $service)
    {
        try {
            $user = User::where('mobile', $request->mobile)->first();
            if (!$user) {
                return $this->error([], 'User not found. Please verify OTP first.', 404);
            }

            // Check if user is already registered
            if ($user->is_registered) {
                return $this->error([], 'User is already registered.', 400);
            }

            $service->register($request->all(), $user);

            if ($request->has('token') && !empty($request->token)) {
                $this->registerNotificationToken(
                    $user->id,
                    $request->token,
                    $user->role,
                    $request->device_info
                );
            }

            return $this->success(new ProfileResponse($user), 'User registered successfully.');
        } catch (\Throwable $th) {


            Log::error('Registration error', [
                'mobile' => $request->mobile,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->error([
                'mobile' => $request->mobile,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ], 'Registration failed. Please try again.', 500);
        }
    }

    /**
     * Generate a unique referral code.
     */
    private function generateUniqueReferralCode()
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $code = Str::upper(Str::random(8));
            $attempts++;

            if ($attempts >= $maxAttempts) {
                // Fallback to timestamp-based code
                $code = Str::upper(Str::random(4) . substr(time(), -4));
                break;
            }
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Create direct authentication response without ProfileResponse resource
     * This eliminates token conflicts by building response manually
     */
    private function createDirectAuthResponse($user, $accessToken, $refreshToken, $tokenExpiry, $message = 'Authentication successful')
    {
        try {
            // Build response data manually to ensure token consistency
            $responseData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'bio' => $user->bio,
                'gender' => $user->gender,
                'dob' => $user->dob ? date('Y-m-d', strtotime($user->dob)) : null,
                'role' => $user->role,
                'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
                'rating' => $user->role == 'vendor' ? number_format(\App\Models\Review::getAverageRatingForProvider($user->id), 1) : '',
                'referral_code' => $user->referral_code,
                'is_registered' => (bool) $user->name,
                'is_kyc_uploaded' => (bool) $user->is_kyc_uploaded,
                'is_kyc_completed' => (bool) $user->is_kyc_completed,
                'status' => (bool) $user->status,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,

                // GUARANTEED FRESH TOKENS
                'auth_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expiry' => $tokenExpiry,

                'address' => $user->address,
                'full_address' => $user->full_address,
                'street' => $user->street,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country,
                'postal_code' => $user->postal_code,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'business_category_name' => $user->business_category_id && $user->businessCategory ? $user->businessCategory->name : null,
                'business_category_id' => $user->business_category_id,
                'experience' => $user->experience,
                'terms_accepted' => (bool) $user->terms_accepted,
                'member_since' => $user->created_at ? date('M, Y', strtotime($user->created_at)) : null,
            ];

            // Final verification log
            Log::info('Direct Auth Response Generated', [
                'user_id' => $user->id,
                'response_refresh_token' => $responseData['refresh_token'],
                'expected_refresh_token' => $refreshToken,
                'tokens_match' => ($responseData['refresh_token'] === $refreshToken)
            ]);

            return $this->success($responseData, $message);
        } catch (\Exception $e) {
            Log::error('Error creating direct auth response', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            // Ultra-minimal fallback response
            return $this->success([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'is_registered' => (bool) $user->is_registered,
                'is_kyc_completed' => (bool) $user->is_kyc_completed,
                'auth_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expiry' => $tokenExpiry,
            ], $message);
        }
    }

    /**
     * Create authentication response with ProfileResponse (backup method)
     */
    private function createAuthResponse($user, $accessToken, $refreshToken, $tokenExpiry, $message = 'Authentication successful')
    {
        try {
            // Get base user data
            $userResource = new ProfileResponse($user);
            $userData = $userResource->toArray(request());

            // FORCE override token fields with fresh data
            $userData['auth_token'] = $accessToken;
            $userData['refresh_token'] = $refreshToken;
            $userData['token_expiry'] = $tokenExpiry;

            // Debug log to verify tokens
            Log::info('Auth Response Tokens', [
                'user_id' => $user->id,
                'generated_refresh_token' => $refreshToken,
                'database_refresh_token' => $user->refresh_token,
                'response_refresh_token' => $userData['refresh_token'],
                'tokens_match' => ($refreshToken === $userData['refresh_token'])
            ]);

            return $this->success($userData, $message);
        } catch (\Exception $e) {
            Log::error('Error creating auth response', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            // Fallback to direct response
            return $this->createDirectAuthResponse($user, $accessToken, $refreshToken, $tokenExpiry, $message);
        }
    }

    /**
     * Logout user by revoking tokens
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            // Revoke current access token
            $request->user()->currentAccessToken()->delete();

            // Optionally clear refresh token
            $user->refresh_token = null;
            $user->refresh_token_expires_at = null;
            $user->save();

            // Deactivate notification tokens
            NotificationToken::where('user_id', $user->id)
                ->update(['is_active' => false]);

            Log::info('User logged out successfully', [
                'user_id' => $user->id
            ]);

            return $this->success([], 'Logged out successfully.');
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return $this->error([], 'Logout failed.', 500);
        }
    }

    public function updateRole(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|digits:10',
            'role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $user = User::where(['mobile' => $request->mobile])->firstOrFail();

            if ($user) {
                $user->role = strtolower($request->role);
                $user->save();
            }

            Log::info('User role updated successfully', [
                'mobile' => $user->mobile,
                'role' => $request->role, // Remove this in production
            ]);
            return $this->success([], 'Role updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error in sendOtp', [
                'mobile' => $request->mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error([], 'Failed to update role.', 500);
        }
    }
}
