<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'InstaAppoint')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Common styles -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
        }

        .navbar-brand img {
            height: 60px;
        }

        footer {
            background-color: var(--secondary-color);
            color: white;
            margin-top: auto;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            transition: background-color 0.3s ease;
        }

        .social-icon:hover {
            background-color: var(--primary-color);
            color: white;
        }
    </style>

    <!-- Page specific styles -->
    @yield('styles')
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset('images/dark_logo.png') }}" alt="InstaAppoint Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#features') }}">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#download') }}">Download</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#about') }}">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#contact') }}">Contact</a>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-primary" href="{{ url('/#download') }}">Get App</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main style="margin-top: 85px;">
    @yield('content')
</main>

<!-- Footer -->
<footer class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <img src="{{ asset('images/light_logo.png') }}" alt="InstaAppoint Logo" class="img-fluid mb-4" style="max-width: 200px;">
                <p class="text-white-50">InstaAppoint is a modern solution for appointment booking and management. We simplify scheduling for businesses and individuals.</p>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-4">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="{{ url('/') }}" class="text-white-50 text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="{{ url('/#features') }}" class="text-white-50 text-decoration-none">Features</a></li>
                    <li class="mb-2"><a href="{{ url('/#download') }}" class="text-white-50 text-decoration-none">Download</a></li>
                    <li class="mb-2"><a href="{{ url('/#about') }}" class="text-white-50 text-decoration-none">About</a></li>
                    <li><a href="{{ url('/#contact') }}" class="text-white-50 text-decoration-none">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-4">Legal</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="{{ route('page.show','terms-of-service') }}" class="text-white-50 text-decoration-none">Terms of Service</a></li>
                    <li class="mb-2"><a href="{{ route('page.show','privacy-policy') }}" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                    <li class="mb-2"><a href="{{ route('page.show','refund-policy') }}" class="text-white-50 text-decoration-none">Refund Policy</a></li>
                    <li class="mb-2"><a href="{{ route('page.show','deactivate-user-account') }}" class="text-white-50 text-decoration-none">Deactivate Account Policy</a></li>
                    <li><a href="{{ route('page.show','cookie-policy') }}" class="text-white-50 text-decoration-none">Cookie Policy</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-4">Newsletter</h5>
                @include('partials.newsletter-compact-form', ['source' => 'footer'])
            </div>
        </div>
        <hr class="my-4 bg-white-50">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-white-50 mb-0">&copy; {{ date('Y') }} InstaAppoint. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-flex text-md-end float-end">
                    <a href="#" class="social-icon me-2">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-icon me-2">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-icon me-2">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-icon">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- Common JavaScript -->
<script>
    // Change navbar background on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('shadow');
        } else {
            navbar.classList.remove('shadow');
        }
    });
</script>

<!-- Page specific scripts -->
@yield('scripts')
</body>
</html>
