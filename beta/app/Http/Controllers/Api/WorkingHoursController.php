<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkingHoursRequest;
use App\Http\Resources\WorkingHoursResponse;
use App\Models\WorkingHours;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkingHoursController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all working hours for the authenticated user.
     */
    public function index()
    {
        $workingHours = WorkingHours::where('user_id', Auth::id())->orderBy('day_of_week')->get();
        return $this->success(WorkingHoursResponse::collection($workingHours), 'Working hours retrieved successfully.');
    }

    /**
     * Store or update working hours for a specific day.
     */
    public function store(WorkingHoursRequest $request)
    {
        // Process each day's working hours
        $data = $request->validated();
        $userId = Auth::id();
        $results = [];

        foreach ($data['days'] as $dayData) {
            // Find existing record or create new one
            $workingHours = WorkingHours::updateOrCreate(
                [
                    'user_id' => $userId,
                    'day_of_week' => $dayData['day_of_week']
                ],
                [
                    'is_working_day' => $dayData['is_working_day'],
                    'start_time' => $dayData['is_working_day'] ? $dayData['start_time'] : null,
                    'end_time' => $dayData['is_working_day'] ? $dayData['end_time'] : null,
                    'break_start' => $dayData['is_working_day'] && isset($dayData['break_start']) ? $dayData['break_start'] : null,
                    'break_end' => $dayData['is_working_day'] && isset($dayData['break_end']) ? $dayData['break_end'] : null,
                ]
            );

            $results[] = $workingHours;
        }

        return $this->success(
            WorkingHoursResponse::collection(collect($results)),
            'Working hours saved successfully.'
        );
    }

    /**
     * Update working hours for a specific day.
     */
    public function update(WorkingHoursRequest $request, $dayOfWeek)
    {
        $userId = Auth::id();
        $workingHours = WorkingHours::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingHours) {
            $workingHours = new WorkingHours();
            $workingHours->user_id = $userId;
            $workingHours->day_of_week = $dayOfWeek;
        }

        $data = $request->validated();

        $workingHours->is_working_day = $data['is_working_day'];

        if ($data['is_working_day']) {
            $workingHours->start_time = $data['start_time'];
            $workingHours->end_time = $data['end_time'];
            $workingHours->break_start = $data['break_start'] ?? null;
            $workingHours->break_end = $data['break_end'] ?? null;
        } else {
            $workingHours->start_time = null;
            $workingHours->end_time = null;
            $workingHours->break_start = null;
            $workingHours->break_end = null;
        }

        $workingHours->save();

        return $this->success(
            new WorkingHoursResponse($workingHours),
            'Working hours updated successfully.'
        );
    }

    /**
     * Copy working hours from one day to all other working days.
     */
    /**
     * Copy working hours from one day to all other working days.
     */
    public function copyToAll(Request $request, $dayOfWeek)
    {
        $userId = Auth::id();
        $sourceDay = WorkingHours::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$sourceDay) {
            return $this->error([], 'Source day not found', 404);
        }

        // Get all days for this user
        $allDays = WorkingHours::where('user_id', $userId)
            ->orderBy('day_of_week')
            ->get();

        // Loop through all days including non-working days
        foreach ($allDays as $targetDay) {
            // Skip the source day itself
            if ($targetDay->day_of_week == $dayOfWeek) {
                continue;
            }

            // Copy settings from source day to target day
            $targetDay->is_working_day = $sourceDay->is_working_day;
            $targetDay->start_time = $sourceDay->start_time;
            $targetDay->end_time = $sourceDay->end_time;
            $targetDay->break_start = $sourceDay->break_start;
            $targetDay->break_end = $sourceDay->break_end;
            $targetDay->save();
        }

        // Return updated working hours
        $workingHours = WorkingHours::where('user_id', $userId)
            ->orderBy('day_of_week')
            ->get();

        return $this->success(
            WorkingHoursResponse::collection($workingHours),
            'Working hours copied to all days successfully.'
        );
    }

    /**
     * Reset working hours to default.
     */
    public function resetToDefault()
    {
        $userId = Auth::id();

        // Default working hours (Mon-Fri, 9am-5pm, lunch 12-1pm)
        for ($day = 0; $day < 7; $day++) {
            $workingHours = WorkingHours::where('user_id', $userId)
                ->where('day_of_week', $day)
                ->first();

            if (!$workingHours) {
                $workingHours = new WorkingHours();
                $workingHours->user_id = $userId;
                $workingHours->day_of_week = $day;
            }

            // Monday to Friday are working days
            $workingHours->is_working_day = ($day > 0 && $day < 6);

            if ($workingHours->is_working_day) {
                $workingHours->start_time = '09:00';
                $workingHours->end_time = '17:00';
                $workingHours->break_start = '13:00';
                $workingHours->break_end = '14:00';
            } else {
                $workingHours->start_time = null;
                $workingHours->end_time = null;
                $workingHours->break_start = null;
                $workingHours->break_end = null;
            }

            $workingHours->save();
        }

        $workingHours = WorkingHours::where('user_id', $userId)->orderBy('day_of_week')->get();

        return $this->success(
            WorkingHoursResponse::collection($workingHours),
            'Working hours reset to default successfully.'
        );
    }
}
