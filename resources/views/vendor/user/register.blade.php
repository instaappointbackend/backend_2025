@extends('layouts.app')

@section('title', 'InstaAppoint - Vendor Registration')

@section('content')
    <style>
        .list-group-item.active {
            background-color: #007BFF;
            color: #fff;
        }

        .list-group-item.active .text-muted {
            --bs-text-opacity: 1;
            color: white !important
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
                        <h4 class="mb-0">Vendor Registration</h4>
                        <small>Create your vendor account</small>
                    </div>

                    <div class="card-body p-4">
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        <form id="vendorForm" class="needs-validation" novalidate method="POST"
                            action="{{ route('vendor.vendorRegister') }}" enctype="multipart/form-data">
                            @csrf

                            <!-- PERSONAL INFO -->
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                                Personal Information
                            </h6>

                            <!-- INDIAN MOBILE NUMBER -->
                            <div class="mb-3">
                                <label class="form-label">Mobile Number (India) *</label>
                                <input type="tel" name="mobile"
                                    class="form-control rounded-3 @error('mobile') is-invalid @enderror"
                                    placeholder="10-digit mobile number" pattern="^[6-9][0-9]{9}$" maxlength="10" required
                                    value="{{ old('mobile') }}">
                                @error('mobile')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">
                                        Enter a valid Indian mobile number (starts with 6–9, 10 digits).
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name"
                                    class="form-control rounded-3 @error('name') is-invalid @enderror" required
                                    value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">
                                        Full name is required.
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email"
                                    class="form-control rounded-3 @error('email') is-invalid @enderror"
                                    placeholder="example@email.com" required value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">
                                        Please enter a valid email address.
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select rounded-3 @error('gender') is-invalid @enderror"
                                    required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female
                                    </option>
                                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">
                                        Please select your gender.
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" name="dob"
                                    class="form-control rounded-3 @error('dob') is-invalid @enderror" required
                                    value="{{ old('dob') }}">
                                @error('dob')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">
                                        Date of birth is required.
                                    </div>
                                @enderror
                            </div>

                            <!-- BUSINESS CATEGORY SELECT -->
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

                            <!-- ADDRESS -->
                            <h6 class="fw-bold text-primary mt-4 mb-3 border-bottom pb-2">
                                Address Details
                            </h6>

                            <div class="mb-3">
                                <label class="form-label">Full Address *</label>
                                <textarea name="full_address" class="form-control rounded-3 @error('full_address') is-invalid @enderror" rows="2"
                                    required>{{ old('full_address') }}</textarea>
                                @error('full_address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">
                                        Full address is required.
                                    </div>
                                @enderror
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City *</label>
                                    <input type="text" name="city"
                                        class="form-control rounded-3 @error('city') is-invalid @enderror" required
                                        value="{{ old('city') }}">
                                    @error('city')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">
                                            City is required.
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">State *</label>
                                    <input type="text" name="state"
                                        class="form-control rounded-3 @error('state') is-invalid @enderror" required
                                        value="{{ old('state') }}">
                                    @error('state')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">
                                            State is required.
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country *</label>
                                    <input type="text" name="country"
                                        class="form-control rounded-3 @error('country') is-invalid @enderror"
                                        value="{{ old('country', 'India') }}" required>
                                    @error('country')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">
                                            Country is required.
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Postal Code *</label>
                                    <input type="text" name="postal_code"
                                        class="form-control rounded-3 @error('postal_code') is-invalid @enderror"
                                        pattern="^[1-9][0-9]{5}$" maxlength="6" placeholder="6-digit PIN code" required
                                        value="{{ old('postal_code') }}">
                                    @error('postal_code')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">
                                            Enter a valid 6-digit Indian PIN code.
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <!-- VENDOR INFO -->
                            <h6 class="fw-bold text-primary mt-4 mb-3 border-bottom pb-2">
                                Vendor Information
                            </h6>


                            <div class="mb-3">
                                <label class="form-label fw-semibold">Experience *</label>

                                <div class="card">
                                    <div class="list-group list-group-flush" id="experienceList">

                                        @php
                                            $experiences = [
                                                'beginner' => 'Just starting out (0–1 year)',
                                                'intermediate' => '1–3 years of experience',
                                                'experienced' => '3–5 years of experience',
                                                'advanced' => '5–10 years of experience',
                                                'expert' => '10–20 years of experience',
                                                'master' => 'Over 20 years of experience',
                                            ];
                                            $oldExperience = old('experience', 'beginner'); // default = beginner
                                        @endphp

                                        @foreach ($experiences as $key => $label)
                                            <label
                                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                    {{ $oldExperience === $key ? 'active' : '' }}">

                                                <div>
                                                    <input class="form-check-input me-2" type="radio" name="experience"
                                                        value="{{ $key }}" required
                                                        {{ $oldExperience === $key ? 'checked' : '' }}>
                                                    <strong class="text-capitalize">{{ $key }}</strong>
                                                    <div class="small text-muted">{{ $label }}</div>
                                                </div>

                                                <i
                                                    class="fa-solid fa-check
                        {{ $oldExperience === $key ? '' : 'd-none' }}"></i>
                                            </label>
                                        @endforeach

                                    </div>
                                </div>

                                <div class="invalid-feedback">
                                    Please select your experience level.
                                </div>

                                @error('experience')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- OPTIONAL -->
                            <div class="mb-3">
                                <label class="form-label">
                                    Referral Code <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="text" name="reference_code" class="form-control rounded-3"
                                    value="{{ old('reference_code') }}">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Profile Picture *</label>
                                <input type="file" name="profile_picture"
                                    class="form-control rounded-3 @error('profile_picture') is-invalid @enderror"
                                    accept="image/*" required>
                                @error('profile_picture')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">
                                        Profile picture is required.
                                    </div>
                                @enderror
                            </div>

                            <input type="hidden" name="role" value="vendor">

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" required name="terms_accepted"
                                    value="1">
                                <label class="form-check-label">
                                    I accept the terms & conditions
                                </label>
                                <div class="invalid-feedback">
                                    You must accept the terms & conditions.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3">
                                Register Vendor
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- BOOTSTRAP VALIDATION SCRIPT -->
    <script>
        (() => {
            const forms = document.querySelectorAll('.needs-validation');

            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })();


        document.querySelectorAll('#experienceList input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('#experienceList .list-group-item').forEach(item => {
                    item.classList.remove('active');
                    item.querySelector('.bi')?.classList.add('d-none');
                });

                const selectedItem = this.closest('.list-group-item');
                selectedItem.classList.add('active');
                selectedItem.querySelector('.bi')?.classList.remove('d-none');
            });
        });

        // Bootstrap-only behavior (no CSS)
        document.querySelectorAll('#experienceList input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove active & hide all checkmarks
                document.querySelectorAll('#experienceList .list-group-item').forEach(item => {
                    item.classList.remove('active');
                    const icon = item.querySelector('.fa-check');
                    if (icon) icon.classList.add('d-none');
                });

                // Add active & show checkmark for selected
                const selectedItem = this.closest('.list-group-item');
                selectedItem.classList.add('active');
                const selectedIcon = selectedItem.querySelector('.fa-check');
                if (selectedIcon) selectedIcon.classList.remove('d-none');
            });
        });

        // Trigger change on page load to show default selection
        const checkedRadio = document.querySelector('#experienceList input[type="radio"]:checked');
        if (checkedRadio) {
            checkedRadio.dispatchEvent(new Event('change'));
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
