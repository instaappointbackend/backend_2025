<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Vendor Registration Successful</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="col-md-6">
            <div class="card shadow-sm text-center">
                <div class="card-body p-5">

                    <!-- Logo -->
                    <div class="mb-4">
                        <img src="{{ asset('images/dark_logo.png') }}" alt="Company Logo" class="img-fluid"
                            style="max-height: 80px;">
                    </div>

                    <h2 class="text-success mb-3">Registration Successful!</h2>

                    <p class="text-muted">
                        Thank you for registering as a vendor. Your registration has been submitted successfully.
                    </p>
                    <p class="text-muted">
                        Our team will review your details and notify you via email once your account is approved.
                    </p>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <a href="/" class="btn btn-success px-4">Go to Homepage</a>
                        {{-- <a href="/contact" class="btn btn-outline-secondary px-4">Contact Support</a> --}}
                    </div>

                </div>
            </div>
        </div>
    </div>

</body>

</html>
