<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'service' => $this->when($this->service, function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
            'combo_service' => $this->when($this->combo_service_id, function () {
                return $this->comboService ? [
                    'id' => $this->comboService->id,
                    'name' => $this->comboService->name,
                ] : null;
            }),
            'provider' => [
                'id' => $this->provider->id,
                'name' => $this->provider->name,
                'profile_image' => $this->provider->profile_image,
            ],
            'user' => $this->when(! $this->is_anonymous, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_image' => $this->user->profile_image,
                ];
            }, function () {
                return [
                    'id' => null,
                    'name' => 'Anonymous User',
                    'profile_image' => null,
                ];
            }),
            'rating' => (float) $this->rating,
            'review_text' => $this->review_text,
            'status' => $this->status,
            'is_anonymous' => (bool) $this->is_anonymous,
            'has_response' => ! is_null($this->review_response),
            'review_response' => $this->review_response,
            'response_at' => $this->response_at ? Carbon::parse($this->response_at)->format('Y-m-d H:i:s') : null,
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
            'time_ago' => Carbon::parse($this->created_at)->diffForHumans(),
        ];
    }
}
