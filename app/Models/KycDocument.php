<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'aadhar_number',
        'aadhar_attachment',
        'is_aadhar_verified',
        'pan_number',
        'pan_attachment',
        'is_pan_verified',
        'bank_name',
        'bank_account',
        'ifsc_code',
        'bank_attachment',
        'is_bank_verified',
        'feedback',
        'business_name',
        'business_address',
        'business_category_id',
        'business_established_date',
        'description',
        'business_logo',
        'identity_document',
        'is_business_verified', 'address', 'full_address',
        'street', 'city', 'state', 'country', 'postal_code', 'latitude', 'longitude',
    ];

    protected $casts = [
        'status' => 'boolean',
        'business_established_date' => 'date', // Cast to Date object
    ];

    /**
     * Get the user associated with the KYC document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    /**
     * Get the full storage path for attachments.
     */
    public function getAadharAttachmentUrlAttribute()
    {
        return $this->aadhar_attachment ? asset('storage/'.$this->aadhar_attachment) : null;
    }

    public function getPanAttachmentUrlAttribute()
    {
        return $this->pan_attachment ? asset('storage/'.$this->pan_attachment) : null;
    }

    public function getBankAttachmentUrlAttribute()
    {
        return $this->bank_attachment ? asset('storage/'.$this->bank_attachment) : null;
    }

    public function getBusinessLogoUrlAttribute()
    {
        return $this->business_logo ? asset('storage/'.$this->business_logo) : null;
    }

    public function getIdentityDocumentUrlAttribute()
    {
        return $this->identity_document ? asset('storage/'.$this->identity_document) : null;
    }
}
