<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'discount_percentage' => (int) $this->discount_percentage,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'is_active' => $this->is_active,
            'offer_type' => $this->offer_type,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];

        // Include service information for provider offers
        if ($this->service_id) {
            $data['service_id'] = $this->service_id;
            $data['service_name'] = $this->whenLoaded('service', function() {
                return $this->service->name;
            });
        }

        // Include admin-specific fields
        if ($this->offer_type === 'admin') {
            $data['coupon_code'] = $this->coupon_code;
            $data['usage_limit'] = $this->usage_limit;
            $data['used_count'] = $this->used_count;
            $data['is_valid'] = $this->isValid();
        }

        return $data;
    }
}