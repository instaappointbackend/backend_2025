@extends('admin.layouts.app')

@section('title', 'Plans')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Plans</li>
        </ol>
    </nav>
@endsection

@section('content')
    @php
        $isEdit = isset($plan);
    @endphp

    <div class="mb-5">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-secondary">
            ← Back
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                {{ $isEdit ? 'Edit Plan' : 'Create Plan' }}
            </h5>


        </div>

        <div class="card-body">
            <form method="POST"
                action="{{ $isEdit ? route('admin.plans.update', $plan->id) : route('admin.plans.store') }}">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="row">

                    {{-- Type --}}
                    <div class="col-md-4 mb-3">
                        <label>Type</label>
                        <input type="text" name="type" class="form-control"
                            value="{{ old('type', $plan->type ?? '') }}" placeholder="like normal or social">
                    </div>

                    {{-- Name --}}
                    <div class="col-md-8 mb-3">
                        <label>Name</label>
                        <input type="text" name="title" class="form-control"
                            value="{{ old('title', $plan->title ?? '') }}" placeholder="Enter plan title">
                    </div>

                    {{-- Original Price --}}
                    <div class="col-md-4 mb-3">
                        <label>Original Price</label>
                        <input type="number" name="original_price" class="form-control"
                            value="{{ old('original_price', $plan->original_price ?? '') }}">
                    </div>

                    {{-- Discounted Price --}}
                    <div class="col-md-4 mb-3">
                        <label>Discounted Price</label>
                        <input type="number" name="discounted_price" class="form-control"
                            value="{{ old('discounted_price', $plan->discounted_price ?? '') }}">
                    </div>

                    {{-- Discount Text --}}
                    <div class="col-md-4 mb-3">
                        <label>Discount Text</label>
                        <input type="text" name="discount" class="form-control"
                            value="{{ old('discount', $plan->discount ?? '') }}" placeholder="e.g. 20% OFF">
                    </div>

                    {{-- Duration --}}
                    <div class="col-md-4 mb-3">
                        <label>Duration</label>
                        <input type="text" name="duration" class="form-control"
                            value="{{ old('duration', $plan->duration ?? '') }}" placeholder="1 month">
                    </div>

                    {{-- Badge --}}
                    <div class="col-md-4 mb-3">
                        <label>Badge</label>
                        <input type="text" name="badge" class="form-control"
                            value="{{ old('badge', $plan->badge ?? '') }}" placeholder="Enter badge e.g. Popular">
                    </div>

                    {{-- Tagline --}}
                    <div class="col-md-12 mb-3">
                        <label>Tagline</label>
                        <input type="text" name="tagline" class="form-control"
                            value="{{ old('tagline', $plan->tagline ?? '') }}" placeholder="Short description">
                    </div>

                </div>

                {{-- FEATURES --}}
                <div class="mt-3">
                    <h6 class="fw-bold">Plan Features</h6>
                    <hr>

                    <div id="features-wrapper">
                        @if ($isEdit && $plan->features->count())
                            @foreach ($plan->features as $i => $feature)
                                <div class="feature-item d-flex mb-2">
                                    <input type="text" name="features[{{ $i }}][text]" class="form-control"
                                        value="{{ $feature->text }}" placeholder="Feature">

                                    <select name="features[{{ $i }}][included]" class="form-control ms-2">
                                        <option value="1" {{ $feature->included ? 'selected' : '' }}>Included</option>
                                        <option value="0" {{ !$feature->included ? 'selected' : '' }}>Not Included
                                        </option>
                                    </select>

                                    <button type="button" class="btn btn-danger ms-2"
                                        onclick="removeFeature(this)">X</button>
                                </div>
                            @endforeach
                        @else
                            <div class="feature-item d-flex mb-2">
                                <input type="text" name="features[0][text]" class="form-control" placeholder="Feature">

                                <select name="features[0][included]" class="form-control ms-2">
                                    <option value="1">Included</option>
                                    <option value="0">Not Included</option>
                                </select>

                                <button type="button" class="btn btn-danger ms-2" onclick="removeFeature(this)">X</button>
                            </div>
                        @endif
                    </div>

                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addFeature()">
                        + Add Feature
                    </button>

                    <br><br>

                    <button type="submit" class="btn btn-primary">
                        {{ $isEdit ? 'Update Plan' : 'Create Plan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let index = {{ isset($plan) ? $plan->features->count() : 1 }};

        function addFeature() {
            let html = `
                <div class="feature-item d-flex mb-2">
                    <input type="text" name="features[${index}][text]" class="form-control" placeholder="Feature">

                    <select name="features[${index}][included]" class="form-control ms-2">
                        <option value="1">Included</option>
                        <option value="0">Not Included</option>
                    </select>

                    <button type="button" class="btn btn-danger ms-2" onclick="removeFeature(this)">X</button>
                </div>`;

            document.getElementById('features-wrapper').insertAdjacentHTML('beforeend', html);
            index++;
        }

        function removeFeature(button) {
            button.closest('.feature-item').remove();
        }
    </script>
@endsection
