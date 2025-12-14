<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Failed | InstAppoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-color: #5F259F;
            --accent-blue: #3B82F6;
            --light-bg: #F4F5FB;
            --text-dark: #333;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f3f4f7, #e9ecff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .failed-card {
            display: flex;
            flex-wrap: wrap;
            max-width: 880px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .failed-card:hover {
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12);
        }

        /* Left Branding */
        .failed-image {
            flex: 1 1 40%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-blue));
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 50px 30px;
            text-align: center;
        }

        .failed-image img {
            width: 120px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.25));
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .brand-tagline {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 4px;
        }

        /* Right Content */
        .failed-content {
            flex: 1 1 60%;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .failed-title {
            color: #e74c3c;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .failed-title i {
            font-size: 32px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .failed-message {
            color: #555;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .alert-custom {
            background-color: #fafaff;
            border: 1px solid #e0d7f8;
            border-left: 5px solid var(--primary-color);
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 25px;
            text-align: left;
        }

        .alert-custom h5 {
            color: var(--text-dark);
            font-size: 17px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .btn-retry {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 10px 25px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .btn-retry:hover {
            background-color: #4a1d7a;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .failed-card {
                flex-direction: column;
            }

            .failed-image {
                padding: 35px 20px;
            }

            .failed-content {
                padding: 30px 25px;
            }

            .failed-image img {
                width: 90px;
            }

            .brand-name {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <div class="failed-card">
        <!-- Left Branding -->
        <div class="failed-image">
            <img src="{{ asset('images/dark_logo.png') }}" alt="InstAppoint Logo">
            <h1 class="brand-name">InstAppoint</h1>
            <p class="brand-tagline">Smart Scheduling Made Simple</p>
        </div>

        <!-- Right Content -->
        <div class="failed-content">
            <h2 class="failed-title"><i class="bi bi-x-circle-fill"></i> Subscription Failed</h2>
            <p class="failed-message">
                {{ $message ?? 'Your payment is in a pending state. Please try again later.' }}
            </p>

            @if (isset($status))
                <div class="alert alert-custom">
                    <h5>Subscription Details:</h5>
                    <p><strong>Subscription ID:</strong> {{ $status['subscriptionId'] ?? 'N/A' }}</p>
                    <p><strong>Status:</strong> {{ $status['subscriptionState'] ?? 'Failed' }}</p>
                    <p><strong>Response Code:</strong> {{ $status['responseCode'] ?? 'N/A' }}</p>
                </div>
            @endif

            <a href="{{ route('subscription.form') }}" class="btn btn-retry btn-lg">Try Again</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
