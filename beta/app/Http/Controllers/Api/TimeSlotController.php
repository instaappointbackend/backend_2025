<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeSlotRequest;
use App\Http\Resources\TimeSlotResponse;
use App\Models\Appointment;
use App\Models\AppointmentSettings;
use App\Models\Service;
use App\Models\ComboService;
use App\Models\TimeSlot;
use App\Models\WorkingHours;
use App\Services\TimeSlotBlockingService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeSlotController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get time slots for a specific date.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'service_id' => 'sometimes|exists:services,id',
            'combo_service_id' => 'sometimes|exists:combo_services,id',
        ]);

        $date = $request->input('date');
        $serviceId = $request->input('service_id');
        $comboServiceId = $request->input('combo_service_id');
        $userId = Auth::id();

        // Auto-cleanup expired slots before showing available slots
        $timeSlotService = new TimeSlotBlockingService();
        $timeSlotService->cleanupExpiredSlots();

        // Get time slots for the requested date
        $timeSlots = TimeSlot::where('user_id', $userId)
            ->where('date', $date)
            ->orderBy('start_time')
            ->get()
            ->filter(function ($slot) {
                return $slot->isTrulyAvailable(); // Use our new method
            });

        // If a service ID is provided, filter slots that have enough consecutive availability
        if ($serviceId || $comboServiceId) {
            $serviceDuration = 0;

            if ($serviceId) {
                $service = Service::findOrFail($serviceId);
                $serviceDuration = $service->duration;
            } elseif ($comboServiceId) {
                $comboService = ComboService::with('services')->findOrFail($comboServiceId);
                $serviceDuration = $comboService->total_duration;
            }

            // Get settings to determine slot duration
            $settings = AppointmentSettings::where('user_id', $userId)->first();
            $slotDuration = $settings ? $settings->appointment_duration : 30; // Default to 30 min

            // Calculate how many consecutive slots are needed
            $slotsNeeded = ceil($serviceDuration / $slotDuration);

            // If more than one slot is needed, filter to only show valid starting slots
            if ($slotsNeeded > 1) {
                $filteredSlots = collect();

                foreach ($timeSlots as $index => $slot) {
                    // Skip slots that are not available or already booked
                    if (!$slot->is_available || $slot->isBooked()) {
                        continue;
                    }

                    // Check if there are enough consecutive available slots
                    $hasEnoughSlots = true;
                    $currentSlotTime = Carbon::parse($slot->start_time);

                    for ($i = 1; $i < $slotsNeeded; $i++) {
                        $nextSlotTime = (clone $currentSlotTime)->addMinutes($slotDuration);
                        $nextSlotExists = false;

                        // Look for the next consecutive slot
                        foreach ($timeSlots as $nextSlot) {
                            $nextSlotStartTime = Carbon::parse($nextSlot->start_time);

                            if ($nextSlotStartTime->eq($nextSlotTime) &&
                                $nextSlot->is_available &&
                                !$nextSlot->isBooked()) {
                                $nextSlotExists = true;
                                break;
                            }
                        }

                        if (!$nextSlotExists) {
                            $hasEnoughSlots = false;
                            break;
                        }

                        $currentSlotTime = $nextSlotTime;
                    }

                    if ($hasEnoughSlots) {
                        $filteredSlots->push($slot);
                    }
                }

                $timeSlots = $filteredSlots;
            }
        }

        return $this->success(
            TimeSlotResponse::collection($timeSlots),
            'Time slots retrieved successfully.'
        );
    }

    /**
     * Generate time slots for a date based on working hours.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'interval' => 'sometimes|integer|min:15|max:120', // in minutes
            'buffer_time' => 'sometimes|integer|min:0|max:60', // in minutes
        ]);

        $date = $request->input('date');
        $interval = $request->input('interval', 30); // Default 30 minutes
        $bufferTime = $request->input('buffer_time', 0); // Default 0 minutes
        $userId = Auth::id();

        // Get date's day of week (0 = Sunday, 1 = Monday, etc.)
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Get working hours for that day
        $workingHours = WorkingHours::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingHours || !$workingHours->is_working_day) {
            return $this->error([], 'This day is not a working day.', 422);
        }

        // Delete existing time slots for this date
        TimeSlot::where('user_id', $userId)
            ->where('date', $date)
            ->delete();

        // Convert times to minutes from midnight for easier calculation
        $startMinutes = $this->timeToMinutes($workingHours->start_time);
        $endMinutes = $this->timeToMinutes($workingHours->end_time);

        $breakStartMinutes = null;
        $breakEndMinutes = null;

        if ($workingHours->break_start && $workingHours->break_end) {
            $breakStartMinutes = $this->timeToMinutes($workingHours->break_start);
            $breakEndMinutes = $this->timeToMinutes($workingHours->break_end);
        }

        $slots = [];
        $current = $startMinutes;

        // Total slot duration including buffer
        $slotDuration = $interval + $bufferTime;

        while ($current + $interval <= $endMinutes) {
            $slotStart = $current;
            $slotEnd = $current + $interval;

            // Skip slots that overlap with break time
            $overlapsBreak = false;
            if ($breakStartMinutes !== null && $breakEndMinutes !== null) {
                // Check if slot starts during break
                if ($slotStart >= $breakStartMinutes && $slotStart < $breakEndMinutes) {
                    $overlapsBreak = true;
                }
                // Check if slot ends during break
                if ($slotEnd > $breakStartMinutes && $slotEnd <= $breakEndMinutes) {
                    $overlapsBreak = true;
                }
                // Check if slot encompasses break
                if ($slotStart <= $breakStartMinutes && $slotEnd >= $breakEndMinutes) {
                    $overlapsBreak = true;
                }
            }

            if (!$overlapsBreak) {
                $slot = new TimeSlot();
                $slot->user_id = $userId;
                $slot->date = $date;
                $slot->start_time = $this->minutesToTime($slotStart);
                $slot->end_time = $this->minutesToTime($slotEnd);
                $slot->is_available = true;
                $slot->save();

                $slots[] = $slot;
            }

            // If we're at break end, jump to after break
            if ($breakEndMinutes !== null && $current < $breakEndMinutes && $current + $slotDuration >= $breakEndMinutes) {
                $current = $breakEndMinutes;
            } else {
                // Move to next slot position including buffer time
                $current += $slotDuration;
            }
        }

        return $this->success(
            TimeSlotResponse::collection(collect($slots)),
            'Time slots generated successfully.'
        );
    }

    /**
     * Update availability for a specific time slot.
     */
    public function update(TimeSlotRequest $request, $id)
    {
        $timeSlot = TimeSlot::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$timeSlot) {
            return $this->error([], 'Time slot not found', 404);
        }

        // Check if there's an appointment for this time slot
        $hasAppointment = $timeSlot->isBooked();

        if ($hasAppointment && !$request->input('is_available')) {
            return $this->error([], 'Cannot block a time slot with an existing appointment.', 422);
        }

        $timeSlot->is_available = $request->input('is_available');
        $timeSlot->save();

        return $this->success(
            new TimeSlotResponse($timeSlot),
            'Time slot updated successfully.'
        );
    }

    /**
     * Batch update time slots.
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'time_slot_ids' => 'required|array',
            'time_slot_ids.*' => 'required|integer|exists:time_slots,id',
            'is_available' => 'required|boolean',
        ]);

        $timeSlotIds = $request->input('time_slot_ids');
        $isAvailable = $request->input('is_available');
        $userId = Auth::id();

        // Get time slots that belong to the user
        $timeSlots = TimeSlot::where('user_id', $userId)
            ->whereIn('id', $timeSlotIds)
            ->get();

        if (count($timeSlots) !== count($timeSlotIds)) {
            return $this->error([], 'Some time slots were not found or do not belong to you.', 422);
        }

        // If blocking slots, check for existing appointments
        if (!$isAvailable) {
            foreach ($timeSlots as $timeSlot) {
                if ($timeSlot->isBooked()) {
                    return $this->error([], 'Cannot block time slots with existing appointments.', 422);
                }
            }
        }

        // Update all time slots
        TimeSlot::where('user_id', $userId)
            ->whereIn('id', $timeSlotIds)
            ->update(['is_available' => $isAvailable]);

        $updatedTimeSlots = TimeSlot::where('user_id', $userId)
            ->whereIn('id', $timeSlotIds)
            ->get();

        return $this->success(
            TimeSlotResponse::collection($updatedTimeSlots),
            'Time slots updated successfully.'
        );
    }

    /**
     * Helper method to convert time string (HH:MM) to minutes from midnight.
     */
    private function timeToMinutes($timeString)
    {
        if (!$timeString) {
            return 0;
        }

        // Parse time using Carbon for more robust handling
        try {
            $carbon = Carbon::parse($timeString);
            return $carbon->hour * 60 + $carbon->minute;
        } catch (\Exception $e) {
            \Log::error('Error parsing time: ' . $timeString . ', Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Helper method to convert minutes from midnight to time string (HH:MM).
     */
    private function minutesToTime($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
}
