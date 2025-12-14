<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'read_at',
        'ip_address',
        'user_agent',
        'user_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that this contact message belongs to (if any).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope a query to only include read messages.
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope a query to only include replied messages.
     */
    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    /**
     * Scope a query to only include spam messages.
     */
    public function scopeSpam($query)
    {
        return $query->where('status', 'spam');
    }

    /**
     * Scope a query to only include messages from authenticated users.
     */
    public function scopeFromUsers($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope a query to only include messages from guests.
     */
    public function scopeFromGuests($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead()
    {
        $this->status = 'read';
        $this->read_at = now();
        return $this->save();
    }

    /**
     * Mark the message as replied.
     */
    public function markAsReplied()
    {
        $this->status = 'replied';
        return $this->save();
    }

    /**
     * Mark the message as spam.
     */
    public function markAsSpam()
    {
        $this->status = 'spam';
        return $this->save();
    }

    /**
     * Check if the contact message is from an authenticated user.
     */
    public function isFromAuthenticatedUser()
    {
        return !is_null($this->user_id);
    }

    /**
     * Get the user's name (if available) or the name provided in the form.
     */
    public function getSenderName()
    {
        if ($this->isFromAuthenticatedUser() && $this->user) {
            return $this->user->name;
        }
        
        return $this->name;
    }

    /**
     * Get the user's email (if available) or the email provided in the form.
     */
    public function getSenderEmail()
    {
        if ($this->isFromAuthenticatedUser() && $this->user) {
            return $this->user->email;
        }
        
        return $this->email;
    }
}