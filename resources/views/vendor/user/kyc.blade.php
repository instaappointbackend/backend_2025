@extends('layouts.app')

@section('title', 'InstaAppoint - Vendor Registration')

@section('content')
    <style>
        .form-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .upload-btn {
            border: 1px dashed #0d6efd;
            color: #0d6efd;
            background: #f8fbff;
        }

        .upload-btn:hover {
            background: #eaf2ff;
        }
    </style>

    <div class="container py-5">
        <div class="row g-4">

            <!-- LEFT COLUMN : BUSINESS CATEGORIES -->
            <div class="col-lg-7 order-2 order-lg-1">
                @include('vendor.user.businessCategory')
            </div>

            <!-- RIGHT COLUMN : REGISTRATION FORM -->
            <div class="col-lg-5 order-1 order-lg-2">
                <div class="card shadow-lg border-0 rounded-4">

                    <div class="card-header bg-primary text-white text-center py-3 rounded-top">
                        <h4 class="mb-0">Complete Your Profile</h4>
                        <small>Upload mandatory documents</small>
                    </div>
                    @if (session('success'))
                        <div class="alert alert-primary alert-dismissible fade show m-2">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-2">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if ($errors->any())
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <form action="{{ route('vendor.vendorKyc') }}" method="POST" enctype="multipart/form-data"
                        class="needs-validation" novalidate>
                        @csrf

                        <!-- Personal Documents -->
                        <div class="card form-card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Personal Documents</h6>

                                <!-- Aadhaar -->
                                <div class="mb-3">
                                    <label class="form-label">Aadhaar Card Number</label>
                                    <input type="text" name="aadhar_number"
                                        class="form-control @error('aadhar_number') is-invalid @enderror"
                                        value="{{ old('aadhar_number') }}" placeholder="Enter 12-digit Aadhaar number"
                                        required pattern="\d{12}">
                                    <div class="invalid-feedback">Aadhaar must be 12 digits.</div>
                                    @error('aadhar_number')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror

                                    <button type="button" class="btn upload-btn w-100 mt-2"
                                        onclick="document.getElementById('aadhar_attachment').click()">
                                        <i class="bi bi-upload me-1"></i> Upload Aadhaar Card
                                    </button>
                                    <input type="file" id="aadhar_attachment" name="aadhar_attachment" class="d-none"
                                        accept=".jpg,.jpeg,.png,.pdf" onchange="showFileName(this, 'aadharFileName')">
                                    <div id="aadharFileName" class="small text-muted mt-1"></div>
                                    @error('aadhar_attachment')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- PAN Upload -->
                                <div class="mb-3">
                                    <label class="form-label">PAN Card Number</label>
                                    <input type="text" name="pan_number"
                                        class="form-control @error('pan_number') is-invalid @enderror"
                                        value="{{ old('pan_number') }}" placeholder="ABCDE1234F" required>
                                    <div class="invalid-feedback">Enter a valid PAN number.</div>
                                    @error('pan_number')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror

                                    <button type="button" class="btn upload-btn w-100 mt-2"
                                        onclick="document.getElementById('pan_attachment').click()">
                                        <i class="bi bi-upload me-1"></i> Upload PAN Card
                                    </button>
                                    <input type="file" id="pan_attachment" name="pan_attachment" class="d-none"
                                        accept=".jpg,.jpeg,.png,.pdf" onchange="showFileName(this, 'panFileName')">
                                    <div id="panFileName" class="small text-muted mt-1"></div>
                                    @error('pan_attachment')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Business Details -->
                        <div class="card form-card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Business Details</h6>

                                <div class="mb-3">
                                    <input type="text" name="business_name"
                                        class="form-control @error('business_name') is-invalid @enderror"
                                        placeholder="Business Name" value="{{ old('business_name') }}" required>
                                    <div class="invalid-feedback">Business name is required.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Establishment Date</label>
                                    <input type="date" name="business_established_date"
                                        class="form-control @error('business_established_date') is-invalid @enderror"
                                        value="{{ old('business_established_date') }}" required>
                                    <div class="invalid-feedback">Please select establishment date.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Business Category *</label>
                                    <select name="business_category_id"
                                        class="form-select rounded-3 @error('business_category_id') is-invalid @enderror"
                                        required onchange="document.getElementById('category-' + this.value)?.click();">
                                        <option value="">-- Select Category --</option>
                                        @foreach ($businessCategory as $cat)
                                            <option value="{{ $cat->id }}"
                                                {{ old('business_category_id') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_category_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">
                                            Please select a business category.
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Business Address</label>
                                    <input type="text" id="address" name="address"
                                        class="form-control @error('address') is-invalid @enderror"
                                        placeholder="Search for your address" value="{{ old('address') }}" required>
                                    <div class="invalid-feedback">Address is required.</div>
                                    @error('address')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror

                                    <!-- Hidden fields for structured address -->
                                    <input type="hidden" name="full_address" id="full_address">
                                    <input type="hidden" name="street" id="street">
                                    <input type="hidden" name="city" id="city">
                                    <input type="hidden" name="state" id="state">
                                    <input type="hidden" name="country" id="country">
                                    <input type="hidden" name="postal_code" id="postal_code">
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                </div>



                                <div class="mb-3">
                                    <textarea name="description" class="form-control" rows="3" placeholder="Business Description">{{ old('description') }}</textarea>
                                </div>
                                <!-- Business Logo Upload -->
                                <div class="mb-3">
                                    <button type="button" class="btn upload-btn w-100 mt-2"
                                        onclick="document.getElementById('business_logo').click()">
                                        <i class="bi bi-upload me-1"></i> Upload Business Logo
                                    </button>
                                    <input type="file" id="business_logo" name="business_logo" class="d-none"
                                        accept=".jpg,.jpeg,.png,.pdf" onchange="showFileName(this, 'businessLogoName')">
                                    <div id="businessLogoName" class="small text-muted mt-1"></div>
                                </div>

                                <!-- Identity Document Upload -->
                                <div class="mb-3">
                                    <button type="button" class="btn upload-btn w-100 mt-2"
                                        onclick="document.getElementById('identity_document').click()">
                                        <i class="bi bi-upload me-1"></i> Upload Identity Document
                                    </button>
                                    <input type="file" id="identity_document" name="identity_document" class="d-none"
                                        accept=".jpg,.jpeg,.png,.pdf" onchange="showFileName(this, 'identityDocName')">
                                    <div id="identityDocName" class="small text-muted mt-1"></div>
                                </div>

                            </div>
                        </div>

                        <!-- Submit -->
                        <button class="btn btn-primary w-100 py-2 fw-semibold">
                            Submit Verification
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOTSTRAP CLIENT-SIDE VALIDATION -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_API_KEY') }}&libraries=places"></script>
    <script>
        (() => {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        function initAutocomplete() {
            const input = document.getElementById('address');
            const autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['geocode'], // only addresses
                componentRestrictions: {
                    country: 'IN'
                } // restrict to India
            });

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();

                if (!place.geometry) return;

                // Fill hidden fields
                document.getElementById('full_address').value = place.formatted_address;
                document.getElementById('latitude').value = place.geometry.location.lat();
                document.getElementById('longitude').value = place.geometry.location.lng();

                let street = '',
                    city = '',
                    state = '',
                    country = '',
                    postal_code = '';

                place.address_components.forEach(component => {
                    const types = component.types;
                    if (types.includes('route')) street = component.long_name;
                    if (types.includes('sublocality') || types.includes('sublocality_level_1')) street =
                        street ? street + ', ' + component.long_name : component.long_name;
                    if (types.includes('locality')) city = component.long_name;
                    if (types.includes('administrative_area_level_1')) state = component.long_name;
                    if (types.includes('country')) country = component.long_name;
                    if (types.includes('postal_code')) postal_code = component.long_name;
                });

                document.getElementById('street').value = street;
                document.getElementById('city').value = city;
                document.getElementById('state').value = state;
                document.getElementById('country').value = country;
                document.getElementById('postal_code').value = postal_code;
            });
        }

        window.onload = initAutocomplete;

        function showFileName(input, displayId) {
            const fileName = input.files[0] ? input.files[0].name : '';
            document.getElementById(displayId).textContent = fileName;
        }
    </script>


@endsection

@section('scripts')
    <script>
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}");
            @endforeach
        @endif
    </script>
@endsection
