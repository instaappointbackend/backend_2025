<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .btn-back {
            background-color: #5f259f;
            color: white;
        }
        .btn-back:hover {
            background-color: #4a1d7a;
            color: white;
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
                        <td>{{ $transaction['transaction_id'] }}</td>
                    </tr>
                    <tr>
                        <th>Amount:</th>
                        <td>₹{{ number_format($transaction['amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Payment Status:</th>
                        <td><span class="badge bg-success">Completed</span></td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td>{{ $transaction['name'] }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $transaction['email'] }}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{{ $transaction['phone'] }}</td>
                    </tr>
                    @if(isset($payment['providerReferenceId']))
                    <tr>
                        <th>Provider Reference:</th>
                        <td>{{ $payment['providerReferenceId'] }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
            
            <div class="d-grid gap-2">
                <a href="{{ route('phonepe.form') }}" class="btn btn-back">Make Another Payment</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>