<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Checkout</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>

<body>
    <div id="loading">
        <h2>Processing your payment...</h2>
        <p>Please wait while we redirect you to payment gateway.</p>
    </div>

    <div id="loading1">
        <h2>Processing your payment...</h2>
    </div>

    <form id="paymentSuccessForm" method="POST" action="{{ route('subscription.callback') }}" style="display:none;">
        @csrf

        <input type="hidden" name="razorpay_order_id">
        <input type="hidden" name="transactionId">
        <input type="hidden" name="razorpay_payment_id">
        <input type="hidden" name="razorpay_signature">
        <input type="hidden" name="payment_gateway" value="razorpay">
    </form>

    <form id="paymentFailedForm" method="POST" action="{{ route('subscription.razorpay.failed') }}"
        style="display:none;">
        @csrf

        <input type="hidden" id="razorpay_order_id" name="razorpay_order_id">
        <input type="hidden" id="razorpay_payment_id" name="razorpay_payment_id">
        <input type="hidden" name="reason">
        <input type="hidden" name="payment_gateway" value="razorpay">
    </form>


    <script>
        const orderId = "{{ session('payment_transaction_id') }}";
        const razorpayKeyId = "{{ config('payment.razorpay.key_id') }}";

        const loadingDiv = document.getElementById('loading');
        const loadingDiv1 = document.getElementById('loading1');

        const options = {
            key: razorpayKeyId,
            order_id: orderId,
            name: "{{ config('app.name') }}",
            description: "Subscription Payment",
            image: "{{ asset('logo.png') }}",

            handler: function(response) {
                // Show loader again while redirecting
                loadingDiv1.style.display = 'block';

                // Fill form values
                document.querySelector('[name="razorpay_order_id"]').value = response.razorpay_order_id;
                document.querySelector('[name="razorpay_payment_id"]').value = response.razorpay_payment_id;
                document.querySelector('[name="razorpay_signature"]').value = response.razorpay_signature;
                document.querySelector('[name="transactionId"]').value = response.razorpay_order_id;

                // Submit POST form
                document.getElementById('paymentSuccessForm').submit();
            },

            prefill: {
                name: "{{ session('payment_name') }}",
                email: "{{ session('payment_email') }}",
                contact: "{{ session('payment_phone') }}"
            },

            method: {
                upi: true,
                card: true,
                netbanking: true,
                wallet: true
            },

            theme: {
                color: "#3399cc"
            },

            modal: {
                ondismiss: function() {

                    // Show loader again when modal is closed
                    loadingDiv.style.display = 'block';

                    window.location.href =
                        "{{ route('subscription.status', ['status' => 'failed', 'message' => 'Subscription canceled']) }}";
                }
            }
        };

        const rzp = new Razorpay(options);

        rzp.on('payment.failed', function(response) {
            console.log(response);
            // // Show loader again before redirect
            // loadingDiv.style.display = 'block';

            // //alert('Payment failed: ' + response.error.description);
            // window.location.href = callbackUrl + '?status=failed';
            console.log(response?.error?.metadata
                ?.order_id);
            document.querySelector('[id="razorpay_order_id"]').value = response?.error?.metadata
                ?.order_id;
            document.querySelector('[id="razorpay_payment_id"]').value = response?.error?.metadata
                ?.payment_id;
            document.querySelector('[name="reason"]').value = response?.error?.reason;
            // Submit POST form
            document.getElementById('paymentFailedForm').submit();
        });

        // Hide loading once Razorpay modal opens
        loadingDiv.style.display = 'none';
        rzp.open();
    </script>

</body>

</html>
