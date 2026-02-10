@extends('admin.layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">
                            <i class="bi bi-shield-lock me-2"></i>Two-Factor Authentication
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">
                            Please confirm access to your account by entering the authentication code provided by your
                            authenticator application.
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- Authentication Code Form -->
                        <form method="POST" action="{{ route('admin.two-factor-verify') }}" id="codeForm">
                            @csrf

                            <div class="mb-3">
                                <label for="code" class="form-label fw-semibold">
                                    <i class="bi bi-phone me-1"></i>Authentication Code
                                </label>
                                <input id="code" type="text" name="code"
                                    class="form-control form-control-lg text-center" placeholder="000000" maxlength="6"
                                    pattern="[0-9]{6}" autofocus autocomplete="off">
                                <small class="form-text text-muted">Enter the 6-digit code from your authenticator
                                    app</small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Verify Code
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.two-factor.disabled') }}" id="codeForm2">
                            <div class="d-grid mt-2">

                                @csrf
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Regenerate 2FA
                                </button>

                            </div>
                        </form>

                        {{-- <div class="text-center my-3">
                            <span class="text-muted">───── OR ─────</span>
                        </div> --}}

                        <!-- Recovery Code Form -->
                        <div class="collapse" id="recoveryCodeCollapse">
                            <form method="POST" action="{{ route('admin.two-factor-verify') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="recovery_code" class="form-label fw-semibold">
                                        <i class="bi bi-key me-1"></i>Recovery Code
                                    </label>
                                    <input id="recovery_code" type="text" name="recovery_code"
                                        class="form-control form-control-lg text-center" placeholder="XXXXXXXXXX"
                                        autocomplete="off">
                                    <small class="form-text text-muted">Enter one of your recovery codes</small>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-secondary btn-lg">
                                        <i class="bi bi-unlock me-2"></i>Use Recovery Code
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse"
                                        data-bs-target="#recoveryCodeCollapse">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- <div class="text-center mt-3" id="recoveryToggle">
                            <button class="btn btn-link text-decoration-none" data-bs-toggle="collapse"
                                data-bs-target="#recoveryCodeCollapse">
                                <i class="bi bi-question-circle me-1"></i>Lost your device? Use a recovery code
                            </button>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hide "Use recovery code" link when recovery form is shown
        document.getElementById('recoveryCodeCollapse').addEventListener('show.bs.collapse', function() {
            document.getElementById('recoveryToggle').style.display = 'none';
            document.getElementById('codeForm').style.display = 'none';
        });

        document.getElementById('recoveryCodeCollapse').addEventListener('hide.bs.collapse', function() {
            document.getElementById('recoveryToggle').style.display = 'block';
            document.getElementById('codeForm').style.display = 'block';
        });
    </script>
@endsection
