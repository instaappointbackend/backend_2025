<div class="newsletter-form bg-light p-4 rounded">
    <h4 class="text-center mb-3">Subscribe to Our Newsletter</h4>
    
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    
    <form action="{{ route('newsletter.subscribe') }}" method="POST">
        @csrf
        <input type="hidden" name="source" value="{{ $source ?? 'website' }}">
        
        <div class="mb-3">
            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email address">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="mb-3">
            <label for="name" class="form-label">Full Name (Optional)</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter your name">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="agree" required>
            <label class="form-check-label" for="agree">
                I agree to receive newsletters and marketing emails. You can unsubscribe at any time.
            </label>
        </div>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Subscribe</button>
        </div>
        
        <div class="text-center mt-2">
            <small class="text-muted">We respect your privacy and will never share your information.</small>
        </div>
    </form>
</div>