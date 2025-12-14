<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Successful | InstAppoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-color: #5F259F;
            --accent-blue: #3B82F6;
            --success-green: #22c55e;
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

        .success-card {
            display: flex;
            flex-wrap: wrap;
            max-width: 880px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .success-card:hover {
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12);
        }

        /* Left Branding */
        .success-image {
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

        .success-image img {
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
        .success-content {
            flex: 1 1 60%;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .success-title {
            color: var(--success-green);
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .success-title i {
            font-size: 32px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .success-message {
            color: #555;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .alert-custom {
            background-color: #f0fdf4;
            border: 1px solid #a7f3d0;
            border-left: 5px solid var(--success-green);
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

        .alert-custom p {
            /* color: var(--text-dark); */
            /* font-size: 17px; */
            margin-bottom: 8px;
            /* font-weight: 600; */
        }

        .btn-dashboard {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 10px 25px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .btn-dashboard:hover {
            background-color: #4a1d7a;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .success-card {
                flex-direction: column;
            }

            .success-image {
                padding: 35px 20px;
            }

            .success-content {
                padding: 30px 25px;
            }

            .success-image img {
                width: 90px;
            }

            .brand-name {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <div class="success-card">
        <!-- Left Branding -->
        <div class="success-image">
            <img src="{{ asset('images/dark_logo.png') }}" alt="InstAppoint Logo">
            <h1 class="brand-name">InstAppoint</h1>
            <p class="brand-tagline">Smart Scheduling Made Simple</p>
        </div>

        <!-- Right Content -->
        <div class="success-content">
            <h2 class="success-title"><i class="bi bi-check-circle-fill"></i> Subscription Successful</h2>
            <p class="success-message">
                {{ $message ?? 'Your subscription was successfully activated. Thank you for choosing InstAppoint!' }}
            </p>

            <div class="alert alert-custom">
                <h5>Subscription Detail:</h5>
                <p><strong>User Name:</strong> {{ $paymentDetails['name'] ?? 'N/A' }}</p>
                <p><strong>Plan Name:</strong> {{ $paymentDetails['plan_name'] ?? 'N/A' }}</p>
                <p><strong>Subscription ID:</strong> {{ $paymentDetails['transaction_id'] ?? 'N/A' }}</p>
                <p><strong>Status:</strong> Active</p>
                <p><strong>Response Code:</strong> {{ $paymentDetails['responseCode'] ?? 'SUCCESS' }}</p>
            </div>

            <a href="/" class="btn btn-dashboard btn-lg">Go to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
