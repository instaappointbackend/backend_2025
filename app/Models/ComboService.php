<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ComboService extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the combo service.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the services included in this combo.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'combo_service_items')
            ->withTimestamps();
    }

    /**
     * Get the appointments for this combo service.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Calculate the total duration of all services in the combo.
     */
    public function getTotalDurationAttribute()
    {
        // Ensure services are loaded
        if (!$this->relationLoaded('services')) {
            $this->load('services');
        }

        return $this->services->sum('duration');
    }

    /**
     * Calculate the total price of all services in the combo (before discount).
     */
    public function getTotalPriceAttribute()
    {
        // Ensure services are loaded
        if (!$this->relationLoaded('services')) {
            $this->load('services');
        }

        return $this->services->sum('price');
    }

    /**
     * Calculate the discounted price of the combo.
     */
    public function getDiscountedPriceAttribute()
    {
        $totalPrice = $this->total_price;
        $discount = $totalPrice * ($this->discount_percentage / 100);
        return $totalPrice - $discount;
    }

    /**
     * Format the total duration in a human-readable format.
     */
    public function getFormattedTotalDurationAttribute()
    {
        $minutes = $this->total_duration;

        if ($minutes < 60) {
            return $minutes . ' minutes';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }

        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $remainingMinutes . ' minute' . ($remainingMinutes > 1 ? 's' : '');
    }

    /**
     * Format the total price with currency symbol.
     */
    public function getFormattedTotalPriceAttribute()
    {
        return 'INR ' . number_format($this->total_price, 2);
    }

    /**
     * Format the discounted price with currency symbol.
     */
    public function getFormattedDiscountedPriceAttribute()
    {
        return 'INR ' . number_format($this->discounted_price, 2);
    }
}
