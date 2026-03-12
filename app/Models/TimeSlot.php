<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'is_available',
        'status',
        'blocked_until',
        'blocked_for_appointment_id',
        'blocked_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_available' => 'boolean',
        'blocked_until' => 'datetime', // Added this cast to fix the error
    ];

    // Status constants
    const STATUS_AVAILABLE = 'available';

    const STATUS_TEMPORARILY_BLOCKED = 'temporarily_blocked';

    const STATUS_BOOKED = 'booked';

    /**
     * Check if slot is truly available (not blocked or booked)
     */
    public function isTrulyAvailable()
    {
        if ($this->status === self::STATUS_AVAILABLE) {
            return true;
        }

        // Check if temporarily blocked slot has expired
        if ($this->status === self::STATUS_TEMPORARILY_BLOCKED &&
            $this->blocked_until &&
            $this->blocked_until->isPast()) {
            // Auto-release expired slot
            $this->releaseBlock();

            return true;
        }

        return false;
    }

    /**
     * Block this time slot temporarily
     */
    public function blockTemporarily($appointmentId, $durationMinutes = 30, $reason = 'payment_pending')
    {
        $this->update([
            'status' => self::STATUS_TEMPORARILY_BLOCKED,
            'blocked_until' => now()->addMinutes($durationMinutes),
            'blocked_for_appointment_id' => $appointmentId,
            'blocked_reason' => $reason,
            'is_available' => false,
        ]);
    }

    /**
     * Confirm booking (convert from temporary block to permanent booking)
     */
    public function confirmBooking()
    {
        $this->update([
            'status' => self::STATUS_BOOKED,
            'blocked_until' => null,
            'blocked_reason' => 'confirmed_booking',
            'is_available' => false,
        ]);
    }

    /**
     * Release block and make slot available again
     */
    public function releaseBlock()
    {
        $this->update([
            'status' => self::STATUS_AVAILABLE,
            'blocked_until' => null,
            'blocked_for_appointment_id' => null,
            'blocked_reason' => null,
            'is_available' => true,
        ]);
    }

    /**
     * Get the user that owns the time slot.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the appointment associated with this time slot, if any.
     */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'user_id', 'user_id')
            ->where('date', $this->date)
            ->where('start_time', $this->start_time)
            ->where('end_time', $this->end_time);
    }

    /**
     * Scope a query to only include available time slots.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query to only include truly available time slots.
     * This includes slots that are available or temporarily blocked but expired.
     */
    public function scopeTrulyAvailable($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_AVAILABLE)
                ->orWhere(function ($subQuery) {
                    $subQuery->where('status', self::STATUS_TEMPORARILY_BLOCKED)
                        ->where('blocked_until', '<', now());
                });
        });
    }

    /**
     * Scope a query to only include future time slots.
     */
    public function scopeFuture($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    /**
     * Scope a query to include slots that need cleanup (expired temporary blocks).
     */
    public function scopeExpiredBlocks($query)
    {
        return $query->where('status', self::STATUS_TEMPORARILY_BLOCKED)
            ->where('blocked_until', '<', now());
    }

    /**
     * Check if this time slot is already booked or part of any appointment.
     * This checks if the time slot has a direct appointment or if it falls within
     * the time range of any appointment for the same provider on the same date.
     */
    public function isBooked()
    {
        // Check status first
        if ($this->status === self::STATUS_BOOKED) {
            return true;
        }

        // First check if there's a direct appointment for this exact time slot
        $exactMatch = $this->appointment()->exists();

        if ($exactMatch) {
            return true;
        }

        // Convert slot times to datetime for comparison
        $slotStart = Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time->format('H:i:s'));
        $slotEnd = Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time->format('H:i:s'));

        // Find any appointment that overlaps with this time slot
        $overlappingAppointments = Appointment::where('user_id', $this->user_id)
            ->where('date', $this->date)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->get();

        foreach ($overlappingAppointments as $appointment) {
            // Convert appointment times to datetime for comparison
            $apptStart = Carbon::parse($appointment->date->format('Y-m-d').' '.$appointment->start_time->format('H:i:s'));
            $apptEnd = Carbon::parse($appointment->date->format('Y-m-d').' '.$appointment->end_time->format('H:i:s'));

            // Check if there's any overlap between the appointment and this time slot
            // Overlap occurs when one interval starts before the other ends
            if (($apptStart < $slotEnd) && ($apptEnd > $slotStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this time slot is temporarily blocked
     */
    public function isTemporarilyBlocked()
    {
        return $this->status === self::STATUS_TEMPORARILY_BLOCKED;
    }

    /**
     * Check if this time slot is permanently booked
     */
    public function isPermanentlyBooked()
    {
        return $this->status === self::STATUS_BOOKED;
    }

    /**
     * Get the time remaining until block expires (in minutes)
     */
    public function getBlockTimeRemaining()
    {
        if (! $this->isTemporarilyBlocked() || ! $this->blocked_until) {
            return 0;
        }

        return max(0, now()->diffInMinutes($this->blocked_until, false));
    }

    /**
     * Check if the temporary block has expired
     */
    public function isBlockExpired()
    {
        if (! $this->isTemporarilyBlocked() || ! $this->blocked_until) {
            return false;
        }

        return $this->blocked_until->isPast();
    }

    /**
     * Static method to clean up expired blocks
     */
    public static function cleanupExpiredBlocks()
    {
        return self::expiredBlocks()->update([
            'status' => self::STATUS_AVAILABLE,
            'blocked_until' => null,
            'blocked_for_appointment_id' => null,
            'blocked_reason' => null,
            'is_available' => true,
        ]);
    }
}
