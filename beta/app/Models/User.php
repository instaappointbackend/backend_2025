<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'mobile', 'bio', 'otp',
        'otp_expires_at', 'gender', 'profile_picture',
        'dob', 'role', 'role_id', 'status', 'referral_code', 'reference_code',
        'reference_id', 'vendor_id',
        'is_registered', 'is_kyc_uploaded', 'is_kyc_completed', 'address', 'full_address',
        'street', 'city', 'state', 'country', 'postal_code', 'latitude', 'longitude',
        'business_category_id', 'experience', 'terms_accepted',
        'refresh_token', 'refresh_token_expires_at'
    ];

    protected $hidden = ['password', 'remember_token', 'refresh_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'dob' => 'date',
        'is_kyc_completed' => 'boolean',
        'status' => 'boolean',
        'terms_accepted' => 'boolean',
    ];

    /**
     * Get the role that owns the user.
     */
    public function userRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->userRole && $this->userRole->name === $role;
        }

        return $role->intersect([$this->userRole])->count() > 0;
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission($permission)
    {
        return $this->userRole && $this->userRole->hasPermission($permission);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin' || ($this->userRole && $this->userRole->name === 'admin');
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin()
    {
        return $this->userRole && $this->userRole->name === 'super_admin';
    }

    /**
     * Get all the permissions of the user's role.
     */
    public function getAllPermissions()
    {
        return $this->userRole ? $this->userRole->permissions : collect();
    }

    // EXISTING METHODS FROM THE CURRENT USER MODEL

    /**
     * Get the business type associated with the user.
     */
    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    /**
     * Get the user who referred this user.
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'reference_id');
    }

    /**
     * Get the vendor (parent user) for a team member.
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function notificationTokens()
    {
        return $this->hasMany(NotificationToken::class);
    }

    /**
     * Get all team members for a vendor.
     */
    public function teamMembers()
    {
        return $this->hasMany(User::class, 'vendor_id');
    }

    /**
     * Generate OTP and set expiry time.
     */
    public function generateOtp()
    {
        $this->otp = rand(100000, 999999);
        $this->otp = 123456;
        $this->otp_expires_at = now()->addMinutes(10);
        $this->save();
    }

    /**
     * Check if OTP is valid.
     */
    public function verifyOtp($otp)
    {
        if ($this->otp === $otp && $this->otp_expires_at->gt(now())) {
            $this->clearOtp(); // Clear OTP after successful verification
            return true;
        }
        return false;
    }

    /**
     * Clear OTP after successful verification.
     */
    public function clearOtp()
    {
        $this->otp = null;
        $this->otp_expires_at = null;
        $this->save();
    }

    /**
     * Check if refresh token is valid.
     */
    public function isRefreshTokenValid($token)
    {
        return $this->refresh_token === $token &&
            $this->refresh_token_expires_at &&
            $this->refresh_token_expires_at->gt(now());
    }

    /**
     * Get the KYC document associated with the user.
     */
    public function kycDocument()
    {
        return $this->hasOne(KycDocument::class, 'user_id');
    }

    /**
     * Scope for active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for users with verified KYC.
     */
    public function scopeVerifiedKyc($query)
    {
        return $query->where('is_kyc_completed', true);
    }

    /**
     * Scope for vendors by business type.
     */
    public function scopeByBusinessCategory($query, $businessCategoryId)
    {
        return $query->where('business_category_id', $businessCategoryId);
    }

    /**
     * Scope for vendors by experience level.
     */
    public function scopeByExperience($query, $experience)
    {
        return $query->where('experience', $experience);
    }

    /**
     * Get the working hours for the user.
     */
    public function workingHours()
    {
        return $this->hasMany(WorkingHours::class);
    }

    /**
     * Get the holidays for the user.
     */
    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    /**
     * Get the time slots for the user.
     */
    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class);
    }

    /**
     * Get the services offered by the user.
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the appointments where the user is the service provider.
     */
    public function providedAppointments()
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

    /**
     * Get the appointments where the user is the client.
     */
    public function clientAppointments()
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    /**
     * Get the appointment settings for the user.
     */
    public function appointmentSettings()
    {
        return $this->hasOne(AppointmentSettings::class);
    }

    /**
     * Get the blogs authored by the user.
     */
    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    /**
     * Get the offers created by the user.
     */
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * Get the reviews received by the vendor.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'provider_id');
    }

    /**
     * Get the reviews given by the user.
     */
    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Get the vendors favorited by the user.
     */
    public function favorites()
    {
        return $this->hasMany(UserFavorite::class, 'user_id');
    }

    /**
     * Get users who have favorited this vendor.
     */
    public function favoritedBy()
    {
        return $this->hasMany(UserFavorite::class, 'vendor_id');
    }

    /**
     * Check if the user has favorited a specific vendor.
     */
    public function hasFavorited($vendorId)
    {
        return $this->favorites()->where('vendor_id', $vendorId)->exists();
    }

    /**
     * Get the appointment statistics for the user.
     */
    public function getAppointmentStatsAttribute()
    {
        $today = now()->startOfDay();

        return [
            'upcoming' => $this->providedAppointments()
                ->where('date', '>=', $today)
                ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                ->count(),

            'confirmed' => $this->providedAppointments()
                ->where('date', '>=', $today)
                ->where('status', Appointment::STATUS_CONFIRMED)
                ->count(),

            'pending' => $this->providedAppointments()
                ->where('date', '>=', $today)
                ->where('status', Appointment::STATUS_PENDING)
                ->count(),

            'cancelled' => $this->providedAppointments()
                ->where('date', '>=', $today->subDays(30))
                ->where('status', Appointment::STATUS_CANCELLED)
                ->count(),
        ];
    }

    /**
     * Get the payout requests made by the user.
     */
    public function payoutRequests()
    {
        return $this->hasMany(PayoutRequest::class);
    }

    /**
     * Get the bank accounts owned by the user.
     */
    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Get the payments where the user is the provider.
     */
    public function providerPayments()
    {
        return $this->hasMany(Payment::class, 'provider_id');
    }

    /**
     * Get the payments made by the user.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id');
    }
}
