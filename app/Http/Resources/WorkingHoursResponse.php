<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkingHoursResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->getDayName(),
            'is_working_day' => $this->is_working_day,
            'start_time' => date('H:i',strtotime($this->start_time)),
            'end_time' => date('H:i',strtotime($this->end_time)),
            'break_start' => date('H:i',strtotime($this->break_start)),
            'break_end' => date('H:i',strtotime($this->break_end)),
            'has_break' => ($this->break_start && $this->break_end),
        ];
    }

    /**
     * Get the day name from day of week number.
     */
    private function getDayName()
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }
}