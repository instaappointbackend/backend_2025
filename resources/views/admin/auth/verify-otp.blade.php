<!-- resources/views/admin/auth/verify-otp.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Verify OTP')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Verify OTP</h2>
            <p class="mb-0">Enter the OTP sent to your account</p>
        </div>
        <div class="auth-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('admin.verify.otp') }}">
                @csrf
                
                <div class="mb-4">
                    <div class="text-center mb-3">
                        @php
                            $loginField = session('admin_login_field');
                            $loginType = session('admin_login_type');
                            $maskedValue = $loginField;
                            
                            if ($loginType === 'email') {
                                $parts = explode('@', $loginField);
                                if (count($parts) === 2) {
                                    $name = $parts[0];
                                    $domain = $parts[1];
                                    $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 4, 0)) . substr($name, -2);
                                    $maskedValue = $maskedName . '@' . $domain;
                                }
                            } else {
                                $maskedValue = substr($loginField, 0, 2) . str_repeat('*', strlen($loginField) - 4) . substr($loginField, -2);
                            }
                        @endphp
                        
                        <span class="badge bg-primary">{{ $maskedValue }}</span>
                    </div>
                    
                    <div class="otp-input-container">
                        <label for="otp" class="form-label">One-Time Password</label>
                        <input type="text" class="form-control @error('otp') is-invalid @enderror" id="otp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" required autofocus>
                        @error('otp')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle me-1"></i> Verify OTP
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <p class="mb-0">Didn't receive OTP? <a href="{{ route('admin.login') }}">Try Again</a></p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto focus on the OTP input
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('otp').focus();
    });
    
    // Highlight the OTP input when focused
    document.getElementById('otp').addEventListener('focus', function() {
        this.select();
    });
</script>
@endsection