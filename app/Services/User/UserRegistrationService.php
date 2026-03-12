<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRegistrationService
{
    public function register(array $data, ?User $user = null): User
    {
        DB::beginTransaction();

        try {
            // Create user if not exists
            if (! $user) {
                $user = new User;
            }

            /* Profile Picture */
            if (! empty($data['profile_picture']) && $data['profile_picture'] instanceof UploadedFile) {
                $user->profile_picture = $data['profile_picture']
                    ->store('profile_pictures', 'public');
            }

            /* Referral Code */
            if (empty($user->referral_code)) {
                $user->referral_code = $this->generateUniqueReferralCode();
            }

            /* Reference Code */
            if (! empty($data['reference_code'])) {
                $referrer = User::where('referral_code', $data['reference_code'])->first();
                if ($referrer) {
                    $user->reference_id = $referrer->id;
                }
            }

            /* Base Fields */
            $updateData = collect($data)->only([
                'name',
                'email',
                'mobile',
                'gender',
                'dob',
                'address',
                'full_address',
                'street',
                'city',
                'state',
                'country',
                'postal_code',
                'latitude',
                'longitude',
                'terms_accepted',
                'role',
            ])->toArray();

            /* Vendor Fields */
            if (($data['role'] ?? null) === 'vendor') {
                $updateData['business_category_id'] = $data['business_category_id'] ?? null;
                $updateData['experience'] = $data['experience'] ?? null;
            }

            $updateData['is_registered'] = true;

            $user->fill($updateData);
            $user->save();

            DB::commit();

            return $user;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
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
                $code = Str::upper(Str::random(4).substr(time(), -4));
                break;
            }
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
