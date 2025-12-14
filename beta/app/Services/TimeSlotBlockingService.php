<?php
namespace App\Services;

use App\Models\TimeSlot;
use App\Models\Appointment;
use App\Models\AppointmentSettings;
use App\Models\Service;
use App\Models\ComboService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimeSlotBlockingService
{
    /**
     * Block time slots temporarily for payment processing
     */
    public function blockTimeSlotsTemporarily($timeSlotIds, $appointmentId, $durationMinutes = 30)
    {
        $blockedSlots = [];

        foreach ($timeSlotIds as $slotId) {
            $timeSlot = TimeSlot::find($slotId);

            if (!$timeSlot) {
                throw new \Exception("Time slot {$slotId} not found");
            }

            if (!$timeSlot->isTrulyAvailable()) {
                throw new \Exception("Time slot {$slotId} is not available");
            }

            $timeSlot->blockTemporarily($appointmentId, $durationMinutes, 'payment_pending');
            $blockedSlots[] = $timeSlot;
        }

        return $blockedSlots;
    }

    /**
     * Confirm time slot booking after successful payment
     */
    public function confirmTimeSlotBooking($appointmentId)
    {
        $timeSlots = TimeSlot::where('blocked_for_appointment_id', $appointmentId)
            ->where('status', TimeSlot::STATUS_TEMPORARILY_BLOCKED)
            ->get();

        foreach ($timeSlots as $timeSlot) {
            $timeSlot->confirmBooking();
        }

        Log::info("Confirmed booking for {$timeSlots->count()} time slots for appointment {$appointmentId}");
    }

    /**
     * Release time slots for an appointment
     */
    public function releaseTimeSlotsForAppointment($appointmentId)
    {
        $timeSlots = TimeSlot::where('blocked_for_appointment_id', $appointmentId)
            ->whereIn('status', [TimeSlot::STATUS_TEMPORARILY_BLOCKED, TimeSlot::STATUS_BOOKED])
            ->get();

        foreach ($timeSlots as $timeSlot) {
            $timeSlot->releaseBlock();
        }

        Log::info("Released {$timeSlots->count()} time slots for appointment {$appointmentId}");
    }

    /**
     * Cleanup expired temporarily blocked slots
     */
    public function cleanupExpiredSlots()
    {
        $expiredSlots = TimeSlot::where('status', TimeSlot::STATUS_TEMPORARILY_BLOCKED)
            ->where('blocked_until', '<', now())
            ->get();

        $cleanedCount = 0;
        foreach ($expiredSlots as $slot) {
            // Also cleanup the draft appointment if it exists
            if ($slot->blocked_for_appointment_id) {
                $appointment = Appointment::where('id', $slot->blocked_for_appointment_id)
                    ->whereIn('status', [Appointment::STATUS_DRAFT, Appointment::STATUS_PAYMENT_PENDING])
                    ->first();

                if ($appointment) {
                    // Delete payment record if exists
                    $appointment->payment()->delete();
                    // Delete the appointment
                    $appointment->delete();
                }
            }

            $slot->releaseBlock();
            $cleanedCount++;
        }

        if ($cleanedCount > 0) {
            Log::info("Cleaned up {$cleanedCount} expired time slots");
        }

        return $cleanedCount;
    }
}
