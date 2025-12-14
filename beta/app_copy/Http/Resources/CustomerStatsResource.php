<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Format currency amounts
        $totalSpend = $this['total_spend'] ?? 0;
        $averageSpend = $this['average_spend'] ?? 0;
        
        $formattedTotalSpend = '₹' . number_format($totalSpend, 2);
        $formattedAverageSpend = '₹' . number_format($averageSpend, 2);

        return [
            'total_appointments' => $this['total_appointments'] ?? 0,
            'completed_appointments' => $this['completed_appointments'] ?? 0,
            'cancelled_appointments' => $this['cancelled_appointments'] ?? 0,
            
            // Financial data
            'total_spend' => $totalSpend,
            'formatted_total_spend' => $formattedTotalSpend,
            'average_spend' => $averageSpend,
            'formatted_average_spend' => $formattedAverageSpend,
            
            // Service preferences
            'most_frequent_service' => $this['most_frequent_service'] ?? null,
            
            // Visit history
            'first_visit' => $this['first_visit'] ?? null,
            'last_visit' => $this['last_visit'] ?? null,
            
            // Customer relationship
            'loyalty_period' => $this['loyalty_period'] ?? 'New Client',
            'loyalty_points' => $this['loyalty_points'] ?? 0,
            
            // Engagement metrics
            'no_show_rate' => $this['no_show_rate'] ?? 0,
            'cancellation_rate' => $this['cancellation_rate'] ?? 0,
            'rebook_rate' => $this['rebook_rate'] ?? 0,
        ];
    }
}