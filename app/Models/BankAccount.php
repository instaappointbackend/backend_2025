<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_holder_name',
        'account_number',
        'bank_name',
        'ifsc_code',
        'account_type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the bank account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get masked account number for display.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        return substr($this->account_number, 0, 4).'••••'.substr($this->account_number, -4);
    }

    /**
     * Get formatted account type.
     */
    public function getFormattedAccountTypeAttribute(): string
    {
        return ucfirst($this->account_type);
    }
}
