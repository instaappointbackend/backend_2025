<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\ProfileResponse;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get the current user's profile.
     */
    public function getProfile()
    {
        $user = Auth::user();

        return $this->success(new ProfileResponse($user), 'Profile retrieved successfully.');
    }

    /**
     * Edit profile information.
     * Accepts personal and business info (e.g., business_name) as well as profile picture.
     */
    public function updateProfile(ProfileRequest $request)
    {
        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {

            // Update profile image
            if ($request->hasFile('profile_picture')) {

                // Delete old image (extra safety)
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                $user->profile_picture = $request
                    ->file('profile_picture')
                    ->store('profile_pictures', 'public');
            }

            // Update other fields
            $user->fill($request->safe()->except('profile_picture'));

            $user->save();
        });
        $user->refresh();

        return $this->success(new ProfileResponse($user), 'Profile updated successfully.');
    }

    /**
     * Delete the current user's account.
     * This will permanently delete the user and all associated data.
     */
    public function deleteAccount()
    {
        try {
            $user = Auth::user();

            DB::beginTransaction();

            // Log the deletion attempt
            Log::info('User account deletion initiated', [
                'user_id' => $user->id,
                'mobile' => $user->mobile,
                'role' => $user->role,
            ]);

            // Delete user's payout requests first (foreign key constraint)
            if (method_exists($user, 'payoutRequests')) {
                $user->payoutRequests()->delete();
            }

            // Delete user's bank accounts
            if (method_exists($user, 'bankAccounts')) {
                $user->bankAccounts()->delete();
            }

            // Delete user's appointments
            if (method_exists($user, 'appointments')) {
                $user->appointments()->delete();
            }

            // Delete user's services (if vendor)
            if (method_exists($user, 'services')) {
                $user->services()->delete();
            }

            // Delete user's combo services (if vendor)
            if (method_exists($user, 'comboServices')) {
                $user->comboServices()->delete();
            }

            // Delete user's team members (if vendor)
            if (method_exists($user, 'teamMembers')) {
                $user->teamMembers()->delete();
            }

            // Delete user's blogs
            if (method_exists($user, 'blogs')) {
                $user->blogs()->delete();
            }

            // Delete user's reviews
            if (method_exists($user, 'reviews')) {
                $user->reviews()->delete();
            }

            // Delete user's notifications (using user_id field)
            \App\Models\Notification::where('user_id', $user->id)->delete();

            // Delete user's notification tokens
            \App\Models\NotificationToken::where('user_id', $user->id)->delete();

            // Delete user's locations
            if (method_exists($user, 'userLocations')) {
                $user->userLocations()->delete();
            }

            // Delete user's reminders
            if (method_exists($user, 'reminders')) {
                $user->reminders()->delete();
            }

            // Delete user's working hours
            if (method_exists($user, 'workingHours')) {
                $user->workingHours()->delete();
            }

            // Delete user's holidays
            if (method_exists($user, 'holidays')) {
                $user->holidays()->delete();
            }

            // Delete user's offers
            if (method_exists($user, 'offers')) {
                $user->offers()->delete();
            }

            // Delete user's contacts
            if (method_exists($user, 'contacts')) {
                $user->contacts()->delete();
            }

            // Delete user's KYC documents
            if (method_exists($user, 'kycDocument')) {
                $user->kycDocument()->delete();
            }

            // Revoke all user tokens
            $user->tokens()->delete();

            // Delete profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Finally, delete the user
            $user->delete();

            DB::commit();

            Log::info('User account deleted successfully', [
                'user_id' => $user->id,
                'mobile' => $user->mobile,
            ]);

            return $this->success([], 'Account deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting user account', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error([], 'Failed to delete account. Please try again.', 500);
        }
    }
}
