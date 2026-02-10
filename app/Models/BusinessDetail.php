<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'business_name', 'industry', 'email', 'phone',
        'website', 'address', 'logo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
