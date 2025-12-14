<!-- resources/views/admin/settings/partials/booking.blade.php -->
<div class="tab-pane fade" id="booking-settings" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Booking Settings</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="min_booking_time" class="form-label">Minimum Booking Time (Hours)</label>
                    <input type="number" class="form-control" id="min_booking_time" name="min_booking_time" value="{{ $bookingSettings['min_booking_time'] }}" min="0">
                    <div class="form-text">Minimum time in hours before an appointment can be booked.</div>
                </div>
                
                <div class="col-md-6">
                    <label for="max_booking_days" class="form-label">Maximum Booking Days Ahead</label>
                    <input type="number" class="form-control" id="max_booking_days" name="max_booking_days" value="{{ $bookingSettings['max_booking_days'] }}" min="1">
                    <div class="form-text">How many days in advance appointments can be booked.</div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="cancel_before_hours" class="form-label">Cancellation Time (Hours)</label>
                    <input type="number" class="form-control" id="cancel_before_hours" name="cancel_before_hours" value="{{ $bookingSettings['cancel_before_hours'] }}" min="0">
                    <div class="form-text">How many hours before an appointment it can be cancelled.</div>
                </div>
                
                <div class="col-md-6">
                    <label for="reschedule_before_hours" class="form-label">Reschedule Time (Hours)</label>
                    <input type="number" class="form-control" id="reschedule_before_hours" name="reschedule_before_hours" value="{{ $bookingSettings['reschedule_before_hours'] }}" min="0">
                    <div class="form-text">How many hours before an appointment it can be rescheduled.</div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="default_appointment_duration" class="form-label">Default Appointment Duration (Minutes)</label>
                    <input type="number" class="form-control" id="default_appointment_duration" name="default_appointment_duration" value="{{ $bookingSettings['default_appointment_duration'] }}" min="5">
                </div>
                
                <div class="col-md-6">
                    <label for="buffer_time_between_appointments" class="form-label">Buffer Time Between Appointments (Minutes)</label>
                    <input type="number" class="form-control" id="buffer_time_between_appointments" name="buffer_time_between_appointments" value="{{ $bookingSettings['buffer_time_between_appointments'] }}" min="0">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="allow_same_day_booking" name="allow_same_day_booking" value="1" {{ $bookingSettings['allow_same_day_booking'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="allow_same_day_booking">Allow Same Day Booking</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_weekend_booking" name="enable_weekend_booking" value="1" {{ $bookingSettings['enable_weekend_booking'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_weekend_booking">Enable Weekend Booking</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>