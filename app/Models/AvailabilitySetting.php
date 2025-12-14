<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilitySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'type', 'day', 'start_time', 'end_time', 'slot_duration', 'visit_type', 'price'
    ];

    const TYPE_SLOT = 'slot';
    const TYPE_HOLIDAY = 'holiday';
    const TYPE_BREAK = 'break';

    const VISIT_TYPE_PHYSICAL = 'physical';
    const VISIT_TYPE_VIRTUAL = 'virtual';

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
