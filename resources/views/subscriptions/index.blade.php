<!DOCTYPE html>
<html lang="en">
@php

    use App\Enums\PlanEnum;
    $plans = PlanEnum::getAllPlans();
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InstAppoint Subscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- <style>
        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-split {
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Left side */
        .left-side {
            flex: 1;
            background-color: #243b8a;
            /* Updated primary color */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 30px;
            text-align: center;
            min-width: 250px;
        }

        .left-side img {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .left-side h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .left-side p {
            font-size: 1.2rem;
            margin-top: 10px;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Right side */
        .right-side {
            flex: 1;
            background: #f8f9fa;
            padding: 20px 40px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .subscription-form {
            background: #fff;
            border-radius: 0.375rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 25px;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        h2 {
            color: #243b8a;
            /* Updated primary color */
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
        }

        p.subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            color: #212529;
        }

        input.form-control {
            border-radius: 0.375rem;
            border: 1.5px solid #dee2e6;
            padding: 10px 12px;
            transition: border-color 0.3s;
        }

        input.form-control:focus {
            border-color: #243b8a;
            box-shadow: 0 0 8px rgba(36, 59, 138, 0.3);
            outline: none;
        }

        .plan-toggle {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0 25px;
        }

        .plan-toggle input[type="radio"] {
            display: none;
        }

        .plan-toggle label {
            flex: 1;
            text-align: center;
            padding: 10px 0;
            font-weight: 700;
            font-size: 1rem;
            border: 2px solid #243b8a;
            border-radius: 0.375rem;
            cursor: pointer;
            user-select: none;
            color: #243b8a;
            transition: all 0.3s ease;
        }

        .plan-toggle input[type="radio"]:checked+label {
            background-color: #243b8a;
            color: white;
            box-shadow: 0 4px 15px rgba(36, 59, 138, 0.6);
        }

        button.btn-pay {
            background-color: #243b8a;
            color: white;
            font-weight: 700;
            padding: 12px;
            border-radius: 0.375rem;
            border: none;
            width: 100%;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        button.btn-pay:hover {
            background-color: #1e326f;
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .footer-text a {
            color: #243b8a;
            text-decoration: underline;
        }

        .alert ul {
            margin-bottom: 0;
        }

        .invalid-feedback {
            display: block;
        }

        @media(max-width: 992px) {
            .container-split {
                flex-direction: column;
            }

            .left-side,
            .right-side {
                flex: unset;
                min-height: 200px;
            }

            .right-side {
                overflow-y: visible;
            }
        }

        :root {
            --sticky-btn-height: 70px;
        }

        @media(max-width: 576px) {
            .right-side {
                padding-bottom: calc(var(--sticky-btn-height) + 20px);
                overflow-y: auto;
            }

            .btn-pay {
                height: var(--sticky-btn-height);
                position: sticky;
                bottom: 0;
            }

            .left-side h1 {
                font-size: 2rem;
            }

            .left-side p {
                font-size: 1rem;
            }

            .plan-toggle {
                flex-direction: column;
                gap: 10px;
            }
        }

        .plan-amount {
            display: block;
            margin-top: 4px;
            font-size: 0.9rem;
        }

        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            /* muted gray */
            margin-right: 5px;
        }

        .discounted-price {
            font-weight: 700;
            color: #7690eb;
            /* your consistent brand color */
        }
    </style> --}}
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-split {
            display: flex;
            min-height: 100vh;
            /* overflow: hidden; */
        }

        /* Left side */
        .left-side {
            flex: 1;
            background-color: #243b8a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 30px;
            text-align: center;
            min-width: 250px;
        }

        .left-side img {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .left-side h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .left-side p {
            font-size: 1.2rem;
            margin-top: 10px;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Right side */
        .right-side {
            flex: 1;
            background: #f8f9fa;
            padding: 20px 40px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .subscription-form {
            background: #fff;
            border-radius: 0.375rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 25px;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        h2 {
            color: #243b8a;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
        }

        p.subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            color: #212529;
        }

        input.form-control {
            border-radius: 0.375rem;
            border: 1.5px solid #dee2e6;
            padding: 10px 12px;
            transition: border-color 0.3s;
        }

        input.form-control:focus {
            border-color: #243b8a;
            box-shadow: 0 0 8px rgba(36, 59, 138, 0.3);
            outline: none;
        }

        .plan-toggle {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0 25px;
        }

        .plan-toggle input[type="radio"] {
            display: none;
        }

        .plan-toggle label {
            flex: 1;
            text-align: center;
            padding: 10px 0;
            font-weight: 700;
            font-size: 1rem;
            border: 2px solid #243b8a;
            border-radius: 0.375rem;
            cursor: pointer;
            user-select: none;
            color: #243b8a;
            transition: all 0.3s ease;
        }

        .plan-toggle input[type="radio"]:checked+label {
            background-color: #243b8a;
            color: white;
            box-shadow: 0 4px 15px rgba(36, 59, 138, 0.6);
        }

        button.btn-pay {
            background-color: #243b8a;
            color: white;
            font-weight: 700;
            padding: 12px;
            border-radius: 0.375rem;
            border: none;
            width: 100%;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        button.btn-pay:hover {
            background-color: #1e326f;
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .footer-text a {
            color: #243b8a;
            text-decoration: underline;
        }

        .alert ul {
            margin-bottom: 0;
        }

        .invalid-feedback {
            display: block;
        }

        /* Tablets */
        @media(max-width: 992px) {
            .container-split {
                flex-direction: column;
            }

            .left-side,
            .right-side {
                flex: unset;
                min-height: 200px;
            }

            .right-side {
                overflow-y: visible;
            }
        }

        /* Phones */
        @media(max-width: 576px) {

            /* Force full height so right-side can scroll */
            .container-split {
                flex-direction: column;
                min-height: auto;
                /* allow full page scroll */
                height: auto;
                /* ensure natural height */
                overflow: visible;
                /* IMPORTANT: allow full scrolling */
            }

            .left-side {
                flex: 0 0 auto;
                padding: 20px;
            }

            .right-side {
                padding-bottom: 120px !important;
                /* space for sticky button */
                overflow: visible;
                /* no inner scrolling */
                height: auto;
                /* space for sticky button */
            }

            .left-side h1 {
                font-size: 2rem;
            }

            .left-side p {
                font-size: 1rem;
            }

            .plan-toggle {
                flex-direction: column;
                gap: 10px;
            }

            .btn-pay {
                position: sticky;
                bottom: 0;
                width: 100%;
                z-index: 999;
                border-radius: 0;
                padding: 15px;
            }
        }

        .plan-amount {
            display: block;
            margin-top: 4px;
            font-size: 0.9rem;
        }

        .original-price {
            text-decoration: line-through;
            color: #6c757d;
        }

        .discounted-price {
            font-weight: 700;
            color: #7690eb;
        }
    </style>
</head>

<body>

    <div class="container-split">

        <!-- Left Side -->
        <div class="left-side">
            <img src="{{ asset('images/dark_logo.png') }}" alt="InstAppoint Logo">
            <h1>InstAppoint</h1>
            <p>Secure subscription with PhonePe</p>
        </div>

        <!-- Right Side -->
        <div class="right-side">
            <div class="subscription-form">
                <h2>Subscribe Now</h2>
                <p class="subtitle">Fill your details & start using InstAppoint</p>

                {{-- Session Messages --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- @dd(session('error')) --}}
                @if (Session::has('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- @if ($error->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif --}}

                {{-- Validation Errors --}}


                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <strong>
                            {!! implode('<br/>', $errors->all('<span>:message</span>')) !!}
                        </strong>
                    </div>
                @endif


                <form action="{{ route('subscribe.process') }}" method="POST" id="subscriptionForm">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text"
                            class="form-control {{ isset($errors) && $errors->has('name') ? 'is-invalid' : '' }}"
                            id="name" name="name" value="{{ old('name') }}" placeholder="John Doe" required>
                        @if (isset($errors) && $errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email"
                            class="form-control {{ isset($errors) && $errors->has('email') ? 'is-invalid' : '' }}"
                            id="email" name="email" value="{{ old('email') }}" placeholder="john@example.com"
                            required>
                        @if (isset($errors) && $errors->has('email'))
                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Mobile Number</label>
                        <input type="tel"
                            class="form-control {{ isset($errors) && $errors->has('phone') ? 'is-invalid' : '' }}"
                            id="phone" name="mobile" value="{{ old('phone') }}" placeholder="1234567890"
                            pattern="[0-9]{10}" required>
                        <small class="form-text text-muted">Enter a valid 10-digit mobile number</small>
                        @if (isset($errors) && $errors->has('phone'))
                            <div class="invalid-feedback">{{ $errors->first('phone') }}</div>
                        @endif
                    </div>
                    <label class="form-label fw-bold">Select Plan</label>
                    <div class="plan-toggle mb-4">
                        {{-- <input type="radio" id="basic" name="plan" value="basic"
                            {{ old('plan', 'basic') == 'basic' ? 'checked' : '' }}>
                        <label for="basic">
                            Basic<br>
                            <span class="plan-amount">
                                <span class="original-price">₹399</span> <!-- Original -->
                                <span class="discounted-price">₹299</span> <!-- Discounted -->
                            </span>
                        </label>

                        <input type="radio" id="standard" name="plan" value="standard"
                            {{ old('plan') == 'standard' ? 'checked' : '' }}>
                        <label for="standard">
                            Standard<br>
                            <span class="plan-amount">
                                <span class="original-price">₹3999</span>
                                <span class="discounted-price">₹2999</span>
                            </span>
                        </label>

                        <input type="radio" id="super_saving" name="plan" value="super_saving"
                            {{ old('plan') == 'super_saving' ? 'checked' : '' }}>
                        <label for="super_saving">
                            Super Saving<br>
                            <span class="plan-amount">
                                <span class="original-price">₹3999</span>
                                <span class="discounted-price">₹2999</span>
                            </span>
                        </label> --}}

                        @foreach ($plans as $plan)
                            <input type="radio" id="{{ $plan['slug'] }}" name="plan_name"
                                value="{{ $plan['title'] }}" {{ $selected_plan == $plan['slug'] ? 'checked' : '' }}>

                            <label for="{{ $plan['slug'] }}">
                                {{ $plan['title'] }}<br>
                                <span class="plan-amount">
                                    <span class="original-price">₹{{ number_format($plan['original_price']) }}</span>
                                    <span
                                        class="discounted-price">₹{{ number_format($plan['discounted_price']) }}</span>
                                </span>
                                @if (!empty($plan['badge']))
                                    <span class="badge">{{ $plan['badge'] }}</span>
                                @endif
                            </label>

                            {{-- <ul class="plan-features">
                                @foreach ($plan['features'] as $feature)
                                    <li class="{{ $feature['included'] ? 'included' : 'excluded' }}">
                                        {{ $feature['text'] }}
                                    </li>
                                @endforeach
                            </ul> --}}

                            {{-- <button class="btn {{ $plan['button_class'] }}">
                                {{ $plan['button_text'] }}
                            </button> --}}
                        @endforeach

                    </div>
                    <div id="plan-error" class="text-danger mt-2" style="display:none; margin-bottom: 20px;">
                        Please select a subscription plan before continuing.
                    </div>


                    <button type="submit" class="btn btn-pay">Subscribe & Pay with PhonePe</button>
                </form>

                <p class="footer-text">&copy; 2025 InstAppoint. Secured by <a href="https://www.phonepe.com"
                        target="_blank">PhonePe</a>.</p>
            </div>
        </div>
        <div>&nbsp;</div>
        <div>&nbsp;</div>
        <div>&nbsp;</div>
        <div>&nbsp;</div>
        <div style="margin-bottom: 1rem">&nbsp;</div>
    </div>
    <script>
        const subscriptionForm = document.getElementById('subscriptionForm');
        const planError = document.getElementById('plan-error');
        const planInputs = document.querySelectorAll('input[name="plan_name"]');

        // Hide error when a plan is selected
        planInputs.forEach(input => {
            input.addEventListener('change', () => {
                planError.style.display = 'none';
            });
        });

        // Form submission check
        subscriptionForm.addEventListener('submit', function(event) {
            const selectedPlan = document.querySelector('input[name="plan_name"]:checked');
            if (!selectedPlan) {
                event.preventDefault(); // Stop form submission
                planError.style.display = 'block';
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
