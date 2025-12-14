@extends('layouts.app')

@section('title', 'InstaAppoint - Quick Appointment Booking')

@section('styles')
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .hero-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 0 0;
            position: relative;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 67px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100"><path fill="%23ffffff" d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,100L1360,100C1280,100,1120,100,960,100C800,100,640,100,480,100C320,100,160,100,80,100L0,100Z"></path></svg>');
            background-size: cover;
            background-repeat: no-repeat;
        }

        .logo {
            max-width: 350px;
            height: auto;
        }

        .logo-icon {
            max-width: 80px;
            height: auto;
        }

        .download-badge {
            width: 160px;
            transition: transform 0.3s ease;
        }

        .download-badge:hover {
            transform: scale(1.05);
        }

        .feature-icon {
            background-color: var(--primary-color);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .qr-code {
            max-width: 150px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 10px;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .app-section {
            background-color: #f8f9fa;
            border-radius: 20px;
            overflow: hidden;
        }

        .phone-mockup {
            max-width: 300px;
            position: relative;
            z-index: 2;
        }

        .fas {
            padding: 0 4px
        }

        .wave-bg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 0;
        }

        footer {
            background-color: var(--secondary-color);
            color: white;
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

        .nav-link {
            color: var(--secondary-color);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .newsletter-section {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
            border-radius: 15px;
            padding: 40px;
            margin-top: 30px;
        }

        .newsletter-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
    </style>
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0"><br><br>
                    <h1 class="display-4 fw-bold mb-3">Quick & Easy <span class="text-primary">Appointment Booking</span></h1>
                    <p class="lead mb-4">InstaAppoint helps you schedule appointments instantly without the hassle. Book,
                        manage, and receive reminders all in one place.</p>
                    <div class="d-flex flex-wrap">
                        <a href="#download" class="btn btn-primary me-3 mb-3">Download Now</a>
                        <a href="#features" class="btn btn-outline-primary mb-3">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="{{ asset('images/dark_logo.png') }}" alt="InstaAppoint Logo" class="img-fluid logo"
                        width="400">
                </div>
            </div>
        </div>
    </section>


    <!-- image carousal-->
    @include('imageCarousal')


    <!-- card slide-->
    <section class="py-2 my-2" id="card-slide">
        <div class="text-center m-3">
            <h2 class="fw-bold">Categories</h2>
            {{-- <p class="lead text-muted">Streamlined features designed to make appointment booking effortless</p> --}}
        </div>
        <div class="container">
            @include('cardSlide')
        </div>
    </section>



    <!-- Features Section -->
    <section class="py-2 my-2" id="features">
        <div class="container">
            <div class="text-center">
                <h2 class="fw-bold">Why Choose InstaAppoint?</h2>
                <p class="lead text-muted">Streamlined features designed to make appointment booking effortless</p>
            </div>
            <div class="row g-2 m-4">
                <div class="col-md-4">
                    <div class="text-center p-2">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h5 class="fw-bold">Instant Booking</h5>
                        <p class="text-muted">Book appointments with just a few taps. No more waiting on phone calls or
                            emails.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-2">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h5 class="fw-bold">Smart Reminders</h5>
                        <p class="text-muted">Never miss an appointment with our intelligent notification system.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-2">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h5 class="fw-bold">Easy Rescheduling</h5>
                        <p class="text-muted">Need to change your appointment? Reschedule with just a single click.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-2">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <h5 class="fw-bold">Real-time Availability</h5>
                        <p class="text-muted">See real-time availability of service providers and book accordingly.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-2">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5 class="fw-bold">Ratings & Reviews</h5>
                        <p class="text-muted">Make informed decisions by checking ratings and reviews from other users.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-2">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-history"></i>
                        </div>
                        <h5 class="fw-bold">Appointment History</h5>
                        <p class="text-muted">Keep track of all your past and upcoming appointments in one place.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- App Showcase Section -->
    <section class="py-5 bg-light" id="about"> <!-- Increased padding slightly for breathing room -->
        <div class="container">
            <div class="row align-items-center">
                <!-- Image Column -->
                <div class="col-lg-6 order-lg-2 mb-4 mb-lg-0">
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <img src="{{ asset('images/vendor_image.jpg') }}" alt="Vendor App" class="img-fluid shadow"
                            style="border-radius:10px; max-height:400px; width:auto;">

                        <img src="{{ asset('images/customer_image.jpg') }}" alt="Customer App" class="img-fluid shadow"
                            style="border-radius:10px; max-height:400px; width:auto;">
                    </div>
                </div>

                <!-- Text Column -->
                <div class="col-lg-6 order-lg-1">
                    <h2 class="fw-bold mb-3">Designed for Effortless Experience</h2>
                    <p class="mb-3">
                        InstaAppoint is crafted with user experience in mind, making appointment booking a breeze for
                        everyone.
                        Our intuitive interface ensures that you can navigate the app easily and book appointments without
                        any technical knowledge.
                    </p>

                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded-circle p-2 me-3 mt-1">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">User-friendly Interface</h5>
                                <p class="text-muted mb-0">Clean and intuitive design that anyone can use without confusion.
                                </p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded-circle p-2 me-3 mt-1">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Personalized Experience</h5>
                                <p class="text-muted mb-0">The app learns your preferences and suggests appointments
                                    accordingly.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded-circle p-2 me-3 mt-1">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Multi-platform Support</h5>
                                <p class="text-muted mb-0">Available on both iOS and Android devices to serve all users.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Newsletter Section -->
    <!-- <section class="py-5" id="newsletter">
                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="container">
                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="newsletter-section">
                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="row align-items-center">
                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="col-lg-6 mb-4 mb-lg-0">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                <h2 class="fw-bold mb-3">Stay Updated with InstaAppoint</h2>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                <p class="mb-4">Subscribe to our newsletter to receive the latest updates, tips, and exclusive offers directly to your inbox.</p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                <ul class="list-unstyled mb-0">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <li class="mb-3 d-flex align-items-center">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <i class="fas fa-check"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <span>App feature announcements and updates</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <li class="mb-3 d-flex align-items-center">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <i class="fas fa-check"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <span>Tips to optimize your appointment scheduling</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <li class="d-flex align-items-center">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <i class="fas fa-check"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <span>Exclusive offers and promotions</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                </ul>
                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="col-lg-6">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="newsletter-card">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="card-body p-4">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        @include(
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            'partials.newsletter-form',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            [
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                'source' =>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    'homepage',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ]
                                                                                                                                                                                                                                                                                                                                                                                                                                                                        )
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                            </section> -->



    <!-- Testimonials Section -->
    <section class="py-2">
        <div class="container py-2">
            <div class="text-center mb-5">
                <h2 class="fw-bold">What Our Users Say</h2>
                <p class="lead text-muted">Thousands of satisfied users love InstaAppoint</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="mb-3 text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="mb-0">"InstaAppoint has completely transformed how I manage my appointments. The
                                interface is intuitive, and booking is incredibly fast. Highly recommended!"</p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-4 px-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <span>PB</span>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold">Pankaj Bansal</h6>
                                    <small class="text-muted">Business Owner</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="mb-3 text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="mb-0">"The reminder feature is a lifesaver! I never miss appointments anymore, and
                                rescheduling is so easy. Great app that truly delivers on its promise."</p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-4 px-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <span>RS</span>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold">Ravi Shankar</h6>
                                    <small class="text-muted">Healthcare Professional</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="mb-3 text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <p class="mb-0">"As someone who manages multiple appointments weekly, InstaAppoint has been a
                                game-changer. The ability to see real-time availability is incredibly useful."</p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-4 px-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <span>IA</span>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold">Imran Ahmend</h6>
                                    <small class="text-muted">Freelance Consultant</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('pricing')

    <!-- Download Section -->
    {{-- <section class="py-2 my-2" id="download">
        <div class="container py-2">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Download InstaAppoint Today</h2>
                <p class="lead text-muted">Available for iOS and Android devices</p>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-md-6 col-lg-6 text-center mb-4 mb-md-0">
                    <div class="d-flex flex-column align-items-center">
                        <img src="{{ asset('images/iso.jpeg') }}" alt="App Store QR Code" class="qr-code mb-3">
                        <h5 class="fw-bold">iOS App Store</h5>
                        <p class="text-muted mb-3">Scan to download for iPhone & iPad</p>
                        <a href="https://apps.apple.com/in/app/instaappoint/id6747049926" class="d-inline-block"
                            target="_blank">
                            <img src="{{ asset('images/Download_on_the_App_Store_Badge.svg') }}"
                                alt="Download on App Store" class="download-badge">
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-6 text-center">
                    <div class="d-flex flex-column align-items-center">
                        <img src="{{ asset('images/android.jpeg') }}" alt="Google Play QR Code" class="qr-code mb-3">
                        <h5 class="fw-bold">Google Play Store</h5>
                        <p class="text-muted mb-3">Scan to download for Android devices</p>
                        <a href="https://play.google.com/store/apps/details?id=in.instaappoint.app&pcampaignid=web_share"
                            target="_blank" class="d-inline-block">
                            <img src="{{ asset('images/Google_Play_Store_badge_EN.svg') }}" alt="Get it on Google Play"
                                class="download-badge">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}

    <!-- Contact Section -->
    <section class="py-2" id="contact">
        <div class="container py-2">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h2 class="fw-bold mb-4">Get In Touch</h2>
                    <p class="mb-4">Have questions or feedback? We'd love to hear from you. Reach out to our team using
                        the contact form or the information provided.</p>
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-2 me-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Email</h6>
                                <p class="mb-0">{{ $contactInfo['support_email'] }}</p>

                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-2 me-3">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Phone</h6>
                                <p class="mb-0">{{ $contactInfo['support_phone'] }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle p-2 me-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Address</h6>
                                <p class="mb-0">{{ $contactInfo['address'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex">
                        <a href="{{ data_get($contactInfo, 'social_facebook') }}" target="_blank"
                            class="social-icon me-2">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="{{ data_get($contactInfo, 'social_twitter') }}" target="_blank"
                            class="social-icon me-2">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="{{ data_get($contactInfo, 'social_instagram') }}" target="_blank"
                            class="social-icon me-2">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="{{ data_get($contactInfo, 'social_linkedin') }}" target="_blank" class="social-icon">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            @include('partials.contact-form')
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const navHeight = document.querySelector('.navbar').offsetHeight;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset -
                        navHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
@endsection
