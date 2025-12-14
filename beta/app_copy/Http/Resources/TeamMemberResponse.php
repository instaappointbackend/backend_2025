<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'mobile'          => $this->mobile,
            'bio'             => $this->bio,
            'gender'          => $this->gender,
            'dob'             => date('d-m-Y',strtotime($this->dob)),
            'role'            => $this->role,
            'profile_picture' => $this->profile_picture ? asset('storage/' . $this->profile_picture) : null,
            'rating'          => $this->rating ?? null,
            'referral_code'   => $this->referral_code,
            'is_registered'   => (bool) $this->name,
            'is_kyc_completed'=> (bool) $this->is_kyc_completed,
            'status'            => (bool) $this->status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'auth_token'      => $this->auth_token,
            'address'      => $this->address,
            'full_address'      => $this->full_address,
            'street'      => $this->street,
            'city'      => $this->city,
            'state'      => $this->state,
            'country'      => $this->country,
            'postal_code'      => $this->postal_code,
            'latitude'      => $this->latitude,
            'longitude'      => $this->longitude,
            'member_since'      => date('m Y',strtotime($this->created_at)),
        ];
    }
}
