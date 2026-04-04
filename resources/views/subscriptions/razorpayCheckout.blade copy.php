<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Checkout</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .loader-container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 400px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            font-size: 14px;
        }

        .status-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
        }

        .status-checking {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="loader-container">
        <div class="spinner" id="spinner"></div>
        <h2 id="statusTitle">Processing Payment...</h2>
        <p id="statusMessage">Please complete the payment in the Razorpay window</p>
        <div id="additionalMessage"></div>
    </div>

    <!-- Hidden forms for POST submission -->
    <form id="paymentSuccessForm" method="POST" action="{{ route('subscription.callback') }}" style="display:none;">
        @csrf
        <input type="hidden" name="razorpay_order_id" id="success_order_id">
        <input type="hidden" name="transactionId" id="success_transaction_id">
        <input type="hidden" name="razorpay_payment_id" id="success_payment_id">
        <input type="hidden" name="razorpay_signature" id="success_signature">
        <input type="hidden" name="payment_gateway" value="razorpay">
    </form>

    <form id="paymentFailedForm" method="POST" action="{{ route('subscription.razorpay.failed') }}"
        style="display:none;">
        @csrf
        <input type="hidden" name="razorpay_order_id" id="failed_order_id">
        <input type="hidden" name="razorpay_payment_id" id="failed_payment_id">
        <input type="hidden" name="reason" id="failed_reason">
        <input type="hidden" name="payment_gateway" value="razorpay">
    </form>

    <script>
        const orderId = "{{ session('payment_transaction_id') }}";
        const razorpayKeyId = "{{ config('payment.razorpay.key_id') }}";
        const checkStatusUrl = "{{ route('subscription.check-status') }}"; // You need to create this route

        let paymentCompleted = false;
        let statusCheckInterval = null;
        let rzpInstance = null;

        // Update UI message
        function updateStatus(title, message, additionalMsg = '', type = 'checking') {
            document.getElementById('statusTitle').textContent = title;
            document.getElementById('statusMessage').textContent = message;

            const additionalDiv = document.getElementById('additionalMessage');
            if (additionalMsg) {
                additionalDiv.innerHTML = `<div class="status-message status-${type}">${additionalMsg}</div>`;
            } else {
                additionalDiv.innerHTML = '';
            }
        }

        // Check payment status via AJAX
        function checkPaymentStatus() {
            console.log('Checking payment status for order:', orderId);

            fetch(checkStatusUrl + '?order_id=' + orderId, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Status check response:', data);

                    if (data.status === 'paid' || data.status === 'completed') {
                        // Payment successful
                        clearInterval(statusCheckInterval);
                        paymentCompleted = true;

                        updateStatus(
                            'Payment Successful!',
                            'Redirecting...',
                            'Your payment has been confirmed. Please wait.',
                            'success'
                        );

                        // Fill success form
                        document.getElementById('success_order_id').value = data.razorpay_order_id || orderId;
                        document.getElementById('success_transaction_id').value = orderId;
                        document.getElementById('success_payment_id').value = data.razorpay_payment_id || '';
                        document.getElementById('success_signature').value = data.razorpay_signature || '';

                        // Submit success form after short delay
                        setTimeout(() => {
                            //document.getElementById('paymentSuccessForm').submit();
                        }, 1500);

                    } else if (data.status === 'failed') {
                        // Payment failed
                        clearInterval(statusCheckInterval);
                        paymentCompleted = true;

                        updateStatus(
                            'Payment Failed',
                            'Redirecting...',
                            data.message || 'Payment could not be processed.',
                            'failed'
                        );

                        // Fill failed form
                        document.getElementById('failed_order_id').value = orderId;
                        document.getElementById('failed_payment_id').value = data.razorpay_payment_id || '';
                        document.getElementById('failed_reason').value = data.message || 'Payment failed';

                        // Submit failed form after short delay
                        setTimeout(() => {
                            document.getElementById('paymentFailedForm').submit();
                        }, 1500);
                    }
                    // If status is 'pending', continue polling
                })
                .catch(error => {
                    console.error('Status check error:', error);
                    // Continue polling even on error
                });
        }

        // Start status polling (for QR code payments)
        function startStatusPolling() {
            // Check immediately
            checkPaymentStatus();

            // Then check every 3 seconds
            statusCheckInterval = setInterval(checkPaymentStatus, 3000);

            // Stop polling after 10 minutes (safety measure)
            setTimeout(() => {
                if (!paymentCompleted) {
                    clearInterval(statusCheckInterval);
                    updateStatus(
                        'Payment Timeout',
                        'Please try again',
                        'Payment verification timed out. If payment was deducted, it will be refunded.',
                        'failed'
                    );

                    setTimeout(() => {
                        window.location.href =
                            "{{ route('subscription.status', ['status' => 'timeout']) }}";
                    }, 3000);
                }
            }, 600000); // 10 minutes
        }

        // Razorpay options
        const options = {
            key: razorpayKeyId,
            order_id: orderId,
            name: "{{ config('app.name') }}",
            description: "Subscription Payment",
            image: "{{ asset('logo.png') }}",

            handler: function(response) {
                console.log('Payment handler called:', response);

                // Stop polling
                if (statusCheckInterval) {
                    clearInterval(statusCheckInterval);
                }

                paymentCompleted = true;

                updateStatus(
                    'Payment Successful!',
                    'Verifying payment...',
                    'Please wait while we confirm your payment.',
                    'success'
                );

                // Fill success form
                document.getElementById('success_order_id').value = response.razorpay_order_id;
                document.getElementById('success_transaction_id').value = response.razorpay_order_id;
                document.getElementById('success_payment_id').value = response.razorpay_payment_id;
                document.getElementById('success_signature').value = response.razorpay_signature;

                // Submit form
                setTimeout(() => {
                    document.getElementById('paymentSuccessForm').submit();
                }, 1000);
            },

            prefill: {
                name: "{{ session('payment_name') }}",
                email: "{{ session('payment_email') }}",
                contact: "{{ session('payment_phone') }}"
            },

            config: {
                display: {
                    blocks: {
                        banks: {
                            name: 'All payment methods',
                            instruments: [{
                                    method: 'upi'
                                },
                                {
                                    method: 'card'
                                },
                                {
                                    method: 'netbanking'
                                },
                                {
                                    method: 'wallet'
                                }
                            ]
                        }
                    },
                    sequence: ['block.banks'],
                    preferences: {
                        show_default_blocks: true
                    }
                }
            },

            theme: {
                color: "#667eea"
            },

            modal: {
                ondismiss: function() {
                    console.log('Payment modal dismissed');

                    if (!paymentCompleted) {
                        updateStatus(
                            'Checking Payment Status...',
                            'Verifying if payment was completed',
                            'For QR code payments, please wait while we verify your payment.',
                            'checking'
                        );

                        // Continue checking status for 30 seconds after modal close
                        // This handles QR code payments where user closes modal after scanning
                        if (!statusCheckInterval) {
                            startStatusPolling();
                        }

                        // After 30 seconds, if still not completed, redirect to status page
                        setTimeout(() => {
                            if (!paymentCompleted) {
                                clearInterval(statusCheckInterval);
                                window.location.href =
                                    "{{ route('subscription.status', ['status' => 'pending']) }}";
                            }
                        }, 30000);
                    }
                },

                escape: true,
                backdropclose: false,

                // Handle modal open
                onopen: function() {
                    console.log('Razorpay modal opened');
                    updateStatus(
                        'Complete Your Payment',
                        'Choose your payment method',
                        'You can pay using UPI, Card, Net Banking, or Wallet. For UPI QR, scan and wait for confirmation.',
                        'checking'
                    );
                }
            }
        };

        // Initialize Razorpay
        rzpInstance = new Razorpay(options);

        // Handle payment failure
        rzpInstance.on('payment.failed', function(response) {
            console.log('Payment failed:', response);

            // Stop polling
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
            }

            paymentCompleted = true;

            updateStatus(
                'Payment Failed',
                'Redirecting...',
                response.error.description || 'Payment could not be processed.',
                'failed'
            );

            // Fill failed form
            document.getElementById('failed_order_id').value = response.error.metadata?.order_id || orderId;
            document.getElementById('failed_payment_id').value = response.error.metadata?.payment_id || '';
            document.getElementById('failed_reason').value = response.error.reason || response.error.description ||
                'Payment failed';

            // Submit failed form
            setTimeout(() => {
                document.getElementById('paymentFailedForm').submit();
            }, 2000);
        });

        // Open Razorpay modal
        setTimeout(() => {
            rzpInstance.open();
        }, 500);

        // Prevent accidental page close
        window.addEventListener('beforeunload', function(e) {
            if (!paymentCompleted) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>

</html>
