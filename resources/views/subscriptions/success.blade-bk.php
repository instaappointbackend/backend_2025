<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .icon.pending {
            background: #fff3cd;
            color: #856404;
        }

        .icon.timeout {
            background: #f8d7da;
            color: #721c24;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #333;
        }

        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }

        .info-box h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
        }

        .info-box ul {
            list-style: none;
            padding: 0;
        }

        .info-box li {
            padding: 8px 0;
            color: #666;
            font-size: 14px;
        }

        .info-box li::before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        @if ($status === 'pending')
            <div class="icon pending">⏱️</div>
            <h1>Payment Being Verified</h1>
            <p>We're checking the status of your payment. This usually takes a few seconds.</p>

            <div class="spinner"></div>

            <div class="info-box">
                <h3>What happens next?</h3>
                <ul>
                    <li>We're verifying your payment with the gateway</li>
                    <li>You'll be redirected once confirmed</li>
                    <li>Check your email for confirmation</li>
                </ul>
            </div>

            <script>
                // Auto-refresh every 3 seconds to check status
                let checkCount = 0;
                const maxChecks = 20; // Check for 1 minute (20 * 3 seconds)

                const checkInterval = setInterval(() => {
                    checkCount++;

                    if (checkCount >= maxChecks) {
                        clearInterval(checkInterval);
                        window.location.href = "{{ route('subscription.status', ['status' => 'timeout']) }}";
                        return;
                    }

                    // Reload page to check status
                    window.location.reload();
                }, 3000);
            </script>
        @elseif($status === 'timeout')
            <div class="icon timeout">⏰</div>
            <h1>Payment Verification Timeout</h1>
            <p>{{ $message ?: 'We couldn\'t verify your payment in time. If money was deducted, it will be refunded within 5-7 business days.' }}
            </p>

            <div class="info-box">
                <h3>What to do next?</h3>
                <ul>
                    <li>Check your bank statement for deduction</li>
                    <li>Contact support if amount was deducted</li>
                    <li>Try creating a new subscription</li>
                </ul>
            </div>

            <a href="{{ route('home') }}" class="btn" style="margin-top: 20px;">Go to Homepage</a>
        @else
            <div class="icon pending">ℹ️</div>
            <h1>Payment Status: {{ ucfirst($status) }}</h1>
            <p>{{ $message ?: 'Please check your subscription status or contact support.' }}</p>

            <a href="{{ route('home') }}" class="btn" style="margin-top: 20px;">Go to Homepage</a>
        @endif
    </div>
</body>

</html>
