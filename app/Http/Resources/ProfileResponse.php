<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'bio' => $this->bio,
            'gender' => $this->gender,
            'dob' => $this->dob ? date('Y-m-d', strtotime($this->dob)) : null,
            'role' => $this->role,
            'profile_picture' => $this->profile_picture ? asset('storage/' . $this->profile_picture) : null,
            'rating' => $this->role == 'vendor' ? number_format(Review::getAverageRatingForProvider($this->id), 1) : '',
            'referral_code' => $this->referral_code,
            'is_registered' => (bool) $this->name,
            'is_kyc_uploaded' => (bool) $this->is_kyc_uploaded,
            'is_kyc_completed' => (bool) $this->is_kyc_completed,
            'status' => (bool) $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // IMPORTANT: Don't return token fields from database
            // These will be overridden by the controller with fresh tokens
            // 'auth_token'      => null,  // Never return from database
            // 'refresh_token'   => null,  // Never return from database
            // 'token_expiry'    => null,  // Never return from database

            'address' => $this->address,
            'full_address' => $this->full_address,
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'business_category_name' => $this->business_category_id && $this->businessCategory ? $this->businessCategory->name : null,
            'business_category_id' => $this->business_category_id,
            'experience' => $this->experience,
            'terms_accepted' => (bool) $this->terms_accepted,
            'member_since' => $this->created_at ? date('M, Y', strtotime($this->created_at)) : null,
            'new_user_coupon_started_at' => $this->new_user_coupon_started_at,
            'new_user_coupon_used' => $this->new_user_coupon_used,
        ];
    }

    /**
     * Handle a failed validation attempt and return a JSON response.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
