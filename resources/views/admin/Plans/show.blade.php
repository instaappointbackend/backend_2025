@extends('admin.layouts.app')

@section('title', 'View Plan')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.plans.index') }}">Plans</a>
            </li>
            <li class="breadcrumb-item active">View</li>
        </ol>
    </nav>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-secondary">
            ← Back
        </a>

        <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-sm btn-primary">
            Edit
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ $plan->title }}</h5>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <strong>Type:</strong>
                    <p>{{ $plan->type }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Original Price:</strong>
                    <p>₹{{ number_format($plan->original_price, 2) }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Discounted Price:</strong>
                    <p>₹{{ number_format($plan->discounted_price, 2) }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Discount:</strong>
                    <p>{{ $plan->discount }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Duration:</strong>
                    <p>{{ $plan->duration }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Badge:</strong>
                    <p>
                        @if ($plan->badge)
                            <span class="badge bg-primary">{{ $plan->badge }}</span>
                        @else
                            -
                        @endif
                    </p>
                </div>

                <div class="col-md-12 mb-3">
                    <strong>Tagline:</strong>
                    <p>{{ $plan->tagline }}</p>
                </div>

            </div>

            {{-- FEATURES --}}
            <div class="mt-4">
                <h6 class="fw-bold">Features</h6>
                <hr>

                @if ($plan->features->count())
                    <ul class="list-group">
                        @foreach ($plan->features as $feature)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $feature->text }}

                                @if ($feature->included)
                                    <span class="badge bg-success">✔ Included</span>
                                @else
                                    <span class="badge bg-danger">✖ Not Included</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No features added.</p>
                @endif
            </div>

            {{-- META --}}
            <div class="mt-4">
                <h6 class="fw-bold">Meta Info</h6>
                <hr>

                <p><strong>Created At:</strong> {{ $plan->created_at->format('Y-m-d H:i') }}</p>
                <p><strong>Updated At:</strong> {{ $plan->updated_at->format('Y-m-d H:i') }}</p>
            </div>

        </div>
    </div>

@endsection
