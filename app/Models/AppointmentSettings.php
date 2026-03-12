<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'appointment_duration',
        'buffer_time',
        'advance_booking_days',
        'max_bookings_per_day',
        'is_online_booking_enabled',
        'auto_confirm_appointments',
        'appointment_modes',
        'payment_methods', // Added new field for payment methods
    ];

    protected $casts = [
        'appointment_duration' => 'integer',
        'buffer_time' => 'integer',
        'advance_booking_days' => 'integer',
        'max_bookings_per_day' => 'integer',
        'is_online_booking_enabled' => 'boolean',
        'auto_confirm_appointments' => 'boolean',
        'appointment_modes' => 'json',
        'payment_methods' => 'json', // Cast to JSON
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the maximum date that can be booked in advance.
     */
    public function getMaxBookingDateAttribute()
    {
        return now()->addDays($this->advance_booking_days);
    }

    /**
     * Get the formatted duration.
     */
    public function getFormattedDurationAttribute()
    {
        if ($this->appointment_duration < 60) {
            return $this->appointment_duration.' minutes';
        }

        $hours = floor($this->appointment_duration / 60);
        $minutes = $this->appointment_duration % 60;

        if ($minutes === 0) {
            return $hours.' hour'.($hours > 1 ? 's' : '');
        }

        return $hours.' hour'.($hours > 1 ? 's' : '').' '.$minutes.' minute'.($minutes > 1 ? 's' : '');
    }

    /**
     * Get the formatted buffer time.
     */
    public function getFormattedBufferTimeAttribute()
    {
        return $this->buffer_time.' minutes';
    }
}
