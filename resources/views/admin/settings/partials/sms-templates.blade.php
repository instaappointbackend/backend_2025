<!-- resources/views/admin/settings/partials/sms-templates.blade.php -->
<div class="tab-pane fade" id="sms-templates" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">SMS Templates</h5>
            <div>
                <span class="badge bg-info">Textlocal SMS Provider</span>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Important:</strong> All SMS templates must be DLT approved before they can be used. Make sure your template IDs are properly registered.
            </div>
            
            <div class="mb-4">
                <label for="otp_verification_sms_template" class="form-label">OTP Verification Template</label>
                <textarea class="form-control" id="otp_verification_sms_template" name="otp_verification_sms_template" rows="3" readonly>[OTP] is your OTP to login to Insta Appoint. DO NOT share with anyone. We never calls to ask for OTP. The otp expires in 10 mins.</textarea>
                <div class="form-text">DLT Approved Template ID: 1707174142743104785</div>
                <div class="form-text">This message will be sent for OTP verification. The [OTP] placeholder will be replaced with the actual OTP.</div>
            </div>
            
            <div class="mb-4">
                <label for="booking_confirmation_sms_template" class="form-label">Booking Confirmation Template</label>
                <textarea class="form-control" id="booking_confirmation_sms_template" name="booking_confirmation_sms_template" rows="3">Your appointment for [SERVICE] is confirmed for [DATE] at [TIME]. Booking ID: [BOOKING_ID]. Thank you for choosing InstaAppoint!</textarea>
                <div class="d-flex align-items-center mt-2">
                    <label for="booking_confirmation_template_id" class="form-label mb-0 me-2">Template ID:</label>
                    <input type="text" class="form-control form-control-sm w-auto" id="booking_confirmation_template_id" name="booking_confirmation_template_id" value="{{ isset($smsTemplates['booking_confirmation_template_id']) ? $smsTemplates['booking_confirmation_template_id'] : '' }}">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="booking_reminder_sms_template" class="form-label">Booking Reminder Template</label>
                <textarea class="form-control" id="booking_reminder_sms_template" name="booking_reminder_sms_template" rows="3">Reminder: Your appointment for [SERVICE] is tomorrow at [TIME]. We look forward to seeing you! If you need to reschedule, please contact us.</textarea>
                <div class="d-flex align-items-center mt-2">
                    <label for="booking_reminder_template_id" class="form-label mb-0 me-2">Template ID:</label>
                    <input type="text" class="form-control form-control-sm w-auto" id="booking_reminder_template_id" name="booking_reminder_template_id" value="{{ isset($smsTemplates['booking_reminder_template_id']) ? $smsTemplates['booking_reminder_template_id'] : '' }}">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="booking_cancellation_sms_template" class="form-label">Booking Cancellation Template</label>
                <textarea class="form-control" id="booking_cancellation_sms_template" name="booking_cancellation_sms_template" rows="3">Your appointment for [SERVICE] on [DATE] at [TIME] has been cancelled. Booking ID: [BOOKING_ID]. If you wish to reschedule, please book again.</textarea>
                <div class="d-flex align-items-center mt-2">
                    <label for="booking_cancellation_template_id" class="form-label mb-0 me-2">Template ID:</label>
                    <input type="text" class="form-control form-control-sm w-auto" id="booking_cancellation_template_id" name="booking_cancellation_template_id" value="{{ isset($smsTemplates['booking_cancellation_template_id']) ? $smsTemplates['booking_cancellation_template_id'] : '' }}">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="booking_rescheduled_sms_template" class="form-label">Booking Rescheduled Template</label>
                <textarea class="form-control" id="booking_rescheduled_sms_template" name="booking_rescheduled_sms_template" rows="3">Your appointment has been rescheduled to [DATE] at [TIME] for [SERVICE]. Booking ID: [BOOKING_ID]. If this time doesn't work for you, please contact us.</textarea>
                <div class="d-flex align-items-center mt-2">
                    <label for="booking_rescheduled_template_id" class="form-label mb-0 me-2">Template ID:</label>
                    <input type="text" class="form-control form-control-sm w-auto" id="booking_rescheduled_template_id" name="booking_rescheduled_template_id" value="{{ isset($smsTemplates['booking_rescheduled_template_id']) ? $smsTemplates['booking_rescheduled_template_id'] : '' }}">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="payment_confirmation_sms_template" class="form-label">Payment Confirmation Template</label>
                <textarea class="form-control" id="payment_confirmation_sms_template" name="payment_confirmation_sms_template" rows="3">Thank you! Your payment of [AMOUNT] for [SERVICE] has been received. Your appointment is confirmed for [DATE] at [TIME]. Booking ID: [BOOKING_ID].</textarea>
                <div class="d-flex align-items-center mt-2">
                    <label for="payment_confirmation_template_id" class="form-label mb-0 me-2">Template ID:</label>
                    <input type="text" class="form-control form-control-sm w-auto" id="payment_confirmation_template_id" name="payment_confirmation_template_id" value="{{ isset($smsTemplates['payment_confirmation_template_id']) ? $smsTemplates['payment_confirmation_template_id'] : '' }}">
                </div>
            </div>
            
            <div class="alert alert-info">
                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Variable Placeholders</h6>
                <p class="mb-1">Use these placeholders in your templates. They'll be replaced with actual values when the SMS is sent:</p>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <ul class="mb-0 ps-3">
                            <li><code>[OTP]</code> - One-time password</li>
                            <li><code>[NAME]</code> - Customer's name</li>
                            <li><code>[SERVICE]</code> - Service name</li>
                            <li><code>[DATE]</code> - Appointment date</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="mb-0 ps-3">
                            <li><code>[TIME]</code> - Appointment time</li>
                            <li><code>[AMOUNT]</code> - Payment amount</li>
                            <li><code>[BOOKING_ID]</code> - Unique booking ID</li>
                            <li><code>[LOCATION]</code> - Business location</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>