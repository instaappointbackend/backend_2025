<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedUser extends Model
{
    use HasFactory;

    protected $table = 'deleted_users';

    // JSON column
    protected $casts = [
        'data' => 'object',   // automatically converts JSON → array
    ];

    protected $fillable = [
        'data',
        'deleted_at',
    ];

    /**
     * Access data inside the JSON easily
     */
    public function getNameAttribute()
    {
        return $this->data->name ?? null;
    }

    public function getEmailAttribute()
    {
        return $this->data->email ?? null;
    }

    public function getRoleAttribute()
    {
        return $this->data->role ?? null;
    }
}
