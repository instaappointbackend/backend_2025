@extends('layouts.app')

@section('title', 'InstaAppoint - Quick Appointment Booking')

@section('content')
    <style>
        /* Generic responsive styles for blog content */
        .article-body {
            font-family: 'Inter', sans-serif;
            color: #333;
            line-height: 1.7;
        }

        .article-body p {
            font-size: 1rem;
            margin-bottom: 1.25rem;
            color: #444;
            word-break: break-word;
            overflow-wrap: break-word;
            text-align: left;
            /* Default: justify on large screens */
            text-justify: inter-word;
        }

        .article-body h1,
        .article-body h2,
        .article-body h3,
        .article-body h4,
        .article-body h5,
        .article-body h6 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            line-height: 1.4;
            color: #222;
        }

        .article-body h1 {
            font-size: 2rem;
        }

        .article-body h2 {
            font-size: 1.75rem;
        }

        .article-body h3 {
            font-size: 1.5rem;
        }

        .article-body h4 {
            font-size: 1.25rem;
        }

        .article-body h5 {
            font-size: 1.1rem;
        }

        .article-body h6 {
            font-size: 1rem;
        }

        .article-body ul,
        .article-body ol {
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .article-body ul li,
        .article-body ol li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        .article-body img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1rem 0;
            display: block;
        }

        .article-body blockquote {
            border-left: 4px solid #764ba2;
            padding-left: 1rem;
            margin: 1.5rem 0;
            font-style: italic;
            background: #f8f8fc;
        }

        .article-body table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .article-body table,
        .article-body th,
        .article-body td {
            border: 1px solid #ddd;
        }

        .article-body th,
        .article-body td {
            padding: 0.75rem;
            text-align: left;
        }

        .article-body a {
            color: #667eea;
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .article-body h1 {
                font-size: 1.6rem;
            }

            .article-body h2 {
                font-size: 1.4rem;
            }

            .article-body h3 {
                font-size: 1.25rem;
            }

            .article-body h4 {
                font-size: 1.1rem;
            }

            .article-body h5 {
                font-size: 1rem;
            }

            .article-body h6 {
                font-size: 0.95rem;
            }

            .article-body p {
                font-size: 0.95rem;
                text-align: left;
                /* Override justify on mobile */
            }

            .article-body table {
                font-size: 0.9rem;
                overflow-x: auto;
                display: block;
            }
        }

        @media (max-width: 480px) {
            .article-body p {
                font-size: 0.9rem;
                line-height: 1.6;
            }

            .article-body h1 {
                font-size: 1.4rem;
            }

            .article-body h2 {
                font-size: 1.25rem;
            }

            .article-body h3 {
                font-size: 1.1rem;
            }
        }
    </style>

    <div class="container my-5">
        <div class="row">
            <!-- Blog Content -->
            <div class="col-lg-8 mb-4">
                <div class="card p-4 shadow-sm rounded">
                    <h1 class="mb-2">{{ $post->title }}</h1>
                    <h5 class="text-muted mb-3">{{ $post->sub_title }}</h5>
                    <small class="text-muted d-block mb-4">
                        Created by <strong>{{ $post->user->name }}</strong> on {{ $post->created_at->format('F j, Y') }}
                    </small>

                    @if ($post->image_path)
                        <img src="{{ asset('storage/' . $post->image_path) }}" alt="Main Blog Image"
                            class="img-fluid rounded mb-4" loading="lazy">
                    @endif

                    <div class="article-body">
                        @php
                            $hasHtml = $post->description !== strip_tags($post->description);
                        @endphp
                        @if ($hasHtml)
                            {!! $post->description !!}
                        @else
                            <p>{{ $post->description }}</p>
                        @endif
                    </div>

                    @if ($post->images->isNotEmpty())
                        @foreach ($post->images as $image)
                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $post->title }}"
                                class="img-fluid rounded my-4" loading="lazy">

                            <div class="article-body mb-4">
                                @php
                                    $hasHtml = $image->image_description !== strip_tags($image->image_description);
                                @endphp

                                @if ($hasHtml)
                                    {!! $image->image_description !!}
                                @else
                                    <p>{{ $image->image_description }}</p>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    <a href="{{ route('blogs') }}" class="btn btn-primary mt-3">← Back to Blog</a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm p-4 rounded">
                    @include('admin.web-blogs.topRead')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                fetch('{{ route('blog.read', $post->id) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        },
                    }).then(response => response.json())
                    .then(data => {
                        console.log('Read count incremented:', data.read_count);
                    });
            }, 15000);
        });
    </script>
@endsection
