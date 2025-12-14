<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .btn-retry {
            background-color: #5f259f;
            color: white;
        }
        .btn-retry:hover {
            background-color: #4a1d7a;
            color: white;
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
            <p class="failed-message">{{ $message }}</p>
            
            @if(isset($status))
            <div class="alert alert-secondary mb-4">
                <h5>Transaction Details:</h5>
                <p><strong>Transaction ID:</strong> {{ $status['transactionId'] }}</p>
                <p><strong>Status:</strong> {{ $status['paymentState'] }}</p>
                <p><strong>Response Code:</strong> {{ $status['responseCode'] ?? 'N/A' }}</p>
            </div>
            @endif
            
            <div class="d-grid gap-2">
                <a href="{{ route('phonepe.form') }}" class="btn btn-retry">Try Again</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>