<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .failed-card {
            max-width: 550px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .failed-icon {
            font-size: 80px;
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
        }
        .failed-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .failed-message {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn-return {
            background-color: #5f259f;
            color: white;
        }
        .btn-return:hover {
            background-color: #4a1d7a;
            color: white;
        }
        .auto-redirect {
            text-align: center;
            margin-top: 15px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="failed-card">
        <div class="failed-icon">
            <i class="bi bi-x-circle-fill"></i>
        </div>
        <h2 class="failed-title">Payment Failed</h2>
        <p class="failed-message">{{ $errorMessage ?? 'Your payment could not be processed at this time.' }}</p>

        @if(isset($status))
        <div class="alert alert-secondary mb-4">
            <h5>Transaction Details:</h5>
            <p><strong>Transaction ID:</strong> {{ $payment->transaction_id ?? $status['transactionId'] ?? 'N/A' }}</p>
            <p><strong>Status:</strong> {{ $status['paymentState'] ?? 'FAILED' }}</p>
            <p><strong>Response Code:</strong> {{ $status['responseCode'] ?? 'N/A' }}</p>
        </div>
        @endif

        <div class="d-grid gap-2">
            <a href="{{ $returnUrl }}" class="btn btn-return btn-lg">
                <i class="bi bi-arrow-left me-2"></i> Return to App
            </a>
        </div>

        <div class="auto-redirect">
            Redirecting you back to the app in <span id="countdown">5</span> seconds...
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto redirect to app after 5 seconds
    let seconds = 5;
    const countdownElement = document.getElementById('countdown');
    const returnUrl = {!! json_encode($returnUrl) !!}; // Use json_encode to prevent HTML entity encoding

    // For debugging - print the URL to console
    console.log('Return URL:', returnUrl);

    const countdown = setInterval(() => {
        seconds--;
        countdownElement.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(countdown);
            // Use window.location.replace instead of href for better handling of special characters
            window.location.replace(returnUrl);
        }
    }, 1000);

    // Also update the return button with the correct URL (no entity encoding)
    document.addEventListener('DOMContentLoaded', function() {
        const returnButton = document.querySelector('.btn-return');
        if (returnButton) {
            returnButton.setAttribute('href', returnUrl);
        }
    });
</script>
</body>
</html>
