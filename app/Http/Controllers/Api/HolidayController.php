<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HolidayRequest;
use App\Http\Resources\HolidayResponse;
use App\Models\Holiday;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all holidays.
     */
    public function index()
    {
        $holidays = Holiday::where('user_id', Auth::id())
            ->orderBy('date')
            ->get();

        return $this->success(HolidayResponse::collection($holidays), 'Holidays retrieved successfully.');
    }

    /**
     * Store a new holiday.
     */
    public function store(HolidayRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        // Check if a holiday already exists for this date
        $existingHoliday = Holiday::where('user_id', Auth::id())
            ->where('date', $data['date'])
            ->first();

        if ($existingHoliday) {
            return $this->error([], 'A holiday already exists for this date.', 422);
        }

        $holiday = Holiday::create($data);

        return $this->success(new HolidayResponse($holiday), 'Holiday created successfully.', 201);
    }

    /**
     * Show a specific holiday.
     */
    public function show($id)
    {
        $holiday = Holiday::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (! $holiday) {
            return $this->error([], 'Holiday not found', 404);
        }

        return $this->success(new HolidayResponse($holiday), 'Holiday retrieved successfully.');
    }

    /**
     * Update a holiday.
     */
    public function update(HolidayRequest $request, $id)
    {
        $holiday = Holiday::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (! $holiday) {
            return $this->error([], 'Holiday not found', 404);
        }

        $data = $request->validated();

        // Check if the date is being changed and if there's already a holiday on that date
        if (isset($data['date']) && $data['date'] != $holiday->date) {
            $existingHoliday = Holiday::where('user_id', Auth::id())
                ->where('date', $data['date'])
                ->where('id', '!=', $id)
                ->first();

            if ($existingHoliday) {
                return $this->error([], 'A holiday already exists for this date.', 422);
            }
        }

        $holiday->update($data);

        return $this->success(new HolidayResponse($holiday), 'Holiday updated successfully.');
    }

    /**
     * Delete a holiday.
     */
    public function destroy($id)
    {
        $holiday = Holiday::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (! $holiday) {
            return $this->error([], 'Holiday not found', 404);
        }

        $holiday->delete();

        return $this->success([], 'Holiday deleted successfully.');
    }

    /**
     * Get future holidays.
     */
    public function future()
    {
        $holidays = Holiday::where('user_id', Auth::id())
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->get();

        return $this->success(HolidayResponse::collection($holidays), 'Future holidays retrieved successfully.');
    }

    /**
     * Get recurring holidays.
     */
    public function recurring()
    {
        $holidays = Holiday::where('user_id', Auth::id())
            ->where('is_recurring', true)
            ->orderBy('date')
            ->get();

        return $this->success(HolidayResponse::collection($holidays), 'Recurring holidays retrieved successfully.');
    }
}
