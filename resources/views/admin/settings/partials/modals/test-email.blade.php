<!-- resources/views/admin/settings/partials/modals/test-email.blade.php -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testEmailModalLabel">Test Email Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="test_email_address" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="test_email_address" placeholder="Enter email address">
                </div>
                <div class="mb-3">
                    <label for="test_email_subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="test_email_subject" value="Test Email from InstaAppoint">
                </div>
                <div class="mb-3">
                    <label for="test_email_message" class="form-label">Message</label>
                    <textarea class="form-control" id="test_email_message" rows="3">This is a test email from InstaAppoint to verify email configuration.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="send-test-email">Send Test Email</button>
            </div>
        </div>
    </div>
</div>