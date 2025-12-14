<!-- resources/views/admin/auth/login.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Admin Login')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Admin Login</h2>
                {{-- <p class="mb-0">Enter your email or mobile to receive an OTP</p> --}}
            </div>
            <div class="auth-body">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.password.login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile"
                            name="mobile" value="{{ old('mobile') }}" placeholder="Enter your  mobile number" required
                            autofocus>
                        @error('mobile')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password" value="{{ old('password') }}" placeholder="Enter password" required autofocus>
                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Send OTP
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">Back to website? <a href="{{ url('/') }}">Go Home</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
