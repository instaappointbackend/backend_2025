<!-- resources/views/admin/settings/partials/api-settings.blade.php -->
<div class="tab-pane fade" id="api-settings" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">API Settings</h5>
        </div>
        <div class="card-body">
            <!-- Google Maps API Settings -->
            <h6 class="mt-2 mb-3">Google Maps API</h6>
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="google_maps_api_key" class="form-label">Google Maps API Key</label>
                    <input type="text" class="form-control @error('google_maps_api_key') is-invalid @enderror" id="google_maps_api_key" name="google_maps_api_key" value="{{ isset($apiSettings['google_maps_api_key']) ? $apiSettings['google_maps_api_key'] : '' }}">
                    <div class="form-text">Used for location services, maps display, and distance calculations.</div>
                    @error('google_maps_api_key')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- SMS Gateway Settings -->
            <h6 class="border-top pt-4 mb-3">Textlocal SMS Gateway Settings</h6>
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Using Textlocal as the SMS provider for sending notifications and OTP verification messages.
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="sms_api_key" class="form-label">Textlocal API Key</label>
                    <input type="text" class="form-control @error('sms_api_key') is-invalid @enderror" id="sms_api_key" name="sms_api_key" value="{{ isset($apiSettings['sms_api_key']) ? $apiSettings['sms_api_key'] : 'mUK2DwqtHuN3rEcR' }}">
                    <div class="form-text">Your Textlocal API key for authentication.</div>
                    @error('sms_api_key')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="sms_sender_id" class="form-label">Sender ID</label>
                    <input type="text" class="form-control @error('sms_sender_id') is-invalid @enderror" id="sms_sender_id" name="sms_sender_id" value="{{ isset($apiSettings['sms_sender_id']) ? $apiSettings['sms_sender_id'] : 'INSTPT' }}">
                    <div class="form-text">The ID that appears as the sender of the SMS (6 characters).</div>
                    @error('sms_sender_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="sms_api_url" class="form-label">API URL</label>
                    <input type="text" class="form-control @error('sms_api_url') is-invalid @enderror" id="sms_api_url" name="sms_api_url" value="{{ isset($apiSettings['sms_api_url']) ? $apiSettings['sms_api_url'] : 'https://manage.txly.in/vb/apikey.php' }}" readonly>
                    <div class="form-text">The base URL for the Textlocal API. <span class="text-muted">(Pre-configured)</span></div>
                    @error('sms_api_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="sms_api_template_id" class="form-label">Default Template ID</label>
                    <input type="text" class="form-control @error('sms_api_template_id') is-invalid @enderror" id="sms_api_template_id" name="sms_api_template_id" value="{{ isset($apiSettings['sms_api_template_id']) ? $apiSettings['sms_api_template_id'] : '1707174142743104785' }}">
                    <div class="form-text">DLT approved template ID for OTP messages.</div>
                    @error('sms_api_template_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card bg-light border">
                        <div class="card-header">
                            <h6 class="mb-0">OTP Message Format</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><code>[OTP] is your OTP to login to Insta Appoint. DO NOT share with anyone. We never calls to ask for OTP. The otp expires in 10 mins.</code></p>
                        </div>
                        <div class="card-footer bg-white">
                            <small class="text-muted">This format must match your DLT approved template.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="test-sms-btn">
                        <i class="fas fa-paper-plane me-1"></i> Test SMS Configuration
                    </button>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="otp_expired" class="form-label">OTP Expire(Minutes)</label>
                    <input type="tel" class="form-control @error('otp_expired') is-invalid @enderror" id="otp_expired" name="otp_expired" value="{{ isset($apiSettings['otp_expired']) ? $apiSettings['otp_expired'] : '10' }}">
                    <div class="form-text">How many minutes OTP has been expired.</div>
                    @error('otp_expired')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input @error('enable_sms_api') is-invalid @enderror" type="checkbox" role="switch" id="enable_sms_api" name="enable_sms_api" value="1" {{ isset($apiSettings['enable_sms_api']) && $apiSettings['enable_sms_api'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_sms_api">Enable SMS API</label>
                        <div class="form-text">When disabled, system will show static OTP (123456) instead of sending real SMS.</div>
                        @error('enable_sms_api')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Textlocal SMS API Request Preview -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="accordion" id="smsApiAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    <i class="fas fa-code me-2"></i> SMS API Request Format
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#smsApiAccordion">
                                <div class="accordion-body">
                                    <pre><code>https://manage.txly.in/vb/apikey.php?
apikey=mUK2DwqtHuN3rEcR
&senderid=INSTPT
&templateid=1707174142743104785
&number=[PHONE_NUMBER]
&message=[OTP] is your OTP to login to Insta Appoint. DO NOT share with anyone. We never calls to ask for OTP. The otp expires in 10 mins.</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Gateway Settings -->
            <!--<h6 class="border-top pt-4 mb-3">Payment Gateway API</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="payment_gateway" class="form-label">Payment Gateway</label>
                    <select class="form-select @error('payment_gateway') is-invalid @enderror" id="payment_gateway" name="payment_gateway">
                        <option value="phonepe" {{ isset($apiSettings['payment_gateway']) && $apiSettings['payment_gateway'] == 'phonepe' ? 'selected' : '' }}>PhonePe</option>
                        <option value="razorpay" {{ isset($apiSettings['payment_gateway']) && $apiSettings['payment_gateway'] == 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                        <option value="stripe" {{ isset($apiSettings['payment_gateway']) && $apiSettings['payment_gateway'] == 'stripe' ? 'selected' : '' }}>Stripe</option>
                        <option value="paypal" {{ isset($apiSettings['payment_gateway']) && $apiSettings['payment_gateway'] == 'paypal' ? 'selected' : '' }}>PayPal</option>
                    </select>
                    @error('payment_gateway')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>-->

            <!-- PhonePe Specific Settings -->
          <!--  <div id="phonepe-settings" class="gateway-settings {{ isset($apiSettings['payment_gateway']) && $apiSettings['payment_gateway'] == 'phonepe' ? '' : 'd-none' }}">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phonepe_merchant_id" class="form-label">Merchant ID</label>
                        <input type="text" class="form-control @error('phonepe_merchant_id') is-invalid @enderror" id="phonepe_merchant_id" name="phonepe_merchant_id" value="{{ isset($apiSettings['phonepe_merchant_id']) ? $apiSettings['phonepe_merchant_id'] : '' }}">
                        @error('phonepe_merchant_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phonepe_salt_key" class="form-label">Salt Key</label>
                        <input type="password" class="form-control @error('phonepe_salt_key') is-invalid @enderror" id="phonepe_salt_key" name="phonepe_salt_key" value="{{ isset($apiSettings['phonepe_salt_key']) ? $apiSettings['phonepe_salt_key'] : '' }}">
                        @error('phonepe_salt_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phonepe_salt_index" class="form-label">Salt Index</label>
                        <input type="text" class="form-control @error('phonepe_salt_index') is-invalid @enderror" id="phonepe_salt_index" name="phonepe_salt_index" value="{{ isset($apiSettings['phonepe_salt_index']) ? $apiSettings['phonepe_salt_index'] : '1' }}">
                        @error('phonepe_salt_index')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input @error('phonepe_production_mode') is-invalid @enderror" type="checkbox" role="switch" id="phonepe_production_mode" name="phonepe_production_mode" value="1" {{ isset($apiSettings['phonepe_production_mode']) && $apiSettings['phonepe_production_mode'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="phonepe_production_mode">Production Mode</label>
                            @error('phonepe_production_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phonepe_callback_url" class="form-label">Callback URL</label>
                        <input type="text" class="form-control @error('phonepe_callback_url') is-invalid @enderror" id="phonepe_callback_url" name="phonepe_callback_url" value="{{ isset($apiSettings['phonepe_callback_url']) ? $apiSettings['phonepe_callback_url'] : url('/payment/phonepe/callback') }}" readonly>
                        <div class="form-text">Set this URL in PhonePe merchant dashboard</div>
                        @error('phonepe_callback_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>-->

            <!-- Other Payment Gateways -->
<!--            <div id="other-payment-settings" class="gateway-settings {{ isset($apiSettings['payment_gateway']) && $apiSettings['payment_gateway'] == 'phonepe' ? 'd-none' : '' }}">-->
<!--                <div class="row mb-3">-->
<!--                    <div class="col-md-6">-->
<!--                        <label for="payment_api_key" class="form-label">API Key / Client ID</label>-->
<!--                        <input type="text" class="form-control @error('payment_api_key') is-invalid @enderror" id="payment_api_key" name="payment_api_key" value="{{ isset($apiSettings['payment_api_key']) ? $apiSettings['payment_api_key'] : '' }}">-->
<!--                        @error('payment_api_key')-->
<!--                            <div class="invalid-feedback">{{ $message }}</div>-->
<!--                        @enderror-->
<!--                    </div>-->
<!---->
<!--                    <div class="col-md-6">-->
<!--                        <label for="payment_api_secret" class="form-label">API Secret / Client Secret</label>-->
<!--                        <input type="password" class="form-control @error('payment_api_secret') is-invalid @enderror" id="payment_api_secret" name="payment_api_secret" value="{{ isset($apiSettings['payment_api_secret']) ? $apiSettings['payment_api_secret'] : '' }}">-->
<!--                        @error('payment_api_secret')-->
<!--                            <div class="invalid-feedback">{{ $message }}</div>-->
<!--                        @enderror-->
<!--                    </div>-->
<!--                </div>-->
<!---->
<!--                <div class="row mb-3">-->
<!--                    <div class="col-md-6">-->
<!--                        <div class="form-check form-switch mt-4">-->
<!--                            <input class="form-check-input @error('payment_sandbox_mode') is-invalid @enderror" type="checkbox" role="switch" id="payment_sandbox_mode" name="payment_sandbox_mode" value="1" {{ isset($apiSettings['payment_sandbox_mode']) && $apiSettings['payment_sandbox_mode'] ? 'checked' : '' }}>-->
<!--                            <label class="form-check-label" for="payment_sandbox_mode">Sandbox/Test Mode</label>-->
<!--                            @error('payment_sandbox_mode')-->
<!--                                <div class="invalid-feedback">{{ $message }}</div>-->
<!--                            @enderror-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->

            <!-- Email SMTP Settings -->
            <h6 class="border-top pt-4 mb-3">Email SMTP Settings</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="mail_mailer" class="form-label">Mail Driver</label>
                    <select class="form-select @error('mail_mailer') is-invalid @enderror" id="mail_mailer" name="mail_mailer">
                        <option value="smtp" {{ isset($apiSettings['mail_mailer']) && $apiSettings['mail_mailer'] == 'smtp' ? 'selected' : '' }}>SMTP</option>
                        <option value="sendmail" {{ isset($apiSettings['mail_mailer']) && $apiSettings['mail_mailer'] == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                        <option value="mailgun" {{ isset($apiSettings['mail_mailer']) && $apiSettings['mail_mailer'] == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                        <option value="ses" {{ isset($apiSettings['mail_mailer']) && $apiSettings['mail_mailer'] == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                    </select>
                    @error('mail_mailer')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="mail_host" class="form-label">SMTP Host</label>
                    <input type="text" class="form-control @error('mail_host') is-invalid @enderror" id="mail_host" name="mail_host" value="{{ isset($apiSettings['mail_host']) ? $apiSettings['mail_host'] : 'smtp.gmail.com' }}">
                    @error('mail_host')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="mail_port" class="form-label">SMTP Port</label>
                    <input type="text" class="form-control @error('mail_port') is-invalid @enderror" id="mail_port" name="mail_port" value="{{ isset($apiSettings['mail_port']) ? $apiSettings['mail_port'] : '587' }}">
                    @error('mail_port')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="mail_encryption" class="form-label">Encryption</label>
                    <select class="form-select @error('mail_encryption') is-invalid @enderror" id="mail_encryption" name="mail_encryption">
                        <option value="tls" {{ isset($apiSettings['mail_encryption']) && $apiSettings['mail_encryption'] == 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ isset($apiSettings['mail_encryption']) && $apiSettings['mail_encryption'] == 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="" {{ isset($apiSettings['mail_encryption']) && $apiSettings['mail_encryption'] == '' ? 'selected' : '' }}>None</option>
                    </select>
                    @error('mail_encryption')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="mail_username" class="form-label">SMTP Username</label>
                    <input type="text" class="form-control @error('mail_username') is-invalid @enderror" id="mail_username" name="mail_username" value="{{ isset($apiSettings['mail_username']) ? $apiSettings['mail_username'] : '' }}">
                    @error('mail_username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="mail_password" class="form-label">SMTP Password</label>
                    <input type="password" class="form-control @error('mail_password') is-invalid @enderror" id="mail_password" name="mail_password" value="{{ isset($apiSettings['mail_password']) ? $apiSettings['mail_password'] : '' }}">
                    <div class="form-text">For Gmail, you may need to use an app password.</div>
                    @error('mail_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="mail_from_address" class="form-label">From Email Address</label>
                    <input type="email" class="form-control @error('mail_from_address') is-invalid @enderror" id="mail_from_address" name="mail_from_address" value="{{ isset($apiSettings['mail_from_address']) ? $apiSettings['mail_from_address'] : 'noreply@instaappoint.com' }}">
                    @error('mail_from_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="mail_from_name" class="form-label">From Name</label>
                    <input type="text" class="form-control @error('mail_from_name') is-invalid @enderror" id="mail_from_name" name="mail_from_name" value="{{ isset($apiSettings['mail_from_name']) ? $apiSettings['mail_from_name'] : 'InstaAppoint' }}">
                    @error('mail_from_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="test-email-btn">
                        <i class="fas fa-paper-plane me-1"></i> Test Email Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
