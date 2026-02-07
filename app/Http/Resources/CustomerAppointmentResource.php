<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // IMPORTANT: Parse dates correctly to ensure proper comparison in frontend
        $date = null;
        $startTime = null;
        $endTime = null;

        // Make sure we parse the date correctly
        if ($this->date) {
            // Convert to user's timezone if needed
            $date = Carbon::parse($this->date);
        }

        if ($this->start_time) {
            $startTime = Carbon::parse($this->start_time);
        }

        if ($this->end_time) {
            $endTime = Carbon::parse($this->end_time);
        }

        // Format dates for display
        $formattedDate = $date ? $date->format('M d, Y') : null;
        $formattedTime = null;

        if ($startTime && $endTime) {
            $formattedTime = $startTime->format('g:i A').' - '.$endTime->format('g:i A');
        }

        // Format service details
        $serviceData = null;
        if ($this->service) {
            $serviceData = [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'duration' => $this->service->duration,
                'price' => $this->service->price,
                'formatted_price' => $this->service->formatted_price ?? ('₹'.number_format($this->service->price, 2)),
            ];
        }

        // Format payment details
        $paymentData = null;
        if ($this->payment) {
            $paymentData = [
                'id' => $this->payment->id,
                'transaction_id' => $this->payment->transaction_id,
                'payment_method' => $this->payment->payment_method,
                'amount' => $this->payment->amount,
                'formatted_amount' => $this->payment->formatted_amount ?? ('₹'.number_format($this->payment->amount, 2)),
                'status' => $this->payment->status,
                'created_at' => $this->payment->created_at,
            ];
        }

        // Calculate if appointment is upcoming or past for easier frontend filtering
        // We'll include a simple date value for frontend comparison
        $simpleDate = $date ? $date->toDateString() : null; // 'YYYY-MM-DD' format
        $today = Carbon::today()->toDateString();

        $isUpcoming = false;
        $isPast = false;

        if ($simpleDate) {
            $isUpcoming = $simpleDate >= $today;
            $isPast = $simpleDate < $today;
        }

        return [
            'id' => $this->id,
            'date' => $this->date,
            'formatted_date' => $formattedDate,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'formatted_time' => $formattedTime,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,

            // Related data
            'service' => $serviceData,
            'payment' => $paymentData,

            // Add simple date for easier comparison
            'simple_date' => $simpleDate,

            // Calculated flags for frontend filtering
            'is_upcoming' => $isUpcoming,
            'is_past' => $isPast,

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
