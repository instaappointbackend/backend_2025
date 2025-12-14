<!-- resources/views/admin/settings/index.blade.php -->
@extends('admin.layouts.app')

@section('title', 'System Settings')

@section('page-title', 'System Settings')

@section('breadcrumb')
<meta name="csrf-token" content="{{ csrf_token() }}" />
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Settings</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush settings-tabs" role="tablist">
                        <a class="list-group-item list-group-item-action active" data-bs-toggle="list" href="#general-settings" role="tab">
                            <i class="fas fa-cog me-2"></i> General Settings
                        </a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#booking-settings" role="tab">
                            <i class="fas fa-calendar-alt me-2"></i> Booking Settings
                        </a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#commission-settings" role="tab">
                            <i class="fas fa-rupee-sign me-2"></i> Commission Settings
                        </a>
                      <!--  <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#notification-settings" role="tab">
                            <i class="fas fa-bell me-2"></i> Notification Settings
                        </a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#sms-templates" role="tab">
                            <i class="fas fa-sms me-2"></i> SMS Templates
                        </a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#email-templates" role="tab">
                            <i class="fas fa-envelope me-2"></i> Email Templates
                        </a>-->
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#api-settings" role="tab">
                            <i class="fas fa-key me-2"></i> API Settings
                        </a>
                        <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#fee-settings" role="tab">
                            <i class="fas fa-inr me-2"></i> Charges Settings
                        </a>
                        <!--<a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#seo-settings" role="tab">
                            <i class="fas fa-search me-2"></i> SEO Settings
                        </a>-->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="tab-content">
                    <!-- General Settings -->
                    @include('admin.settings.partials.general')

                    <!-- Booking Settings -->
                    @include('admin.settings.partials.booking')

                    <!-- Commission Settings -->
                    @include('admin.settings.partials.commission')

                    <!-- Notification Settings -->
                    @include('admin.settings.partials.notification')

                    <!-- SMS Templates -->
                    @include('admin.settings.partials.sms-templates')

                    <!-- Email Templates -->
                    @include('admin.settings.partials.email-templates')

                    <!-- API Settings -->
                    @include('admin.settings.partials.api-settings')

                    @include('admin.settings.partials.fee-settings')

                    <!-- SEO Settings -->
                    @include('admin.settings.partials.seo')
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Email Template Variables Modal -->
    @include('admin.settings.partials.modals.email-template-help')

    <!-- Test SMS Modal -->
    @include('admin.settings.partials.modals.test-sms')

    <!-- Test Email Modal -->
    @include('admin.settings.partials.modals.test-email')
@endsection
@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/tinymce@6.4.2/skins/ui/oxide/skin.min.css" rel="stylesheet">
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.4.2/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '.tinymce-editor',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            branding: false,
            promotion: false,
        });
    </script>
