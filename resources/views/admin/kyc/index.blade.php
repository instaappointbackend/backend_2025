@extends('admin.layouts.app')

@section('title', 'KYC Verification')

@section('page-title', 'KYC Verification')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">KYC Verification</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">KYC Verification Requests</h5>

            <div class="search-filter">
                <form action="{{ route('admin.kyc.index') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..."
                            value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>ID</th>
                            <th>Vendor</th>
                            <th>Business Name</th>
                            <th>Business Type</th>
                            <th>Documents Status</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kycDocuments as $index=>$kyc)
                            <tr>
                                <td>{{ $kycDocuments->firstItem() + $index }}</td>
                                <td>{{ $kyc->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <img src="{{ $kyc->user->profile_picture ? asset('storage/' . $kyc->user->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                                alt="{{ $kyc->user->name }}" class="avatar-img rounded-circle"
                                                width="40" height="40">
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $kyc->user->name }}</h6>
                                            <small>{{ $kyc->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $kyc->business_name }}</td>
                                <td>{{ $kyc->businessCategory->name ?? 'N/A' }}</td>
                                <td>
                                    <span
                                        class="badge bg-{{ isset($kyc->is_aadhar_verified) && $kyc->is_aadhar_verified ? 'success' : 'warning text-dark' }}"
                                        data-bs-toggle="tooltip" title="Aadhar">
                                        <i class="fas fa-id-card"></i>
                                    </span>
                                    <span
                                        class="badge bg-{{ isset($kyc->is_pan_verified) && $kyc->is_pan_verified ? 'success' : 'warning text-dark' }}"
                                        data-bs-toggle="tooltip" title="PAN">
                                        <i class="fas fa-file-alt"></i>
                                    </span>
                                    @if ($kyc->bank_attachment)
                                        <span
                                            class="badge bg-{{ isset($kyc->is_bank_verified) && $kyc->is_bank_verified ? 'success' : 'warning text-dark' }}"
                                            data-bs-toggle="tooltip" title="Bank Details">
                                            <i class="fas fa-university"></i>
                                        </span>
                                    @endif
                                    @if ($kyc->business_logo)
                                        <span
                                            class="badge bg-{{ $kyc->is_business_verified ? 'success' : 'warning text-dark' }}"
                                            data-bs-toggle="tooltip" title="Business Logo">
                                            <i class="fas fa-building"></i>
                                        </span>
                                    @endif
                                    @if ($kyc->identity_document)
                                        <span class="badge bg-primary" data-bs-toggle="tooltip" title="Identity Document">
                                            <i class="fas fa-passport"></i>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($kyc->is_business_verified)
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $kyc->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="d-flex">
                                        <a href="{{ route('admin.kyc.show', $kyc->id) }}" class="btn btn-sm btn-info me-1"
                                            data-bs-toggle="tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if (!$kyc->is_business_verified)
                                            <button type="button" class="btn btn-sm btn-success me-1"
                                                data-bs-toggle="modal" data-bs-target="#approveModal{{ $kyc->id }}"
                                                title="Approve KYC">
                                                <i class="fas fa-check"></i>
                                            </button>

                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                data-bs-target="#rejectModal{{ $kyc->id }}" title="Reject KYC">
                                                <i class="fas fa-times"></i>
                                            </button>

                                            <!-- Approve Modal -->
                                            <div class="modal fade" id="approveModal{{ $kyc->id }}" tabindex="-1"
                                                aria-labelledby="approveModalLabel{{ $kyc->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.kyc.approve', $kyc->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="approveModalLabel{{ $kyc->id }}">Approve KYC
                                                                </h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to approve the KYC for
                                                                    <strong>{{ $kyc->user->name }}</strong>?
                                                                </p>
                                                                <p>This will mark all documents as verified and complete the
                                                                    KYC process.</p>

                                                                <div class="mb-3">
                                                                    <label for="feedback{{ $kyc->id }}"
                                                                        class="form-label">Feedback (Optional)</label>
                                                                    <textarea class="form-control" id="feedback{{ $kyc->id }}" name="feedback" rows="3"
                                                                        placeholder="Enter any feedback or notes"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit"
                                                                    class="btn btn-success">Approve</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Reject Modal -->
                                            <div class="modal fade" id="rejectModal{{ $kyc->id }}" tabindex="-1"
                                                aria-labelledby="rejectModalLabel{{ $kyc->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.kyc.reject', $kyc->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"
                                                                    id="rejectModalLabel{{ $kyc->id }}">Reject KYC
                                                                </h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to reject the KYC for
                                                                    <strong>{{ $kyc->user->name }}</strong>?
                                                                </p>

                                                                <div class="mb-3">
                                                                    <label for="rejectFeedback{{ $kyc->id }}"
                                                                        class="form-label">Reason for Rejection<span
                                                                            class="text-danger">*</span></label>
                                                                    <textarea class="form-control" id="rejectFeedback{{ $kyc->id }}" name="feedback" rows="3"
                                                                        placeholder="Enter the reason for rejection" required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit"
                                                                    class="btn btn-danger">Reject</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No KYC documents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($kycDocuments->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $kycDocuments->firstItem() ?? 0 }} to {{ $kycDocuments->lastItem() ?? 0 }} of
                        {{ $kycDocuments->total() }}
                        kycDocuments
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $kycDocuments->onEachSide(5)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
@endsection
