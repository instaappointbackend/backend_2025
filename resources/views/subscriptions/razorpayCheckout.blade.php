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

        .check-count {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="loader-container">
        <div class="spinner" id="spinner"></div>
        <h2 id="statusTitle">Processing Payment...</h2>
        <p id="statusMessage">Please complete the payment in the Razorpay window</p>
        <div id="additionalMessage"></div>
        <div class="check-count" id="checkCount"></div>
    </div>

    <script>
        const orderId = "{{ session('payment_transaction_id') }}";
        const razorpayKeyId = "{{ config('payment.razorpay.key_id') }}";
        const checkStatusUrl = "{{ route('subscription.check-status') }}";
        const callbackUrl = "{{ route('subscription.razorpay.callback') }}";
        const callbackFailedUrl = "{{ route('subscription.razorpay.failed') }}";

        let paymentCompleted = false;
        let statusCheckInterval = null;
        let rzpInstance = null;
        let checkCount = 0;

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

        // Check payment status via AJAX and redirect directly
        function checkPaymentStatus() {
            checkCount++;
            document.getElementById('checkCount').textContent = `Checking... (${checkCount})`;

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
                            'Your payment has been confirmed.',
                            'success'
                        );

                        // Direct redirect with transaction_id
                        setTimeout(() => {
                            window.location.href = callbackUrl + '?transactionId=' + orderId;
                        }, 1000);

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

                        // Redirect to failure page
                        setTimeout(() => {
                            window.location.href = callbackUrl + '?transactionId=' + orderId + '&status=failed';
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
                //console.log('Payment handler called:', response);

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

                // Direct redirect to callback with payment details
                setTimeout(() => {
                    const params = new URLSearchParams({
                        transactionId: response.razorpay_order_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature,
                        payment_gateway: 'razorpay'
                    });

                    window.location.href = callbackUrl + '?' + params.toString();
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
                        console.log('here');
                        updateStatus(
                            'Checking Payment Status...',
                            'Verifying if payment was completed',
                            'For QR code payments, please wait while we verify your payment.',
                            'checking'
                        );

                        // Continue checking status for 30 seconds after modal close
                        //This handles QR code payments where user closes modal after scanning
                        if (!statusCheckInterval) {
                            startStatusPolling();
                        }

                        // After 30 seconds, if still not completed, redirect to status page
                        setTimeout(() => {
                            if (!paymentCompleted) {
                                clearInterval(statusCheckInterval);
                                window.location.href =
                                    "{{ route('subscription.status', ['status' => 'failed', 'message' => 'Subscription canceled']) }}";
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

            // Direct redirect with error details
            setTimeout(() => {
                const params = new URLSearchParams({
                    razorpay_order_id: response?.error?.metadata
                        ?.order_id,
                    razorpay_payment_id: response?.error?.metadata
                        ?.payment_id,
                    reason: response?.error?.reason,
                });

                window.location.href = callbackFailedUrl + '?' + params.toString();
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
