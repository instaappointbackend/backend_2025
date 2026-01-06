<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Payment - Test</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .payment-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .amount-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .amount-row .detail-label,
        .amount-row .detail-value {
            color: white;
        }

        .amount-row .detail-value {
            font-size: 24px;
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .info-box h4 {
            color: #856404;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .info-box p {
            color: #856404;
            font-size: 12px;
            line-height: 1.5;
        }

        .test-cards {
            margin-top: 10px;
            font-size: 11px;
            color: #856404;
        }

        .spinner {
            display: none;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
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

        .loading .spinner {
            display: block;
        }

        .loading .btn {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <h1>💳 Payment Checkout</h1>
            <p>Secure payment powered by Razorpay</p>
        </div>

        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Appointment ID</span>
                <span class="detail-value">#{{ $appointment->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Patient Name</span>
                <span class="detail-value">{{ $appointment->patient_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order ID</span>
                <span class="detail-value">{{ $order_id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment ID</span>
                <span class="detail-value">#{{ $payment_id }}</span>
            </div>

            <div class="amount-row">
                <div class="detail-row">
                    <span class="detail-label">Total Amount</span>
                    <span class="detail-value">₹{{ number_format($amount / 100, 2) }}</span>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" id="payButton" onclick="initiatePayment()">
            Pay Now with Razorpay
        </button>

        <button class="btn btn-secondary" onclick="window.location.href='{{ route('razorpay.test.page') }}'">
            Cancel Payment
        </button>

        <div class="spinner" id="spinner"></div>

        <div class="info-box">
            <h4>🧪 Test Mode Active</h4>
            <p>This is a test transaction. Use test card numbers to complete payment.</p>
            <div class="test-cards">
                <strong>Test Card:</strong> 4111 1111 1111 1111<br>
                <strong>CVV:</strong> Any 3 digits | <strong>Expiry:</strong> Any future date
            </div>
        </div>
    </div>

    <script>
        const options = {
            key: '{{ $key_id }}',
            amount: {{ $amount }},
            currency: '{{ $currency }}',
            order_id: '{{ $order_id }}',
            name: 'Your Clinic Name',
            description: 'Appointment Payment',
            image: 'https://via.placeholder.com/100x100?text=Logo',
            handler: function(response) {
                handlePaymentSuccess(response);
            },
            prefill: {
                name: '{{ $appointment->patient_name ?? 'Test User' }}',
                email: '{{ $appointment->email ?? 'test@example.com' }}',
                contact: '{{ $appointment->phone ?? '9999999999' }}'
            },
            theme: {
                color: '#667eea'
            },
            modal: {
                ondismiss: function() {
                    console.log('Payment cancelled by user');
                }
            }
        };

        const rzp = new Razorpay(options);

        rzp.on('payment.failed', function(response) {
            handlePaymentFailure(response.error);
        });

        function initiatePayment() {
            document.getElementById('payButton').classList.add('loading');
            rzp.open();
        }

        function handlePaymentSuccess(response) {
            console.log('Payment Success:', response);
            document.getElementById('spinner').style.display = 'block';

            // Send to callback URL
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ $callbackUrl }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            console.log('response', response);
            const fields = {
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature
            };

            for (const key in fields) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }

        function handlePaymentFailure(error) {
            console.error('Payment Failed:', error);
            alert('Payment Failed: ' + error.description);
            document.getElementById('payButton').classList.remove('loading');
        }

        // Auto-open payment on page load (optional)
        // window.onload = function() {
        //     initiatePayment();
        // };
    </script>
</body>

</html>