<script>
    // Handle tab switching and maintain state
    document.addEventListener('DOMContentLoaded', function() {
        // Get the active tab from localStorage or use the first tab
        const activeTab = localStorage.getItem('adminSettingsActiveTab') || 'general-settings';

        // Activate the tab
        const tabEl = document.querySelector(`a[href="#${activeTab}"]`);
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
        }

        // Save the active tab to localStorage when tabs are clicked
        document.querySelectorAll('.settings-tabs a').forEach(item => {
            item.addEventListener('shown.bs.tab', function(e) {
                const id = e.target.getAttribute('href').substring(1);
                localStorage.setItem('adminSettingsActiveTab', id);
            });
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize TinyMCE for HTML email templates
        tinymce.init({
            selector: '.html-editor',
            height: 300,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });

        // Payment Gateway Toggling
        document.getElementById('payment_gateway').addEventListener('change', function() {
            const selectedGateway = this.value;
            if (selectedGateway === 'phonepe') {
                document.getElementById('phonepe-settings').classList.remove('d-none');
                document.getElementById('other-payment-settings').classList.add('d-none');
            } else {
                document.getElementById('phonepe-settings').classList.add('d-none');
                document.getElementById('other-payment-settings').classList.remove('d-none');
            }
        });

        // Test SMS Button
        document.getElementById('test-sms-btn').addEventListener('click', function() {
            // Show the test SMS modal
            const testSmsModal = new bootstrap.Modal(document.getElementById('testSmsModal'));
            testSmsModal.show();
        });

        // Send Test SMS
       // Send Test SMS
       document.getElementById('send-test-sms').addEventListener('click', function() {
    const phoneNumber = document.getElementById('test_phone_number').value;
    const otpValue = document.getElementById('test_otp').value;

    if (!phoneNumber) {
        alert('Please enter a valid phone number');
        return;
    }

    // Show loading state
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
    this.disabled = true;

    // Use the correct route URL
    fetch('/admin/settings/test-sms', {  // Changed from '/admin/settings/test-sms'
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            phone_number: phoneNumber,
            otp: otpValue,
            message: '[OTP] is your OTP to login to Insta Appoint. DO NOT share with anyone. We never calls to ask for OTP. The otp expires in 10 mins.'.replace('[OTP]', otpValue),
            api_key: document.getElementById('sms_api_key').value,
            sender_id: document.getElementById('sms_sender_id').value,
            template_id: document.getElementById('sms_api_template_id').value,
            api_url: document.getElementById('sms_api_url').value
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Reset button
        this.innerHTML = 'Send Test SMS';
        this.disabled = false;

        if (data.success) {
            alert('SMS sent successfully!');
            bootstrap.Modal.getInstance(document.getElementById('testSmsModal')).hide();
        } else {
            alert('Failed to send SMS: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        this.innerHTML = 'Send Test SMS';
        this.disabled = false;
        alert('An error occurred: ' + error.message);
    });
});

        // Test Email Button
        document.getElementById('test-email-btn').addEventListener('click', function() {
            // Show the test email modal
            const testEmailModal = new bootstrap.Modal(document.getElementById('testEmailModal'));
            testEmailModal.show();
        });

        // Send Test Email
        document.getElementById('send-test-email').addEventListener('click', function() {
            const email = document.getElementById('test_email_address').value;
            const subject = document.getElementById('test_email_subject').value;
            const message = document.getElementById('test_email_message').value;

            if (!email) {
                alert('Please enter a valid email address');
                return;
            }

            // Show loading state
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
            this.disabled = true;

            // Make AJAX request to test email
            fetch('/admin/settings/test-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    email: email,
                    subject: subject,
                    message: message,
                    mail_driver: document.getElementById('mail_mailer').value,
                    mail_host: document.getElementById('mail_host').value,
                    mail_port: document.getElementById('mail_port').value,
                    mail_username: document.getElementById('mail_username').value,
                    mail_password: document.getElementById('mail_password').value,
                    mail_encryption: document.getElementById('mail_encryption').value,
                    mail_from_address: document.getElementById('mail_from_address').value,
                    mail_from_name: document.getElementById('mail_from_name').value
                })
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                this.innerHTML = 'Send Test Email';
                this.disabled = false;

                if (data.success) {
                    alert('Email sent successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('testEmailModal')).hide();
                } else {
                    alert('Failed to send email: ' + data.message);
                }
            })
            .catch(error => {
                this.innerHTML = 'Send Test Email';
                this.disabled = false;
                alert('An error occurred: ' + error.message);
            });
        });

        // Add preset for SMS URL
        const smsPresetButton = document.createElement('button');
        smsPresetButton.type = 'button';
        smsPresetButton.className = 'btn btn-outline-secondary btn-sm mt-2';
        smsPresetButton.innerHTML = '<i class="fas fa-magic me-1"></i> Use Textlocal Format';
        smsPresetButton.onclick = function() {
            document.getElementById('sms_api_url').value = 'https://manage.txly.in/vb/apikey.php';
            document.getElementById('sms_sender_id').value = 'INSTPT';
            document.getElementById('sms_api_template_id').value = '1707174142743104785';
            alert('Textlocal URL format applied. Don\'t forget to add your API key.');
        };

        // Add the button after the SMS API URL field
        const smsApiUrlFormText = document.querySelector('label[for="sms_api_url"]').nextElementSibling.nextElementSibling;
        smsApiUrlFormText.parentNode.insertBefore(smsPresetButton, smsApiUrlFormText.nextSibling);

        // Example OTP template
        const otpTemplateDiv = document.createElement('div');
        otpTemplateDiv.className = 'mt-3 p-2 bg-light rounded border';
        otpTemplateDiv.innerHTML = `
            <small class="d-block mb-1"><strong>Sample OTP template:</strong></small>
            <small class="d-block text-muted">12345 is your OTP to login to Insta Appoint. DO NOT share with anyone. We never calls to ask for OTP. The otp expires in 10 mins.</small>
        `;

        // Add the OTP template example after the template ID field
        const templateIdFormText = document.querySelector('label[for="sms_api_template_id"]').nextElementSibling.nextElementSibling;
        templateIdFormText.parentNode.insertBefore(otpTemplateDiv, templateIdFormText.nextSibling);
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Preview function for logo uploads
    function setupLogoPreview(inputId, previewContainerId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Check if this is an image
            if (!file.type.match('image.*')) {
                alert('Please select an image file');
                input.value = '';
                return;
            }

            // Create preview
            const reader = new FileReader();
            reader.onload = function(e) {
                let previewContainer = document.getElementById(previewContainerId);

                // If container doesn't exist, create it
                if (!previewContainer) {
                    previewContainer = document.createElement('div');
                    previewContainer.id = previewContainerId;
                    previewContainer.className = 'mt-2';
                    input.parentNode.appendChild(previewContainer);
                }

                // Clear previous preview
                previewContainer.innerHTML = '';

                // Create preview image
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Logo Preview';
                img.height = 40;

                // Add background for preview clarity
                if (inputId === 'app_logo_light') {
                    img.className = 'bg-light p-1 rounded';
                } else if (inputId === 'app_logo_dark') {
                    img.className = 'bg-dark p-1 rounded';
                }

                previewContainer.appendChild(img);
            };

            reader.readAsDataURL(file);
        });
    }

    // Setup previews for both logos
    setupLogoPreview('app_logo_light', 'light_logo_preview');
    setupLogoPreview('app_logo_dark', 'dark_logo_preview');

    // Logo removal functionality
    document.querySelectorAll('.remove-logo').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this logo?')) {
                const logoType = this.getAttribute('data-logo-type');

                // Create a hidden input to tell the server to remove this logo
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'remove_' + logoType;
                hiddenInput.value = '1';
                document.querySelector('form').appendChild(hiddenInput);

                // Hide the preview
                this.parentElement.style.display = 'none';
            }
        });
    });

    // Theme mode toggling preview
    const themeSelect = document.getElementById('theme_mode');
    if (themeSelect) {
        themeSelect.addEventListener('change', function() {
            // This is just a visual indication - in a real app you'd do more
            const body = document.body;
            if (this.value === 'dark') {
                body.classList.add('theme-preview-dark');
            } else {
                body.classList.remove('theme-preview-dark');
            }
        });
    }
});
</script>
@endsection
