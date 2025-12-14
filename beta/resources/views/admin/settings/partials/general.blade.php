<!-- resources/views/admin/settings/partials/general.blade.php -->
<div class="tab-pane fade show active" id="general-settings" role="tabpanel">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>General Settings</h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="app_name" class="form-label fw-bold">Application Name</label>
                    <input type="text" class="form-control" id="app_name" name="app_name" value="{{ $generalSettings['app_name'] }}">
                    <div class="form-text">This name will be used throughout the application and in communications.</div>
                </div>
                <div class="col-md-6">
                    <label for="timezone" class="form-label fw-bold">Timezone</label>
                    <select class="form-select" id="timezone" name="timezone">
                        @foreach(timezone_identifiers_list() as $timezone)
                        <option value="{{ $timezone }}" {{ $generalSettings['timezone'] == $timezone ? 'selected' : '' }}>
                        {{ $timezone }}
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text">All dates and times will be displayed in this timezone.</div>
                </div>
            </div>

            <h5 class="mt-4 mb-3 border-bottom pb-2"><i class="fas fa-image me-2"></i>Brand Identity</h5>

            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Primary Logo</h6>
                        </div>
                        <div class="card-body">
                            @if(isset($generalSettings['app_logo']))
                            <div class="text-center mb-3 p-3 bg-light rounded">
                                <img src="{{ asset('storage/' . $generalSettings['app_logo']) }}" alt="Primary Logo" class="img-fluid" style="max-height: 60px;">
                            </div>
                            <div class="d-flex justify-content-center mb-3">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-logo" data-logo-type="app_logo">
                                    <i class="fas fa-trash-alt me-1"></i> Remove Logo
                                </button>
                            </div>
                            @else
                            <div class="text-center mb-3 p-3 bg-light rounded">
                                <div class="text-muted"><i class="fas fa-image fa-3x mb-2"></i><br>No logo uploaded</div>
                            </div>
                            @endif

                            <label for="app_logo" class="form-label">Upload Primary Logo</label>
                            <input type="file" class="form-control" id="app_logo" name="app_logo">
                            <div id="logo_preview" class="mt-2"></div>
                            <small class="form-text text-muted">Recommended size: 180x60px. PNG with transparency works best.</small>
                            <small class="d-block form-text text-muted">This is the main logo displayed throughout the application.</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">Dark Logo</h6>
                        </div>
                        <div class="card-body">
                            @if(isset($generalSettings['app_logo_dark']))
                            <div class="text-center mb-3 p-3 bg-dark rounded">
                                <img src="{{ asset('storage/' . $generalSettings['app_logo_dark']) }}" alt="Dark Logo" class="img-fluid" style="max-height: 60px;">
                            </div>
                            <div class="d-flex justify-content-center mb-3">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-logo" data-logo-type="app_logo_dark">
                                    <i class="fas fa-trash-alt me-1"></i> Remove Logo
                                </button>
                            </div>
                            @else
                            <div class="text-center mb-3 p-3 bg-dark rounded">
                                <div class="text-white"><i class="fas fa-image fa-3x mb-2"></i><br>No logo uploaded</div>
                            </div>
                            @endif

                            <label for="app_logo_dark" class="form-label">Upload Alternative Logo</label>
                            <input type="file" class="form-control" id="app_logo_dark" name="app_logo_dark">
                            <div id="dark_logo_preview" class="mt-2"></div>
                            <small class="form-text text-muted">Recommended size: 180x60px. Light colored logo for dark backgrounds.</small>
                            <small class="d-block form-text text-muted">This alternative logo will be used on dark backgrounds.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Favicon</h6>
                        </div>
                        <div class="card-body">
                            @if(isset($generalSettings['app_favicon']))
                            <div class="text-center mb-3">
                                <img src="{{ asset('storage/' . $generalSettings['app_favicon']) }}" alt="Favicon" class="img-thumbnail" style="max-width: 64px;">
                            </div>
                            <div class="d-flex justify-content-center mb-3">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-logo" data-logo-type="app_favicon">
                                    <i class="fas fa-trash-alt me-1"></i> Remove Favicon
                                </button>
                            </div>
                            @endif

                            <label for="app_favicon" class="form-label">Upload Favicon</label>
                            <input type="file" class="form-control" id="app_favicon" name="app_favicon">
                            <div id="favicon_preview" class="mt-2"></div>
                            <small class="form-text text-muted">Recommended size: 32x32px or 64x64px. PNG format.</small>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mt-4 mb-3 border-bottom pb-2"><i class="fas fa-globe me-2"></i>Regional Settings</h5>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="date_format" class="form-label fw-bold">Date Format</label>
                    <select class="form-select" id="date_format" name="date_format">
                        <option value="Y-m-d" {{ $generalSettings['date_format'] == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD (2025-04-30)</option>
                        <option value="m/d/Y" {{ $generalSettings['date_format'] == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY (04/30/2025)</option>
                        <option value="d/m/Y" {{ $generalSettings['date_format'] == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY (30/04/2025)</option>
                        <option value="d.m.Y" {{ $generalSettings['date_format'] == 'd.m.Y' ? 'selected' : '' }}>DD.MM.YYYY (30.04.2025)</option>
                    </select>
                    <div class="form-text">Format for displaying dates throughout the application.</div>
                </div>

                <div class="col-md-6">
                    <label for="time_format" class="form-label fw-bold">Time Format</label>
                    <select class="form-select" id="time_format" name="time_format">
                        <option value="H:i" {{ $generalSettings['time_format'] == 'H:i' ? 'selected' : '' }}>24-hour (14:30)</option>
                        <option value="h:i A" {{ $generalSettings['time_format'] == 'h:i A' ? 'selected' : '' }}>12-hour (02:30 PM)</option>
                    </select>
                    <div class="form-text">Format for displaying times throughout the application.</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="currency" class="form-label fw-bold">Currency</label>
                    <select class="form-select" id="currency" name="currency">
                        <option value="INR" {{ $generalSettings['currency'] == 'INR' ? 'selected' : '' }}>Indian Rupee (₹)</option>
                        <option value="USD" {{ $generalSettings['currency'] == 'USD' ? 'selected' : '' }}>US Dollar ($)</option>
                        <option value="EUR" {{ $generalSettings['currency'] == 'EUR' ? 'selected' : '' }}>Euro (€)</option>
                        <option value="GBP" {{ $generalSettings['currency'] == 'GBP' ? 'selected' : '' }}>British Pound (£)</option>
                    </select>
                    <div class="form-text">Currency for payments and financial transactions.</div>
                </div>
            </div>

            <h5 class="mt-4 mb-3 border-bottom pb-2"><i class="fas fa-id-card me-2"></i>Contact Information</h5>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="admin_email" class="form-label fw-bold">Admin Email</label>
                    <input type="email" class="form-control" id="admin_email" name="admin_email" value="{{ $generalSettings['admin_email'] }}">
                    <div class="form-text">Primary email for admin notifications and alerts.</div>
                </div>

                <div class="col-md-6">
                    <label for="support_email" class="form-label fw-bold">Support Email</label>
                    <input type="email" class="form-control" id="support_email" name="support_email" value="{{ $generalSettings['support_email'] }}">
                    <div class="form-text">Email address shown to customers for support inquiries.</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="support_phone" class="form-label fw-bold">Support Phone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="support_phone" name="support_phone" value="{{ $generalSettings['support_phone'] }}">
                    </div>
                    <div class="form-text">Phone number shown to customers for support inquiries.</div>
                </div>

                <div class="col-md-6">
                    <label for="address" class="form-label fw-bold">Business Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" class="form-control" id="address" name="address" value="{{ $generalSettings['address'] ?? '123 App Street, Tech City, CA 12345' }}">
                    </div>
                    <div class="form-text">Physical address shown on the website.</div>
                </div>
            </div>

            <h5 class="mt-4 mb-3 border-bottom pb-2"><i class="fas fa-share-alt me-2"></i>Social Media Links</h5>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="social_facebook" class="form-label fw-bold">Facebook</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fab fa-facebook-f"></i></span>
                        <input type="url" class="form-control" id="social_facebook" name="social_facebook" value="{{ $generalSettings['social_facebook'] ?? '' }}" placeholder="https://facebook.com/yourbusiness">
                    </div>
                    <div class="form-text">Your business Facebook page URL.</div>
                </div>

                <div class="col-md-6">
                    <label for="social_twitter" class="form-label fw-bold">Twitter</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                        <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="{{ $generalSettings['social_twitter'] ?? '' }}" placeholder="https://twitter.com/yourbusiness">
                    </div>
                    <div class="form-text">Your business Twitter profile URL.</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="social_instagram" class="form-label fw-bold">Instagram</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                        <input type="url" class="form-control" id="social_instagram" name="social_instagram" value="{{ $generalSettings['social_instagram'] ?? '' }}" placeholder="https://instagram.com/yourbusiness">
                    </div>
                    <div class="form-text">Your business Instagram profile URL.</div>
                </div>

                <div class="col-md-6">
                    <label for="social_linkedin" class="form-label fw-bold">LinkedIn</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fab fa-linkedin-in"></i></span>
                        <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" value="{{ $generalSettings['social_linkedin'] ?? '' }}" placeholder="https://linkedin.com/company/yourbusiness">
                    </div>
                    <div class="form-text">Your business LinkedIn page URL.</div>
                </div>
            </div>
        </div>
    </div>
</div>
