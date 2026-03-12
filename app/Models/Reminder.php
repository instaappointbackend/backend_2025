<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'reminder_date',
        'reminder_time',
        'status', // 'pending', 'completed', 'cancelled'
        'type', // 'one-time', 'recurring'
        'recurrence_pattern', // 'daily', 'weekly', 'monthly' (if recurring)
        'recurrence_end_date', // When recurring reminders should end (optional)
        'priority', // 'low', 'medium', 'high'
        'user_id', // The user who created the reminder
        'target_id', // Could be customer ID or appointment ID the reminder is about (optional)
        'target_type', // 'customer', 'appointment', etc. (optional)
        'is_read', // Boolean to mark if notification has been read
        'notification_sent', // Boolean to track if notification has been sent
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'recurrence_end_date' => 'date',
        'is_read' => 'boolean',
        'notification_sent' => 'boolean',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // For reminders associated with a customer
    public function customer()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    // For reminders associated with an appointment
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'target_id');
    }

    // Helper method to get the target based on target_type
    public function getTargetAttribute()
    {
        if ($this->target_type === 'customer') {
            return $this->customer;
        } elseif ($this->target_type === 'appointment') {
            return $this->appointment;
        }

        return null;
    }

    // Scope for upcoming reminders
    public function scopeUpcoming($query)
    {
        return $query->where('reminder_date', '>=', now())
            ->where('status', 'pending')
            ->orderBy('reminder_date');
    }

    // Scope for today's reminders
    public function scopeToday($query)
    {
        return $query->whereDate('reminder_date', now()->format('Y-m-d'))
            ->where('status', 'pending')
            ->orderBy('reminder_time');
    }

    // Scope for overdue reminders
    public function scopeOverdue($query)
    {
        return $query->where('reminder_date', '<', now()->format('Y-m-d'))
            ->where('status', 'pending')
            ->orderBy('reminder_date');
    }
}
