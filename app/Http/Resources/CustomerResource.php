<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Format profile picture URL if it exists
        $profilePicture = $this->profile_picture
            ? asset('storage/'.$this->profile_picture)
            : null;

        // Format last visit date if it exists
        $lastVisitDate = null;
        if (! empty($this->lastAppointmentDate)) {
            $appointmentDate = Carbon::parse($this->lastAppointmentDate);
            $now = Carbon::now();

            if ($appointmentDate->isToday()) {
                $lastVisitDate = 'Today';
            } elseif ($appointmentDate->isYesterday()) {
                $lastVisitDate = 'Yesterday';
            } elseif ($appointmentDate->diffInDays($now) < 7) {
                $lastVisitDate = $appointmentDate->diffInDays($now).' days ago';
            } else {
                $lastVisitDate = $appointmentDate->format('M d, Y');
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->mobile, // Use mobile field but return as phone for frontend consistency
            'profile_picture' => $profilePicture,
            'gender' => $this->gender,
            'dob' => $this->dob ? date('Y-m-d', strtotime($this->dob)) : null,
            'address' => $this->address,
            'full_address' => $this->full_address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,

            // Customer-specific fields
            'totalVisits' => $this->when(isset($this->totalVisits), $this->totalVisits),
            'lastVisit' => $this->when(isset($this->lastVisit), $this->lastVisit ?: $lastVisitDate),
            'lastAppointmentDate' => $this->when(isset($this->lastAppointmentDate), $this->lastAppointmentDate),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'member_since' => date('M Y', strtotime($this->created_at)),
        ];
    }
}
