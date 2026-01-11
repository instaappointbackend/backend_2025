@extends('admin.layouts.app')

@section('title', 'KYC Details')

@section('page-title', 'KYC Verification Details')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.kyc.index') }}">KYC Verification</a></li>
            <li class="breadcrumb-item active" aria-current="page">Details</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    @if (!$kycDocument->is_business_verified)
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#approveModal">
            <i class="fas fa-check-circle me-1"></i> Approve All
        </button>

        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="fas fa-times-circle me-1"></i> Reject
        </button>
    @endif

    <a href="{{ route('admin.kyc.index') }}" class="btn btn-secondary ms-2">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            @if ($kycDocument->user)
                <!-- Vendor Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Vendor Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="{{ $kycDocument->user->profile_picture ? asset('storage/' . $kycDocument->user->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                alt="{{ $kycDocument->user->name }}" class="img-fluid rounded-circle"
                                style="width: 100px; height: 100px; object-fit: cover;">
                            <h5 class="mt-3 mb-0">{{ $kycDocument->user->name }}</h5>
                            <p class="text-muted">{{ $kycDocument->user->email }}</p>

                            <div class="d-flex justify-content-center gap-2 mt-2">
                                <span class="badge bg-secondary">{{ $kycDocument->user->mobile }}</span>
                                <span
                                    class="badge {{ $kycDocument->user->is_kyc_completed ? 'bg-success' : 'bg-warning' }}">
                                    {{ $kycDocument->user->is_kyc_completed ? 'Verified' : 'Pending' }}
                                </span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Personal Details</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Gender</small>
                                    <span>{{ ucfirst($kycDocument->user->gender ?? 'Not specified') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Date of Birth</small>
                                    <span>{{ $kycDocument->user->dob ? $kycDocument->user->dob->format('M d, Y') : 'Not specified' }}</span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Address</small>
                                    <span>{{ $kycDocument->user->full_address ?? 'Not specified' }}</span>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Account Details</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">User ID</small>
                                    <span>#{{ $kycDocument->user->id }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Joined On</small>
                                    <span>{{ $kycDocument->user->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Status</small>
                                    <span class="badge {{ $kycDocument->user->status ? 'bg-success' : 'bg-danger' }}">
                                        {{ $kycDocument->user->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Role</small>
                                    <span class="badge bg-primary">{{ ucfirst($kycDocument->user->role) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5>User Not Found</h5>
                            <p>The user associated with this KYC document could not be found.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-8">
            <!-- KYC Documents -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">KYC Documents</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="kycTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="documents-tab" data-bs-toggle="tab"
                                data-bs-target="#documents-tab-pane" type="button" role="tab"
                                aria-controls="documents-tab-pane" aria-selected="true">
                                <i class="fas fa-id-card me-1"></i> Documents
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="business-tab" data-bs-toggle="tab"
                                data-bs-target="#business-tab-pane" type="button" role="tab"
                                aria-controls="business-tab-pane" aria-selected="false">
                                <i class="fas fa-building me-1"></i> Business Details
                                <span
                                    class="badge {{ $kycDocument->is_business_verified ? 'bg-success' : 'bg-warning text-dark' }} ms-1">
                                    {{ $kycDocument->is_business_verified ? 'Verified' : 'Pending' }}
                                </span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-4" id="kycTabsContent">
                        <!-- Documents Tab -->
                        <div class="tab-pane fade show active" id="documents-tab-pane" role="tabpanel"
                            aria-labelledby="documents-tab" tabindex="0">
                            <div class="accordion" id="documentsAccordion">
                                <!-- Aadhaar Details -->
                                <div class="accordion-item mb-3">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseAadhar" aria-expanded="true"
                                            aria-controls="collapseAadhar">
                                            Aadhaar Details
                                            <span
                                                class="badge {{ isset($kycDocument->is_aadhar_verified) && $kycDocument->is_aadhar_verified ? 'bg-success' : 'bg-warning text-dark' }} ms-2">
                                                {{ isset($kycDocument->is_aadhar_verified) && $kycDocument->is_aadhar_verified ? 'Verified' : 'Pending' }}
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="collapseAadhar" class="accordion-collapse collapse show"
                                        data-bs-parent="#documentsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <h6 class="text-muted mb-2">Aadhaar Details</h6>
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block">Aadhaar Number</small>
                                                            <span class="fs-5">{{ $kycDocument->aadhar_number }}</span>
                                                        </div>

                                                        <form action="{{ route('admin.kyc.verify', $kycDocument) }}"
                                                            method="POST" class="mt-4">
                                                            @csrf
                                                            <input type="hidden" name="field"
                                                                value="is_aadhar_verified">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    role="switch" id="is_aadhar_verified"
                                                                    name="is_aadhar_verified" value="1"
                                                                    {{ isset($kycDocument->is_aadhar_verified) && $kycDocument->is_aadhar_verified ? 'checked' : '' }}
                                                                    onchange="this.form.submit()">
                                                                <label class="form-check-label"
                                                                    for="is_aadhar_verified">Mark Aadhar as
                                                                    Verified</label>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-4 text-center">
                                                        @if ($kycDocument->aadhar_attachment)
                                                            <div class="card">
                                                                <div class="card-body p-2">
                                                                    <h6 class="text-muted mb-2">Aadhaar Attachment</h6>
                                                                    {{-- @dd($kycDocument) --}}
                                                                    <img src="{{ asset('storage/' . $kycDocument->aadhar_attachment) }}"
                                                                        alt="Aadhar Card" class="img-fluid mb-2"
                                                                        style="max-height: 200px;">
                                                                    <div class="d-flex justify-content-center">
                                                                        <a href="{{ asset('storage/' . $kycDocument->aadhar_attachment) }}"
                                                                            target="_blank"
                                                                            class="btn btn-sm btn-primary me-2">
                                                                            <i class="fas fa-eye me-1"></i> View
                                                                        </a>
                                                                        <a href="{{ route('admin.kyc.download', ['kycDocument' => $kycDocument->id, 'type' => 'aadhar']) }}"
                                                                            class="btn btn-sm btn-success">
                                                                            <i class="fas fa-download me-1"></i> Download
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="alert alert-warning">
                                                                No Aadhaar attachment found.
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PAN Details -->
                                <div class="accordion-item mb-3">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapsePan" aria-expanded="false"
                                            aria-controls="collapsePan">
                                            PAN Details
                                            <span
                                                class="badge {{ isset($kycDocument->is_pan_verified) && $kycDocument->is_pan_verified ? 'bg-success' : 'bg-warning text-dark' }} ms-2">
                                                {{ isset($kycDocument->is_pan_verified) && $kycDocument->is_pan_verified ? 'Verified' : 'Pending' }}
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="collapsePan" class="accordion-collapse collapse"
                                        data-bs-parent="#documentsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <h6 class="text-muted mb-2">PAN Details</h6>
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block">PAN Number</small>
                                                            <span class="fs-5">{{ $kycDocument->pan_number }}</span>
                                                        </div>

                                                        <form action="{{ route('admin.kyc.verify', $kycDocument) }}"
                                                            method="POST" class="mt-4">
                                                            @csrf
                                                            <input type="hidden" name="field" value="is_pan_verified">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    role="switch" id="is_pan_verified"
                                                                    name="is_pan_verified" value="1"
                                                                    {{ isset($kycDocument->is_pan_verified) && $kycDocument->is_pan_verified ? 'checked' : '' }}
                                                                    onchange="this.form.submit()">
                                                                <label class="form-check-label" for="is_pan_verified">Mark
                                                                    PAN as Verified</label>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-4 text-center">
                                                        @if ($kycDocument->pan_attachment)
                                                            <div class="card">
                                                                <div class="card-body p-2">
                                                                    <h6 class="text-muted mb-2">PAN Attachment</h6>
                                                                    <img src="{{ asset('storage/' . $kycDocument->pan_attachment) }}"
                                                                        alt="PAN Card" class="img-fluid mb-2"
                                                                        style="max-height: 200px;">
                                                                    <div class="d-flex justify-content-center">
                                                                        <a href="{{ asset('storage/' . $kycDocument->pan_attachment) }}"
                                                                            target="_blank"
                                                                            class="btn btn-sm btn-primary me-2">
                                                                            <i class="fas fa-eye me-1"></i> View
                                                                        </a>
                                                                        <a href="{{ route('admin.kyc.download', ['kycDocument' => $kycDocument->id, 'type' => 'pan']) }}"
                                                                            class="btn btn-sm btn-success">
                                                                            <i class="fas fa-download me-1"></i> Download
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="alert alert-warning">
                                                                No PAN attachment found.
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Details -->
                                @if ($kycDocument->bank_name)
                                    <div class="accordion-item mb-3">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapseBank"
                                                aria-expanded="false" aria-controls="collapseBank">
                                                Bank Details
                                                <span
                                                    class="badge {{ isset($kycDocument->is_bank_verified) && $kycDocument->is_bank_verified ? 'bg-success' : 'bg-warning text-dark' }} ms-2">
                                                    {{ isset($kycDocument->is_bank_verified) && $kycDocument->is_bank_verified ? 'Verified' : 'Pending' }}
                                                </span>
                                            </button>
                                        </h2>
                                        <div id="collapseBank" class="accordion-collapse collapse"
                                            data-bs-parent="#documentsAccordion">
                                            <div class="accordion-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <h6 class="text-muted mb-2">Bank Details</h6>
                                                            <div class="mb-3">
                                                                <small class="text-muted d-block">Bank Name</small>
                                                                <span>{{ $kycDocument->bank_name }}</span>
                                                            </div>
                                                            <div class="mb-3">
                                                                <small class="text-muted d-block">Account Number</small>
                                                                <span>{{ $kycDocument->bank_account }}</span>
                                                            </div>
                                                            <div class="mb-3">
                                                                <small class="text-muted d-block">IFSC Code</small>
                                                                <span>{{ $kycDocument->ifsc_code }}</span>
                                                            </div>

                                                            <form action="{{ route('admin.kyc.verify', $kycDocument) }}"
                                                                method="POST" class="mt-4">
                                                                @csrf
                                                                <input type="hidden" name="field"
                                                                    value="is_bank_verified">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        role="switch" id="is_bank_verified"
                                                                        name="is_bank_verified" value="1"
                                                                        {{ isset($kycDocument->is_bank_verified) && $kycDocument->is_bank_verified ? 'checked' : '' }}
                                                                        onchange="this.form.submit()">
                                                                    <label class="form-check-label"
                                                                        for="is_bank_verified">Mark Bank Details as
                                                                        Verified</label>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4 text-center">
                                                            @if ($kycDocument->bank_attachment)
                                                                <div class="card">
                                                                    <div class="card-body p-2">
                                                                        <h6 class="text-muted mb-2">Bank Document</h6>
                                                                        <img src="{{ asset('storage/' . $kycDocument->bank_attachment) }}"
                                                                            alt="Bank Document" class="img-fluid mb-2"
                                                                            style="max-height: 200px;">
                                                                        <div class="d-flex justify-content-center">
                                                                            <a href="{{ asset('storage/' . $kycDocument->bank_attachment) }}"
                                                                                target="_blank"
                                                                                class="btn btn-sm btn-primary me-2">
                                                                                <i class="fas fa-eye me-1"></i> View
                                                                            </a>
                                                                            <a href="{{ route('admin.kyc.download', ['kycDocument' => $kycDocument->id, 'type' => 'bank']) }}"
                                                                                class="btn btn-sm btn-success">
                                                                                <i class="fas fa-download me-1"></i>
                                                                                Download
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <div class="alert alert-warning">
                                                                    No bank attachment found.
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Business Tab -->
                        <div class="tab-pane fade" id="business-tab-pane" role="tabpanel" aria-labelledby="business-tab"
                            tabindex="0">
                            <div class="card border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <h5 class="mb-3">Business Details</h5>

                                                <div class="mb-3">
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Business Name</small>
                                                        <h5>{{ $kycDocument->business_name }}</h5>
                                                    </div>

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Business Type</small>
                                                        <span>{{ $kycDocument->businessCategory->name ?? 'N/A' }}</span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Established Date</small>
                                                        <span>{{ $kycDocument->business_established_date ? $kycDocument->business_established_date->format('M d, Y') : 'Not specified' }}</span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Description</small>
                                                        <p>{{ $kycDocument->description ?? 'Not provided' }}</p>
                                                    </div>

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Business Address</small>
                                                        <p>{{ $kycDocument->business_address ?? 'Not provided' }}</p>
                                                        <p>
                                                            {{ $kycDocument->street ?? '' }}
                                                            {{ $kycDocument->city ? ', ' . $kycDocument->city : '' }}
                                                            {{ $kycDocument->state ? ', ' . $kycDocument->state : '' }}
                                                            {{ $kycDocument->postal_code ? ' - ' . $kycDocument->postal_code : '' }}
                                                            {{ $kycDocument->country ? ', ' . $kycDocument->country : '' }}
                                                        </p>
                                                    </div>
                                                </div>

                                                @if ($kycDocument->latitude && $kycDocument->longitude)
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Business Location</small>
                                                        <div id="business-map" class="mt-2"
                                                            style="height: 200px; width: 100%;"></div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <!-- Business Logo -->
                                            @if ($kycDocument->business_logo)
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Business Logo</h6>
                                                    </div>
                                                    <div class="card-body p-3 text-center">
                                                        <img src="{{ asset('storage/' . $kycDocument->business_logo) }}"
                                                            alt="Business Logo" class="img-fluid mb-2"
                                                            style="max-height: 150px;">
                                                        <div class="d-flex justify-content-center mt-2">
                                                            <a href="{{ asset('storage/' . $kycDocument->business_logo) }}"
                                                                target="_blank" class="btn btn-sm btn-primary me-2">
                                                                <i class="fas fa-eye me-1"></i> View
                                                            </a>
                                                            <a href="{{ route('admin.kyc.download', ['kycDocument' => $kycDocument->id, 'type' => 'business_logo']) }}"
                                                                class="btn btn-sm btn-success">
                                                                <i class="fas fa-download me-1"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Identity Document -->
                                            @if ($kycDocument->identity_document)
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Identity Document</h6>
                                                    </div>
                                                    <div class="card-body p-3 text-center">
                                                        <img src="{{ asset('storage/' . $kycDocument->identity_document) }}"
                                                            alt="Identity Document" class="img-fluid mb-2"
                                                            style="max-height: 150px;">
                                                        <div class="d-flex justify-content-center mt-2">
                                                            <a href="{{ asset('storage/' . $kycDocument->identity_document) }}"
                                                                target="_blank" class="btn btn-sm btn-primary me-2">
                                                                <i class="fas fa-eye me-1"></i> View
                                                            </a>
                                                            <a href="{{ route('admin.kyc.download', ['kycDocument' => $kycDocument->id, 'type' => 'identity_document']) }}"
                                                                class="btn btn-sm btn-success">
                                                                <i class="fas fa-download me-1"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Business Verification -->
                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Business Verification</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form action="{{ route('admin.kyc.verify', $kycDocument) }}"
                                                        method="POST" class="mt-1">
                                                        @csrf
                                                        <input type="hidden" name="field"
                                                            value="is_business_verified">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                role="switch" id="is_business_verified"
                                                                name="is_business_verified" value="1"
                                                                {{ $kycDocument->is_business_verified ? 'checked' : '' }}
                                                                onchange="this.form.submit()">
                                                            <label class="form-check-label" for="is_business_verified">
                                                                <span class="fw-bold">Mark Business as Verified</span>
                                                                <small class="d-block text-muted mt-1">This will approve
                                                                    the entire business profile and complete KYC
                                                                    verification.</small>
                                                            </label>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Verification Feedback</h5>
                </div>
                <div class="card-body">
                    @if ($kycDocument->feedback)
                        <div class="alert {{ $kycDocument->is_business_verified ? 'alert-success' : 'alert-danger' }}">
                            <h6 class="alert-heading">
                                {{ $kycDocument->is_business_verified ? 'Approval Notes' : 'Rejection Reason' }}</h6>
                            <p class="mb-0">{{ $kycDocument->feedback }}</p>
                        </div>
                    @endif

                    <form action="{{ route('admin.kyc.update', $kycDocument->id) }}" method="POST" class="mt-3">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="feedback" class="form-label">Admin Feedback</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="3"
                                placeholder="Enter feedback for vendor...">{{ $kycDocument->feedback }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Feedback</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.kyc.approve', $kycDocument->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">Approve KYC</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($kycDocument->user)
                            <p>Are you sure you want to approve the KYC for
                                <strong>{{ $kycDocument->user->name }}</strong>?
                            </p>
                        @else
                            <p>Are you sure you want to approve this KYC document?</p>
                        @endif
                        <p>This will mark all documents as verified and complete the KYC process.</p>

                        <div class="mb-3">
                            <label for="feedback" class="form-label">

                                <label for="feedback" class="form-label">Feedback (Optional)</label>
                                <textarea class="form-control" id="feedback" name="feedback" rows="3"
                                    placeholder="Enter any feedback or notes">{{ $kycDocument->feedback }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve All</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.kyc.reject', $kycDocument->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">Reject KYC</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($kycDocument->user)
                            <p>Are you sure you want to reject the KYC for <strong>{{ $kycDocument->user->name }}</strong>?
                            </p>
                        @else
                            <p>Are you sure you want to reject this KYC document?</p>
                        @endif

                        <div class="mb-3">
                            <label for="rejectFeedback" class="form-label">Reason for Rejection<span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="rejectFeedback" name="feedback" rows="3"
                                placeholder="Enter the reason for rejection" required>{{ $kycDocument->feedback }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @if ($kycDocument->latitude && $kycDocument->longitude)
        <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_API_KEY') }}&callback=initMap" async defer>
        </script>
        <script>
            function initMap() {
                const location = {
                    lat: {{ $kycDocument->latitude }},
                    lng: {{ $kycDocument->longitude }}
                };

                // Create the business map
                const businessMap = new google.maps.Map(document.getElementById("business-map"), {
                    zoom: 15,
                    center: location,
                });

                // Add a marker for the business location
                const businessMarker = new google.maps.Marker({
                    position: location,
                    map: businessMap,
                    title: "{{ $kycDocument->business_name }}'s Location"
                });
            }
        </script>
    @endif

    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Handle user null check for approval/rejection modals
        document.addEventListener('DOMContentLoaded', function() {
            // Add null checks for other user references
            const userReferences = document.querySelectorAll('[data-user-check]');
            userReferences.forEach(element => {
                const userExists = {{ $kycDocument->user ? 'true' : 'false' }};
                if (!userExists) {
                    element.style.display = 'none';
                }
            });
        });
    </script>
@endsection
