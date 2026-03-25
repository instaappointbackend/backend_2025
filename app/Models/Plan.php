<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'original_price',
        'discounted_price',
        'discount',
        'duration',
        'badge',
        'tagline',
        'type'
    ];

    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }
}
