<div class="col-sm-12 col-md-6 col-lg-4 mb-4 d-flex justify-content-center">
    <a href="{{ route('blog', $post->slug) }}" class="card-link-wrapper text-decoration-none text-reset w-100">
        <div class="card shadow-sm" style="width: 100%;">
            <div class="card-img-wrapper">
                <picture>
                    {{-- Optional WebP source --}}
                    {{-- <source srcset="{{ asset('storage/' . $post->image_webp) }}" type="image/webp"> --}}
                    <img src="{{ asset('storage/' . $post->image_path) }}" class="card-img-top fixed-img"
                        alt="{{ $post->title }}" loading="lazy" />
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
