<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'reminder_minutes',
        'recipient_type',
        'user_id',
        'sent_at',
        'notification_data',
        'delivery_success',
        'delivery_error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'notification_data' => 'json',
        'delivery_success' => 'boolean',
    ];

    // Recipient types
    const RECIPIENT_CLIENT = 'client';

    const RECIPIENT_PROVIDER = 'provider';

    /**
     * Get the appointment that owns the reminder.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the user (recipient) for this reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if reminder was already sent for specific appointment, interval, and recipient
     */
    public static function wasReminderSent(int $appointmentId, int $minutes, string $recipientType, int $userId): bool
    {
        return self::where('appointment_id', $appointmentId)
            ->where('reminder_minutes', $minutes)
            ->where('recipient_type', $recipientType)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Mark reminder as sent
     */
    public static function markReminderSent(
        int $appointmentId,
        int $minutes,
        string $recipientType,
        int $userId,
        ?array $notificationData = null,
        bool $success = true,
        ?string $error = null
    ): self {
        return self::create([
            'appointment_id' => $appointmentId,
            'reminder_minutes' => $minutes,
            'recipient_type' => $recipientType,
            'user_id' => $userId,
            'sent_at' => now(),
            'notification_data' => $notificationData,
            'delivery_success' => $success,
            'delivery_error' => $error,
        ]);
    }
}
