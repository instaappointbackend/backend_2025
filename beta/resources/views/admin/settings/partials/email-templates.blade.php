<!-- resources/views/admin/settings/partials/email-templates.blade.php -->
<div class="tab-pane fade" id="email-templates" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Email Templates</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailTemplateHelp">
                <i class="fas fa-question-circle me-1"></i> Template Variables
            </button>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="emailTemplatesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="booking-confirmation-tab" data-bs-toggle="tab" data-bs-target="#booking-confirmation" type="button" role="tab">
                        Booking Confirmation
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="booking-reminder-tab" data-bs-toggle="tab" data-bs-target="#booking-reminder" type="button" role="tab">
                        Booking Reminder
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="booking-cancellation-tab" data-bs-toggle="tab" data-bs-target="#booking-cancellation" type="button" role="tab">
                        Booking Cancellation
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-confirmation-tab" data-bs-toggle="tab" data-bs-target="#payment-confirmation" type="button" role="tab">
                        Payment Confirmation
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="otp-verification-tab" data-bs-toggle="tab" data-bs-target="#otp-verification" type="button" role="tab">
                        OTP Verification
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="emailTemplatesContent">
                <!-- Booking Confirmation Email -->
                <div class="tab-pane fade show active" id="booking-confirmation" role="tabpanel">
                    <div class="mb-3">
                        <label for="booking_confirmation_email_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="booking_confirmation_email_subject" name="booking_confirmation_email_subject" value="{{ isset($emailTemplates['booking_confirmation']['subject']) ? $emailTemplates['booking_confirmation']['subject'] : 'Your appointment has been confirmed' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="booking_confirmation_email_template" class="form-label">Email Body</label>
                        <textarea class="form-control html-editor" id="booking_confirmation_email_template" name="booking_confirmation_email_template" rows="10">{{ isset($emailTemplates['booking_confirmation']['body']) ? $emailTemplates['booking_confirmation']['body'] : '<p>Hello {name},</p>
<p>Your appointment has been confirmed for {service} with {provider} on {date} at {time}.</p>
<p>Location: {location}</p>
<p>Booking ID: {booking_id}</p>
<p>If you need to make any changes to your appointment, please contact us at least {cancel_hours} hours in advance.</p>
<p>Thank you for choosing us!</p>
<p>Regards,<br>{company_name} Team</p>' }}</textarea>
                    </div>
                </div>
                
                <!-- Booking Reminder Email -->
                <div class="tab-pane fade" id="booking-reminder" role="tabpanel">
                    <div class="mb-3">
                        <label for="booking_reminder_email_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="booking_reminder_email_subject" name="booking_reminder_email_subject" value="{{ isset($emailTemplates['booking_reminder']['subject']) ? $emailTemplates['booking_reminder']['subject'] : 'Reminder: Your appointment tomorrow' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="booking_reminder_email_template" class="form-label">Email Body</label>
                        <textarea class="form-control html-editor" id="booking_reminder_email_template" name="booking_reminder_email_template" rows="10">{{ isset($emailTemplates['booking_reminder']['body']) ? $emailTemplates['booking_reminder']['body'] : '<p>Hello {name},</p>
<p>This is a friendly reminder about your upcoming appointment:</p>
<p><strong>Service:</strong> {service}<br>
<strong>Provider:</strong> {provider}<br>
<strong>Date:</strong> {date}<br>
<strong>Time:</strong> {time}<br>
<strong>Location:</strong> {location}</p>
<p>If you need to reschedule or cancel, please contact us as soon as possible.</p>
<p>We look forward to seeing you!</p>
<p>Regards,<br>{company_name} Team</p>' }}</textarea>
                    </div>
                </div>
                
                <!-- Booking Cancellation Email -->
                <div class="tab-pane fade" id="booking-cancellation" role="tabpanel">
                    <div class="mb-3">
                        <label for="booking_cancellation_email_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="booking_cancellation_email_subject" name="booking_cancellation_email_subject" value="{{ isset($emailTemplates['booking_cancellation']['subject']) ? $emailTemplates['booking_cancellation']['subject'] : 'Your appointment has been cancelled' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="booking_cancellation_email_template" class="form-label">Email Body</label>
                        <textarea class="form-control html-editor" id="booking_cancellation_email_template" name="booking_cancellation_email_template" rows="10">{{ isset($emailTemplates['booking_cancellation']['body']) ? $emailTemplates['booking_cancellation']['body'] : '<p>Hello {name},</p>
<p>We are writing to confirm that your appointment for {service} on {date} at {time} has been cancelled.</p>
<p>Booking ID: {booking_id}</p>
<p>If this cancellation was in error or you would like to reschedule, please visit our website or contact our customer support team.</p>
<p>Thank you for your understanding.</p>
<p>Regards,<br>{company_name} Team</p>' }}</textarea>
                    </div>
                </div>
                
                <!-- Payment Confirmation Email -->
                <div class="tab-pane fade" id="payment-confirmation" role="tabpanel">
                    <div class="mb-3">
                        <label for="payment_confirmation_email_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="payment_confirmation_email_subject" name="payment_confirmation_email_subject" value="{{ isset($emailTemplates['payment_confirmation']['subject']) ? $emailTemplates['payment_confirmation']['subject'] : 'Payment Confirmation - Receipt' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_confirmation_email_template" class="form-label">Email Body</label>
                        <textarea class="form-control html-editor" id="payment_confirmation_email_template" name="payment_confirmation_email_template" rows="10">{{ isset($emailTemplates['payment_confirmation']['body']) ? $emailTemplates['payment_confirmation']['body'] : '<p>Hello {name},</p>
<p>Thank you for your payment. This email confirms that your payment has been processed successfully.</p>
<p><strong>Payment Details:</strong></p>
<ul>
    <li>Amount: {amount}</li>
    <li>Payment Method: {payment_method}</li>
    <li>Transaction ID: {transaction_id}</li>
    <li>Date: {payment_date}</li>
</ul>
<p><strong>Appointment Details:</strong></p>
<ul>
    <li>Service: {service}</li>
    <li>Date: {date}</li>
    <li>Time: {time}</li>
    <li>Provider: {provider}</li>
    <li>Location: {location}</li>
</ul>
<p>If you have any questions about this payment or your appointment, please contact our support team.</p>
<p>Thank you for choosing our services!</p>
<p>Regards,<br>{company_name} Team</p>' }}</textarea>
                    </div>
                </div>

                <!-- OTP Verification Email -->
                <div class="tab-pane fade" id="otp-verification" role="tabpanel">
                    <div class="mb-3">
                        <label for="otp_verification_email_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="otp_verification_email_subject" name="otp_verification_email_subject" value="{{ isset($emailTemplates['otp_verification']['subject']) ? $emailTemplates['otp_verification']['subject'] : 'Your Verification Code' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="otp_verification_email_template" class="form-label">Email Body</label>
                        <textarea class="form-control html-editor" id="otp_verification_email_template" name="otp_verification_email_template" rows="10">{{ isset($emailTemplates['otp_verification']['body']) ? $emailTemplates['otp_verification']['body'] : '<p>Hello,</p>
<p>Your verification code for Insta Appoint is: <strong>{otp}</strong></p>
<p>This code is valid for 10 minutes. Please do not share this code with anyone.</p>
<p>If you did not request this code, please ignore this email.</p>
<p>Regards,<br>{company_name} Team</p>' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>