<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhonePe Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .payment-card {
            max-width: 550px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .payment-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .payment-logo img {
            height: 60px;
        }
        .payment-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .details-table {
            margin-bottom: 30px;
        }
        .btn-phonepe {
            background-color: #5f259f;
            color: white;
        }
        .btn-phonepe:hover {
            background-color: #4a1d7a;
            color: white;
        }
        .payment-info {
            background-color: #f0f9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
<div class="container d-none">
    <div class="payment-card">

        <h2 class="payment-title">Complete Your Payment</h2>

        <div class="payment-info">
            <p class="mb-0"><i class="bi bi-info-circle me-2"></i> You are about to make a payment via PhonePe. Click the button below to continue.</p>
        </div>

        <table class="table details-table">
            <tbody>
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
            <tr>
                <th>Provider:</th>
                <td>{{ $appointment->provider->name ?? 'Service Provider' }}</td>
            </tr>
            <tr>
                <th>Amount:</th>
                <td><strong>₹{{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
            <tr>
                <th>Payment Method:</th>
                <td><span class="badge bg-primary">PhonePe</span></td>
            </tr>
            </tbody>
        </table>

        <form action="{{ route('phonepe.bridge.process') }}" method="POST">
            @csrf
            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-phonepe btn-lg" id="payButtonId">
                    <i class="bi bi-credit-card me-2"></i> Pay Now with PhonePe
                </button>
            </div>
        </form>


        <div class="mt-3 text-center">
            <small class="text-muted">By clicking "Pay Now", you will be redirected to PhonePe's secure payment gateway.</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.onload = function() {
        var payButtonId = document.getElementById("payButtonId");

        payButtonId.click(); // this will trigger the click event

    };
</script>
</body>
</html>
