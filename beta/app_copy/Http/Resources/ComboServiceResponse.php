<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComboServiceResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'services' => ServiceResponse::collection($this->whenLoaded('services')),
            'total_duration' => $this->total_duration,
            'formatted_total_duration' => $this->formatted_total_duration,
            'total_price' => $this->total_price,
            'formatted_total_price' => $this->formatted_total_price,
            'discount_percentage' => $this->discount_percentage,
            'discounted_price' => $this->discounted_price,
            'formatted_discounted_price' => $this->formatted_discounted_price,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
