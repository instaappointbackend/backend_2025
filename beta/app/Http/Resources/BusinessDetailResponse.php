<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessDetailResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'business_name' => $this->business_name,
            'industry'      => $this->industry,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'website'       => $this->website,
            'address'       => $this->address,
            'logo'          => $this->logo ? asset('storage/' . $this->logo) : null,
            'created_at'    => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
