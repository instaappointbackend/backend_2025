<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReminderRequest;
use App\Http\Resources\ReminderResponse;
use App\Models\Reminder;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    use ApiResponseTrait;


    /**
     * Get all reminders for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = Reminder::where('user_id', auth()->id());

        // Apply filters
        if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && in_array($request->priority, ['low', 'medium', 'high'])) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('type') && in_array($request->type, ['one-time', 'recurring'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('period')) {
            switch ($request->period) {
                case 'today':
                    $query->today();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'overdue':
                    $query->overdue();
                    break;
            }
        }

        // Default sorting by reminder date and time
        $reminders = $query->orderBy('reminder_date')
            ->orderBy('reminder_time')
            ->latest('created_at')
            ->get();

        return $this->success(ReminderResponse::collection($reminders), 'Reminders retrieved successfully.');
    }

    /**
     * Store a new reminder.
     */
    public function store(ReminderRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $data['is_read'] = false;
        $data['notification_sent'] = false;

        $reminder = Reminder::create($data);

        return $this->success(new ReminderResponse($reminder), 'Reminder created successfully.', 201);
    }

    /**
     * Show a single reminder.
     */
    public function show($id)
    {
        $reminder = Reminder::where('user_id', auth()->id())->find($id);

        if (!$reminder) {
            return $this->error([], 'Reminder not found', 404);
        }

        // Mark reminder as read when viewed
        if (!$reminder->is_read) {
            $reminder->update(['is_read' => true]);
        }

        return $this->success(new ReminderResponse($reminder), 'Reminder retrieved successfully.');
    }

    /**
     * Update a reminder.
     */
    public function update(ReminderRequest $request, $id)
    {
        $reminder = Reminder::where('user_id', auth()->id())->find($id);

        if (!$reminder) {
            return $this->error([], 'Reminder not found', 404);
        }

        $data = $request->validated();
        $reminder->update($data);

        return $this->success(new ReminderResponse($reminder), 'Reminder updated successfully.');
    }

    /**
     * Delete a reminder.
     */
    public function destroy($id)
    {
        $reminder = Reminder::where('user_id', auth()->id())->find($id);

        if (!$reminder) {
            return $this->error([], 'Reminder not found', 404);
        }

        $reminder->delete();

        return $this->success([], 'Reminder deleted successfully.');
    }

    /**
     * Mark a reminder as completed.
     */
    public function markAsCompleted($id)
    {
        $reminder = Reminder::where('user_id', auth()->id())->find($id);

        if (!$reminder) {
            return $this->error([], 'Reminder not found', 404);
        }

        $reminder->update(['status' => 'completed']);

        return $this->success(new ReminderResponse($reminder), 'Reminder marked as completed.');
    }

    /**
     * Mark a reminder as cancelled.
     */
    public function markAsCancelled($id)
    {
        $reminder = Reminder::where('user_id', auth()->id())->find($id);

        if (!$reminder) {
            return $this->error([], 'Reminder not found', 404);
        }

        $reminder->update(['status' => 'cancelled']);

        return $this->success(new ReminderResponse($reminder), 'Reminder marked as cancelled.');
    }

    /**
     * Get statistics about reminders.
     */
    public function stats()
    {
        $stats = [
            'total' => Reminder::where('user_id', auth()->id())->count(),
            'pending' => Reminder::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'completed' => Reminder::where('user_id', auth()->id())->where('status', 'completed')->count(),
            'today' => Reminder::where('user_id', auth()->id())->today()->count(),
            'overdue' => Reminder::where('user_id', auth()->id())->overdue()->count(),
            'high_priority' => Reminder::where('user_id', auth()->id())->where('priority', 'high')->where('status', 'pending')->count(),
        ];

        return $this->success($stats, 'Reminder statistics retrieved successfully.');
    }

    /**
     * Get reminders by target type and ID (customer or appointment).
     */
    public function getByTarget(Request $request)
    {
        $request->validate([
            'target_type' => 'required|in:customer,appointment',
            'target_id' => 'required',
        ]);

        $reminders = Reminder::where('user_id', auth()->id())
            ->where('target_type', $request->target_type)
            ->where('target_id', $request->target_id)
            ->orderBy('reminder_date')
            ->orderBy('reminder_time')
            ->get();

        return $this->success(ReminderResponse::collection($reminders), 'Target reminders retrieved successfully.');
    }


}
