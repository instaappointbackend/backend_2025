<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'formatted_date' => $this->date->format('F j, Y'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'formatted_time' => $startTime->format('g:i A').' - '.$endTime->format('g:i A'),
            'is_available' => $this->is_available,
            'is_booked' => $this->isBooked(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
