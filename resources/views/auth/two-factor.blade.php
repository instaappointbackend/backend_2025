@extends('admin.layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Two-Factor Authentication</h4>
                    </div>
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if (!$user->two_factor_secret)
                            <!-- Enable 2FA -->
                            <div class="mb-4">
                                <p class="mb-3">Two-factor authentication adds an additional layer of security to your
                                    account by requiring a code from your phone in addition to your password.</p>
                                <form method="POST" action="{{ route('admin.two-factor.enable') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-shield-check me-2"></i>Enable Two-Factor Authentication
                                    </button>
                                </form>
                            </div>
                        @elseif (!$user->two_factor_confirmed_at)
                            <!-- Show QR Code and Confirm -->
                            <div class="mb-4">
                                <h5 class="mb-3">Setup Two-Factor Authentication</h5>

                                <div class="alert alert-info">
                                    <strong>Step 1:</strong> Scan this QR code with your authenticator app (Google
                                    Authenticator, Authy, etc.)
                                </div>

                                <div class="text-center mb-4 p-3 bg-light rounded">
                                    {!! $qrCode !!}
                                </div>

                                {{-- <div class="mb-4">
                                    <h5 class="mb-3">Recovery Codes</h5>
                                    <div class="alert alert-warning">
                                        <strong>Important:</strong> Store these codes in a safe place. They can be used to
                                        recover access if you lose your device.
                                    </div>
                                    <div class="p-3 bg-light rounded border">
                                        <div class="row">
                                            @foreach ($recoveryCodes as $code)
                                                <div class="col-md-6 mb-2">
                                                    <code class="d-block">{{ $code }}</code>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="alert alert-info">
                                    <strong>Step 2:</strong> Enter the 6-digit code from your authenticator app to confirm
                                    setup
                                </div>

                                <form method="POST" action="{{ route('admin.two-factor.confirm') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="code" class="form-label">Authentication Code</label>
                                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                                            id="code" name="code" placeholder="000000" maxlength="6" required
                                            autofocus>
                                        @error('code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i>Confirm Two-Factor Authentication
                                    </button>
                                </form>
                            </div>
                        @else
                            <!-- 2FA is Active -->
                            <div class="mb-4">
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <i class="bi bi-shield-check fs-4 me-2"></i>
                                    <div>
                                        <strong>Two-factor authentication is active</strong>
                                        <p class="mb-0 small">Your account is protected with two-factor authentication.</p>
                                    </div>

                                </div>
                                <a href="{{ route('admin.password.login') }}">Go To login</a>

                                <!-- Show Recovery Codes -->
                                @if (session('recoveryCodes'))
                                    <div class="mb-4">
                                        <h5 class="mb-3">New Recovery Codes</h5>
                                        <div class="alert alert-warning">
                                            Save these new recovery codes in a secure location. The old codes will no longer
                                            work.
                                        </div>
                                        <div class="p-3 bg-light rounded border">
                                            <div class="row">
                                                @foreach (session('recoveryCodes') as $code)
                                                    <div class="col-md-6 mb-2">
                                                        <code class="d-block">{{ $code }}</code>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- <hr class="my-4"> --}}

                                <!-- Regenerate Recovery Codes -->
                                {{-- <div class="mb-4">
                                    <h5 class="mb-3">Regenerate Recovery Codes</h5>
                                    <p class="text-muted small">Generate new recovery codes. This will invalidate your
                                        existing codes.</p>
                                    <form method="POST" action="{{ route('admin.two-factor.regenerate') }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="password_regenerate" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="password_regenerate"
                                                name="password" style="max-width: 300px;" required>
                                        </div>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-arrow-clockwise me-2"></i>Regenerate Recovery Codes
                                        </button>
                                    </form>
                                </div> --}}

                                {{-- <hr class="my-4"> --}}

                                <!-- Disable 2FA -->
                                {{-- <div class="mb-4">
                                    <h5 class="mb-3 text-danger">Disable Two-Factor Authentication</h5>
                                    <p class="text-muted small">This will remove the extra layer of security from your
                                        account.</p>
                                    <form method="POST" action="{{ route('admin.two-factor.disable') }}">
                                        @csrf
                                        @method('DELETE')
                                        <div class="mb-3">
                                            <label for="password_disable" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="password_disable"
                                                name="password" style="max-width: 300px;" required>
                                        </div>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-shield-x me-2"></i>Disable Two-Factor Authentication
                                        </button>
                                    </form>
                                </div> --}}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
