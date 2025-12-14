<div class="newsletter-compact-form">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @elseif(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @elseif(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @else
     
        <p class="text-white-50 mb-3">Subscribe to our newsletter for updates and promotions.</p>
        
        <form action="{{ route('newsletter.subscribe') }}" method="POST" class="d-flex">
            @csrf
            <input type="hidden" name="source" value="{{ $source ?? 'footer' }}">
            
            <div class="input-group">
                <input type="email" class="form-control" name="email" placeholder="Your email address" required>
                <button class="btn btn-primary" type="submit">Subscribe</button>
            </div>
        </form>
        
        <div class="form-text mt-2">
            We respect your privacy. Unsubscribe anytime.
        </div>
    @endif
</div>