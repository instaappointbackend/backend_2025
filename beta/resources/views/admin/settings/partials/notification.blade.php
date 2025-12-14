<!-- resources/views/admin/settings/partials/notification.blade.php -->
<div class="tab-pane fade" id="notification-settings" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Notification Settings</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_email_notifications" name="enable_email_notifications" value="1" {{ $notificationSettings['enable_email_notifications'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_email_notifications">Email Notifications</label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_sms_notifications" name="enable_sms_notifications" value="1" {{ $notificationSettings['enable_sms_notifications'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_sms_notifications">SMS Notifications</label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_push_notifications" name="enable_push_notifications" value="1" {{ $notificationSettings['enable_push_notifications'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_push_notifications">Push Notifications</label>
                    </div>
                </div>
            </div>
            
            <h6 class="mt-4 mb-3">Customer Notifications</h6>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="booking_confirmation_email" name="booking_confirmation_email" value="1" {{ $notificationSettings['booking_confirmation_email'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="booking_confirmation_email">Booking Confirmation Email</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="booking_reminder_email" name="booking_reminder_email" value="1" {{ $notificationSettings['booking_reminder_email'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="booking_reminder_email">Booking Reminder Email</label>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="booking_confirmation_sms" name="booking_confirmation_sms" value="1" {{ isset($notificationSettings['booking_confirmation_sms']) && $notificationSettings['booking_confirmation_sms'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="booking_confirmation_sms">Booking Confirmation SMS</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="booking_reminder_sms" name="booking_reminder_sms" value="1" {{ isset($notificationSettings['booking_reminder_sms']) && $notificationSettings['booking_reminder_sms'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="booking_reminder_sms">Booking Reminder SMS</label>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="booking_reminder_hours" class="form-label">Reminder Hours Before Appointment</label>
                    <input type="number" class="form-control" id="booking_reminder_hours" name="booking_reminder_hours" value="{{ $notificationSettings['booking_reminder_hours'] }}" min="1" max="72">
                </div>
            </div>
            
            <h6 class="mt-4 mb-3">Admin Notifications</h6>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="admin_new_booking_notification" name="admin_new_booking_notification" value="1" {{ $notificationSettings['admin_new_booking_notification'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="admin_new_booking_notification">New Booking Notification</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="admin_booking_cancellation_notification" name="admin_booking_cancellation_notification" value="1" {{ isset($notificationSettings['admin_booking_cancellation_notification']) && $notificationSettings['admin_booking_cancellation_notification'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="admin_booking_cancellation_notification">Booking Cancellation Notification</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>