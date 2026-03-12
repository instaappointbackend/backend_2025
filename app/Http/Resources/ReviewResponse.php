<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResponse extends JsonResource
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
            'user_id' => $this->user_id,
            'vendor_id' => $this->vendor_id,
            'service_id' => $this->service_id,
            'appointment_id' => $this->appointment_id,
            'rating' => (float) $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'is_verified' => (bool) $this->is_verified,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Include vendor details if loaded
            'vendor' => $this->whenLoaded('vendor', function () {
                return [
                    'id' => $this->vendor->id,
                    'name' => $this->vendor->name,
                    'profile_picture' => $this->vendor->profile_picture ? asset('storage/'.$this->vendor->profile_picture) : null,
                ];
            }),

            // Include user details if loaded
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_picture' => $this->user->profile_picture ? asset('storage/'.$this->user->profile_picture) : null,
                ];
            }),

            // Include service details if loaded
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
        ];
    }
}
