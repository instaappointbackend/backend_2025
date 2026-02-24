@extends('admin.layouts.app')

@section('title', 'Edit Global Offer')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.offers.index') }}">Global Offers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.offers.show', $offer->id) }}" class="btn btn-info">
            <i class="fas fa-eye me-1"></i> View
        </a>
        <a href="{{ route('admin.offers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Offers
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Global Offer</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.offers.update', $offer->id) }}" method="POST">
                @csrf
                @method('PUT')
                <!-- Hidden offer_type field to ensure it remains as 'admin' -->
                <input type="hidden" name="offer_type" value="{{ \App\Models\Offer::TYPE_ADMIN }}">
                @if ($offer->new_user_only)
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="new_user_only" name="new_user_only"
                            value="1" {{ old('new_user_only', $offer->new_user_only ?? false) ? 'checked' : '' }}
                            {{ $offer->new_user_only ? 'disabled' : '' }}>
                        <label class="form-check-label" for="new_user_only">New User Only Coupon</label>
                    </div>
                @endif


                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Offer Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                                name="title" value="{{ old('title', $offer->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="coupon_code" class="form-label">Coupon Code <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control @error('coupon_code') is-invalid @enderror"
                                    id="coupon_code" name="coupon_code"
                                    value="{{ old('coupon_code', $offer->coupon_code) }}" required
                                    {{ $offer->new_user_only ? 'readonly' : '' }}>
                                <button class="btn btn-outline-secondary" type="button" id="generateCode"
                                    {{ $offer->new_user_only ? 'disabled' : '' }}>Generate</button>
                            </div>
                            <div class="form-text">Enter a unique code for customers to apply this offer.</div>
                            @error('coupon_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                        rows="4" required>{{ old('description', $offer->description) }}</textarea>
                    <div class="form-text">Provide details about this offer that will be visible to customers.</div>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="row">
                    <!-- Discount Type -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="discount_type" class="form-label">Discount Type <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="discount_type" name="discount_type">
                                <option value="percentage"
                                    {{ old('discount_type', $offer->discount_type ?? '') == 'percentage' ? 'selected' : '' }}>
                                    Percentage</option>
                                <option value="fixed"
                                    {{ old('discount_type', $offer->discount_type ?? '') == 'fixed' ? 'selected' : '' }}>
                                    Fixed</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6" id="discount_percentage_wrapper">
                        <div class="mb-3">
                            <label for="discount_percentage" class="form-label">Discount Percentage</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="discount_percentage"
                                    name="discount_percentage"
                                    value="{{ old('discount_percentage', $offer->discount_percentage ?? '') }}"
                                    min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6" id="discount_fixed_wrapper">
                        <div class="mb-3">
                            <label for="discount_fixed" class="form-label">Fixed Discount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="discount_fixed" name="discount_fixed"
                                    value="{{ old('discount_fixed', $offer->discount_fixed ?? '') }}" min="0"
                                    step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    {{-- <div class="col-md-6">
                        <div class="mb-3">
                            <label for="discount_percentage" class="form-label">Discount Percentage <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                    class="form-control @error('discount_percentage') is-invalid @enderror"
                                    id="discount_percentage" name="discount_percentage"
                                    value="{{ old('discount_percentage', $offer->discount_percentage) }}" min="0"
                                    max="100" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                            @error('discount_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div> --}}

                    {{-- <div class="col-md-6">
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Usage Limit</label>
                            <input type="number" class="form-control @error('usage_limit') is-invalid @enderror"
                                id="usage_limit" name="usage_limit"
                                value="{{ old('usage_limit', $offer->usage_limit) }}" min="1">
                            <div class="form-text">Maximum number of times this offer can be used. Leave empty for
                                unlimited.</div>
                            @error('usage_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div> --}}
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="start_date" name="start_date"
                                value="{{ old('start_date', $offer->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                id="end_date" name="end_date"
                                value="{{ old('end_date', $offer->end_date->format('Y-m-d')) }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Usage Limit</label>
                            <input type="number" class="form-control @error('usage_limit') is-invalid @enderror"
                                id="usage_limit" name="usage_limit"
                                value="{{ old('usage_limit', $offer->usage_limit) }}" min="1">
                            <div class="form-text">Maximum number of times this offer can be used. Leave empty for
                                unlimited.</div>
                            @error('usage_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', $offer->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                            <div class="form-text">Inactive offers will not be available to customers.</div>
                        </div>
                    </div>

                </div>


                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                    <button type="submit" class="btn btn-primary">Update Offer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Generate random coupon code
        document.getElementById('generateCode').addEventListener('click', function() {
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            const length = 8;

            for (let i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() * characters.length));
            }

            document.getElementById('coupon_code').value = result;
        });

        // Validate end date is after start date
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = this.value;

            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                alert('End date must be after start date');
                this.value = '';
            }
        });

        function updateDiscountFields() {
            const isNewUser = document.getElementById('new_user_only').checked;
            const discountType = document.getElementById('discount_type').value;

            const percWrapper = document.getElementById('discount_percentage_wrapper');
            const fixedWrapper = document.getElementById('discount_fixed_wrapper');

            if (isNewUser) {
                // Force fixed discount
                document.getElementById('discount_type').value = 'fixed';
                percWrapper.style.display = 'none';
                fixedWrapper.style.display = 'block';
                document.getElementById('discount_percentage').value = '';
            } else {
                // Show/hide based on selected discount type
                if (discountType === 'percentage') {
                    percWrapper.style.display = 'block';
                    fixedWrapper.style.display = 'none';
                    document.getElementById('discount_fixed').value = '';
                } else {
                    percWrapper.style.display = 'none';
                    fixedWrapper.style.display = 'block';
                    document.getElementById('discount_percentage').value = '';
                }
            }
        }

        // Event listeners
        document.getElementById('new_user_only').addEventListener('change', updateDiscountFields);
        document.getElementById('discount_type').addEventListener('change', updateDiscountFields);
        document.addEventListener('DOMContentLoaded', updateDiscountFields);
    </script>
@endsection
