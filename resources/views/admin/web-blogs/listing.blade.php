@extends('layouts.app')

@section('title', 'InstaAppoint - Quick Appointment Booking')
@section('styles')
    <style>
        .card-link-wrapper {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: block;
            border-radius: 12px;
        }

        .card-link-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            text-decoration: none;
        }

        .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }

        .card-img-top {
            object-fit: cover;
            height: 200px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            transition: transform 0.3s ease;
        }

        .card-link-wrapper:hover .card-img-top {
            transform: scale(1.05);
        }

        .card-body {
            padding: 1rem 1.2rem;
            background: #fff;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
        }

        .card-text {
            color: #666;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .fixed-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
    </style>
@endsection

@section('content')
    <!-- Header -->

    <header class="py-4 bg-dark text-white text-center">
        <div class="container">
            <h1 class="h3">InstaAppoint Blogs </h1>
            <p class="lead">Latest posts from InstaAppoint</p>
        </div>
    </header>

    <main class="mx-5 py-4" style="">
        <div class="card shadow-sm">
            {{-- <div class="card-header bg-primary text-white">
                <h4 class="mb-0">All Blog Posts</h4>
            </div> --}}
            <div class="card-body">
                <div class="row g-4">
                    @forelse ($posts as $post)
                        <div class="col-sm-12 col-md-6 col-lg-4 mb-4 d-flex justify-content-center">
                            <a href="{{ route('blog', $post->slug) }}"
                                class="card-link-wrapper text-decoration-none text-reset w-100">
                                <div class="card shadow-sm" style="width: 100%;">
                                    <div class="card-img-wrapper">
                                        <picture>
                                            {{-- Optional WebP source --}}
                                            {{-- <source srcset="{{ asset('storage/' . $post->image_webp) }}" type="image/webp"> --}}
                                            <img src="{{ asset('storage/' . $post->image_path) }}"
                                                class="card-img-top fixed-img" alt="{{ $post->title }}" loading="lazy" />
                                        </picture>
                                    </div>

                                    <div class="card-body">
                                        <h5 class="card-title">{{ $post->title }}</h5>
                                        <p class="card-text">{{ $post->sub_title }}</p>
                                        {{-- <a href="#" class="btn btn-primary">Read More</a> --}}
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-5 w-100">
                            <h3 class="mb-3">No blog found</h3>
                            <p class="text-muted">Sorry, there are no blogs to display right now. Please check back later.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Pagination below the card --}}
            @if ($posts->hasPages())
                <div class="card-footer bg-light">
                    <div class="custom-pagination-container d-flex justify-content-center mt-2 pagination">
                        {{ $posts->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </main>



    <!-- Contact Section -->
    {{-- <section class="py-2 bg-light" id="contact">
        <div class="container py-2">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h2 class="fw-bold mb-4">Get In Touch</h2>
                    <p class="mb-4">Have questions or feedback? We'd love to hear from you. Reach out to our team using
                        the contact form or the information provided.</p>
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-2 me-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Email</h6>
                                <p class="mb-0">{{ $contactInfo['support_email'] }}</p>

                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-2 me-3">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Phone</h6>
                                <p class="mb-0">{{ $contactInfo['support_phone'] }}</p>

                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle p-2 me-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Address</h6>
                                <p class="mb-0">{{ $contactInfo['address'] }}</p>


                            </div>
                        </div>
                    </div>

                    <div class="d-flex">
                        <a href="{{ data_get($contactInfo, 'social_facebook') }}" target="_blank" class="social-icon me-2">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="{{ data_get($contactInfo, 'social_twitter') }}" target="_blank" class="social-icon me-2">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="{{ data_get($contactInfo, 'social_instagram') }}" target="_blank"
                            class="social-icon me-2">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="{{ data_get($contactInfo, 'social_linkedin') }}" target="_blank" class="social-icon">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            @include('partials.contact-form')
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section> --}}
@endsection

@section('scripts')
    <script>
        //here
    </script>
@endsection
