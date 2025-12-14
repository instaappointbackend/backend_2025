@extends('layouts.app')


@section('title', $page->meta_title ?: $page->title)
@section('meta')
    <meta name="description" content="{{ $page->meta_description }}">
@endsection
@section('styles')
<style>
    .policy-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 60px 0 30px;
        margin-bottom: 40px;
    }
    
    .section-title {
        color: var(--primary-color);
        margin-top: 40px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
</style>
@endsection

@section('content')
    <!-- Header -->
    <header class="policy-header">
        <div class="container text-center">
            <h1 class="fw-bold">{{ $page->title }}</h1>
            <p class="lead">Last Updated: {{ $page->last_updated_at ? $page->last_updated_at->format('F d, Y') : 'N/A' }}</p>
        </div>
    </header>

    <!-- Content -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                    {!! $page->content !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection