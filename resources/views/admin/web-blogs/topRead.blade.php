<h5 class="mb-4">📈 Most Read</h5>

{{-- @php
        $popularPosts = [
            [
                'title' => 'Design in the Age of AI',
                'category' => 'UI/UX',
                'views' => '8.4K',
                'img' => 'popular1',
            ],
            [
                'title' => 'Travel Hacks 2025',
                'category' => 'Travel',
                'views' => '6.1K',
                'img' => 'popular2',
            ],
            [
                'title' => 'Mastering Remote Work',
                'category' => 'Work',
                'views' => '5.7K',
                'img' => 'popular3',
            ],
            [
                'title' => 'Mindful Productivity',
                'category' => 'Wellness',
                'views' => '4.8K',
                'img' => 'popular4',
            ],
        ];
    @endphp --}}

@foreach ($topBlogs as $popular)
    <!-- popular-post.blade.php -->

    <style>
        /* Custom styles for popular post */
        .popular-post img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            flex-shrink: 0;
            /* prevent image shrinking */
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .popular-post {
                flex-direction: column !important;
                /* stack image and text vertically */
                align-items: flex-start !important;
            }

            .popular-post img {
                width: 100% !important;
                /* full width image on small screens */
                height: auto !important;
                /* auto height to keep aspect ratio */
                margin-bottom: 8px;
            }
        }

        a {
            text-decoration: none;
        }
    </style>
    <a href="{{ route('blog', $popular->slug) }}">
        <div class="d-flex bg-white mb-3 shadow-sm rounded overflow-hidden popular-post align-items-center">
            <img src="{{ asset('storage/' . $popular->image_path) }}" alt="{{ $popular->title }}" class="flex-shrink-0">
            <div class="p-2 flex-grow-1">
                <h6 class="mb-1">{{ $popular->title }}</h6>
                {{-- <small class="text-muted">{{ $popular['category'] }} •
                    {{ number_format_short((int) $popular['views']) }} views</small> --}}
                <small class="text-muted">
                    {{ number_format_short((int) $popular->view_count) }} views</small>
            </div>
        </div>
    </a>
@endforeach
