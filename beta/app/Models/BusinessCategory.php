<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image'
    ];

    public function kycDocuments()
    {
        return $this->hasMany(KycDocument::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
