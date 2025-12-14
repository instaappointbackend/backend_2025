<!-- resources/views/admin/settings/partials/modals/test-sms.blade.php -->
<div class="modal fade" id="testSmsModal" tabindex="-1" aria-labelledby="testSmsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testSmsModalLabel">Test SMS Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="test_phone_number" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="test_phone_number" placeholder="Enter phone number" value="9068662967">
                    <div class="form-text">Example: 9876543210 (without +91 or country code)</div>
                </div>
                <div class="mb-3">
                    <label for="test_otp" class="form-label">Test OTP</label>
                    <input type="text" class="form-control" id="test_otp" value="123456">
                    <div class="form-text">Enter a 6-digit OTP for testing</div>
                </div>
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle me-1"></i> The SMS will be sent using your Textlocal account with the following template:<br>
                        <span class="mt-1 d-block"><strong class="me-1">Message:</strong> [OTP] is your OTP to login to Insta Appoint. DO NOT share with anyone. We never calls to ask for OTP. The otp expires in 10 mins.</span>
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="send-test-sms">Send Test SMS</button>
            </div>
        </div>
    </div>
</div>