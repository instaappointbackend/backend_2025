<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <title>Payment Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .success-card {
            max-width: 550px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
        .success-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .details-table {
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
    <div class="success-card">
        <div class="success-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h2 class="success-title">Payment Successful!</h2>

        <table class="table details-table">
            <tbody>
            <tr>
                <th>Transaction ID:</th>
                <td>{{ $payment->transaction_id }}</td>
            </tr>
            <tr>
                <th>Amount:</th>
                <td>₹{{ number_format($payment->amount, 2) }}</td>
            </tr>
            <tr>
                <th>Payment Status:</th>
                <td><span class="badge bg-success">Completed</span></td>
            </tr>
            <tr>
                <th>Service:</th>
                <td>{{ $appointment->service->name ?? 'Appointment Service' }}</td>
            </tr>
            <tr>
                <th>Date:</th>
                <td>{{ \Carbon\Carbon::parse($appointment->date)->format('F d, Y') }}</td>
            </tr>
            <tr>
                <th>Time:</th>
                <td>{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</td>
            </tr>
            </tbody>
        </table>
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
