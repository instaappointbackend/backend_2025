<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentSettingsRequest;
use App\Http\Resources\AppointmentSettingsResponse;
use App\Models\AppointmentSettings;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class AppointmentSettingsController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get the appointment settings for the authenticated user.
     */
    public function index()
    {
        $settings = AppointmentSettings::where('user_id', Auth::id())->first();

        if (! $settings) {
            $settings = $this->createDefaultSettings();
        }

        return $this->success(
            new AppointmentSettingsResponse($settings),
            'Appointment settings retrieved successfully.'
        );
    }

    /**
     * Update the appointment settings.
     */
    public function update(AppointmentSettingsRequest $request)
    {
        $settings = AppointmentSettings::where('user_id', Auth::id())->first();

        if (! $settings) {
            $settings = $this->createDefaultSettings();
        }

        // Ensure PhonePe is always enabled in payment methods if it exists
        $data = $request->validated();
        if (isset($data['payment_methods'])) {
            $data['payment_methods']['phonepe'] = true;
        }

        $settings->update($data);

        return $this->success(
            new AppointmentSettingsResponse($settings),
            'Appointment settings updated successfully.'
        );
    }

    /**
     * Reset settings to default.
     */
    public function resetToDefault()
    {
        $settings = AppointmentSettings::where('user_id', Auth::id())->first();

        if ($settings) {
            $settings->delete();
        }

        $settings = $this->createDefaultSettings();

        return $this->success(
            new AppointmentSettingsResponse($settings),
            'Appointment settings reset to default successfully.'
        );
    }

    /**
     * Create default settings for a user.
     */
    private function createDefaultSettings()
    {
        return AppointmentSettings::create([
            'user_id' => Auth::id(),
            'appointment_duration' => 60, // 60 minutes per appointment
            'buffer_time' => 15, // 15 minutes buffer between appointments
            'advance_booking_days' => 30, // Can book up to 30 days in advance
            'max_bookings_per_day' => 10, // Maximum 10 bookings per day
            'is_online_booking_enabled' => true, // Online booking is enabled
            'auto_confirm_appointments' => false, // Don't auto-confirm appointments
            'appointment_modes' => [
                'online_mode' => true,
                'office_visit_mode' => false,
                'home_visit_mode' => false,
                'online_mode_url' => null,
                'office_address' => null,
                'home_visit_radius' => 10,
                'home_visit_fee' => 0,
            ],
            'payment_methods' => [
                'phonepe' => true, // Always enabled by default
                'cash' => true,    // Enabled by default
                'card' => false,
                'upi' => false,
            ],
        ]);
    }
}
