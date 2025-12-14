<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'reminder_date' => $this->reminder_date->format('Y-m-d'),
            'reminder_time' => $this->reminder_time,
            'status' => $this->status,
            'type' => $this->type,
            'recurrence_pattern' => $this->when($this->type === 'recurring', $this->recurrence_pattern),
            'recurrence_end_date' => $this->when($this->recurrence_end_date, $this->recurrence_end_date?->format('Y-m-d')),
            'priority' => $this->priority,
            'target_id' => $this->target_id,
            'target_type' => $this->target_type,
            'is_read' => $this->is_read,
            'notification_sent' => $this->notification_sent,
            'is_overdue' => $this->reminder_date < now()->format('Y-m-d'),
            'is_today' => $this->reminder_date->format('Y-m-d') === now()->format('Y-m-d'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
