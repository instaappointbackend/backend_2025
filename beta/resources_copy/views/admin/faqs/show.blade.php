@extends('admin.layouts.app')

@section('title', 'View FAQ')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.faqs.index') }}">FAQs</a></li>
            <li class="breadcrumb-item active" aria-current="page">View</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.faqs.edit', $faq->id) }}" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <form action="{{ route('admin.faqs.destroy', $faq->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
        <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to FAQs
        </a>
    </div>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">FAQ Details</h5>
            <span class="badge {{ $faq->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $faq->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="mb-4">
                        <h4 class="text-primary mb-3">Question</h4>
                        <div class="p-3 bg-light rounded">
                            <h5>{{ $faq->question }}</h5>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="text-primary mb-3">Answer</h4>
                        <div class="p-3 bg-light rounded">
                            {!! nl2br(e($faq->answer)) !!}
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Details</h6>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="text-muted">ID:</span>
                                            <span class="fw-medium">{{ $faq->id }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="text-muted">Status:</span>
                                            <span class="fw-medium">
                                                @if($faq->is_active)
                                                    <span class="text-success">Active</span>
                                                @else
                                                    <span class="text-secondary">Inactive</span>
                                                @endif
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="text-muted">Created:</span>
                                            <span class="fw-medium">{{ $faq->created_at->format('M d, Y H:i A') }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span class="text-muted">Last Updated:</span>
                                            <span class="fw-medium">{{ $faq->updated_at->format('M d, Y H:i A') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="accordion" id="faqPreview">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#previewCollapse" aria-expanded="true" aria-controls="previewCollapse">
                                                    {{ $faq->question }}
                                                </button>
                                            </h2>
                                            <div id="previewCollapse" class="accordion-collapse collapse show" data-bs-parent="#faqPreview">
                                                <div class="accordion-body">
                                                    {!! nl2br(e($faq->answer)) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-center text-muted">
                                        <small>This is how the FAQ will appear to users</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection