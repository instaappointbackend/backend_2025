<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay API Tester</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-icon {
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            color: #555;
            font-size: 14px;
            margin-bottom: 5px;
            font-weight: 500;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            font-family: monospace;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .response-box {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .response-box h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .response-content {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }

        .response-content pre {
            margin: 0;
            white-space: pre-wrap;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 13px;
            color: #856404;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .quick-actions button {
            flex: 1;
            padding: 10px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Razorpay API Testing Dashboard</h1>
            <p>Test all Razorpay mobile API endpoints without a mobile app</p>
        </div>
        <div class="form-group">
            <label>Authorization Token (Optional)</label>
            <input type="text" name="token" id="token" placeholder="Bearer token if auth required">
        </div>
        <div class="grid">
            <!-- Test 1: Create Order -->

            <div class="card">
                <h3>
                    <span class="card-icon">1️⃣</span>
                    Create Order
                    <span class="badge badge-info">POST</span>
                </h3>
                <form id="createOrderForm">
                    <div class="form-group">
                        <label>Appointment ID</label>
                        <input type="number" name="appointment_id" value="1" required>
                    </div>
                    {{-- <div class="form-group">
                        <label>Authorization Token (Optional)</label>
                        <input type="text" name="token" placeholder="Bearer token if auth required">
                    </div> --}}
                    <button type="submit" class="btn btn-primary">Create Order</button>
                </form>
            </div>

            <!-- Test 2: Verify Payment -->
            <div class="card">
                <h3>
                    <span class="card-icon">2️⃣</span>
                    Verify Payment
                    <span class="badge badge-success">POST</span>
                </h3>
                <form id="verifyPaymentForm">
                    <div class="form-group">
                        <label>Razorpay Order ID</label>
                        <input type="text" name="razorpay_order_id" placeholder="order_xxx"
                            value="{{ $razorpayOrderId ?? '' }}" required>
                    </div>
                    <div class="form-group">
                        <label>Razorpay Payment ID</label>
                        <input type="text" name="razorpay_payment_id" placeholder="pay_xxx" required
                            value="{{ $razorpayPaymentId ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Razorpay Signature</label>
                        <input type="text" name="razorpay_signature" placeholder="signature" required
                            value="{{ $razorpaySignature ?? '' }}">
                    </div>
                    <button type="submit" class="btn btn-secondary">Verify Payment</button>
                </form>
            </div>

            <!-- Test 3: Handle Failure -->
            <div class="card">
                <h3>
                    <span class="card-icon">3️⃣</span>
                    Payment Failed
                    <span class="badge badge-danger">POST</span>
                </h3>
                <form id="paymentFailedForm">
                    <div class="form-group">
                        <label>Razorpay Order ID</label>
                        <input type="text" name="razorpay_order_id" placeholder="order_xxx" required>
                    </div>
                    <div class="form-group">
                        <label>Error Code</label>
                        <input type="text" name="error_code" value="BAD_REQUEST_ERROR">
                    </div>
                    <div class="form-group">
                        <label>Error Description</label>
                        <input type="text" name="error_description" value="Payment cancelled by user">
                    </div>
                    <button type="submit" class="btn btn-danger">Report Failure</button>
                </form>
            </div>

            <!-- Test 4: Get Payment Status -->
            <div class="card">
                <h3>
                    <span class="card-icon">4️⃣</span>
                    Payment Status
                    <span class="badge badge-info">GET</span>
                </h3>
                <form id="paymentStatusForm">
                    <div class="form-group">
                        <label>Payment ID</label>
                        <input type="text" name="payment_id" required>
                    </div>
                    <button type="submit" class="btn btn-info">Check Status</button>
                </form>
            </div>

            <!-- Test 5: Webhook Test -->
            <div class="card">
                <h3>
                    <span class="card-icon">5️⃣</span>
                    Simulate Webhook
                    <span class="badge badge-info">POST</span>
                </h3>
                <form id="webhookForm">
                    <div class="form-group">
                        <label>Webhook Event</label>
                        <select name="event">
                            <option value="payment.captured">payment.captured</option>
                            <option value="payment.authorized">payment.authorized</option>
                            <option value="payment.failed">payment.failed</option>
                            <option value="order.paid">order.paid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Order ID</label>
                        <input type="text" name="order_id" placeholder="order_xxx" required>
                    </div>
                    <div class="form-group">
                        <label>Payment ID</label>
                        <input type="text" name="payment_id" placeholder="pay_xxx" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Webhook</button>
                </form>
            </div>

            <!-- Web Payment Test -->
            <div class="card">
                <h3>
                    <span class="card-icon">🌐</span>
                    Web Payment Test
                    <span class="badge badge-success">WEB</span>
                </h3>
                <form id="webPaymentForm">
                    <div class="form-group">
                        <label>Appointment ID</label>
                        <input type="number" name="appointment_id" value="1" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Open Web Checkout</button>
                </form>
                <div class="note">
                    Opens Razorpay checkout in browser - simulates complete mobile flow
                </div>
            </div>
        </div>

        <!-- Response Box -->
        <div class="response-box">
            <h3>📊 API Response <span id="responseStatus"></span></h3>
            <div class="spinner" id="spinner"></div>
            <div class="response-content" id="responseContent">
                <pre>Waiting for API call...</pre>
            </div>
            <div class="quick-actions">
                <button onclick="clearResponse()" class="btn btn-secondary">Clear</button>
                <button onclick="copyResponse()" class="btn btn-info">Copy Response</button>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = '{{ url('/api') }}';
        const WEB_BASE_URL = '{{ url('') }}';

        // Create Order
        document.getElementById('createOrderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            await makeApiCall(`${API_BASE_URL}/razorpay/mobile/create-order`, 'POST', {
                appointment_id: parseInt(formData.get('appointment_id'))
            });
        });

        // Verify Payment
        document.getElementById('verifyPaymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            await makeApiCall(`${API_BASE_URL}/razorpay/mobile/verify-payment`, 'POST', {
                razorpay_order_id: formData.get('razorpay_order_id'),
                razorpay_payment_id: formData.get('razorpay_payment_id'),
                razorpay_signature: formData.get('razorpay_signature')
            });
        });

        // Payment Failed
        document.getElementById('paymentFailedForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            await makeApiCall(`${API_BASE_URL}/razorpay/mobile/payment-failed`, 'POST', {
                razorpay_order_id: formData.get('razorpay_order_id'),
                error_code: formData.get('error_code'),
                error_description: formData.get('error_description')
            });
        });

        // Payment Status
        document.getElementById('paymentStatusForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const paymentId = formData.get('payment_id');

            await makeApiCall(`${API_BASE_URL}/razorpay/mobile/payment-status/${paymentId}`, 'GET');
        });

        // Webhook
        document.getElementById('webhookForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            const webhookPayload = {
                event: formData.get('event'),
                payload: {
                    payment: {
                        entity: {
                            id: formData.get('payment_id'),
                            order_id: formData.get('order_id'),
                            status: formData.get('event') === 'payment.failed' ? 'failed' : 'captured',
                            amount: 50000
                        }
                    }
                }
            };

            await makeApiCall(`${API_BASE_URL}/razorpay/webhook`, 'POST', webhookPayload);
        });

        // Web Payment
        document.getElementById('webPaymentForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const appointmentId = formData.get('appointment_id');

            window.open(`${WEB_BASE_URL}/razorpay/test/checkout?appointment_id=${appointmentId}`, '_blank');
        });

        async function makeApiCall(url, method, data = null, token = null) {
            const spinner = document.getElementById('spinner');
            const responseContent = document.getElementById('responseContent');
            const responseStatus = document.getElementById('responseStatus');

            spinner.style.display = 'block';
            responseContent.innerHTML = '<pre>Loading...</pre>';
            responseStatus.innerHTML = '';

            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                };
                const token = document.getElementById('token').value ?? null;
                console.log('token', token);

                if (token) {
                    options.headers['Authorization'] = token.startsWith('Bearer ') ? token : `Bearer ${token}`;
                }

                if (data && method !== 'GET') {
                    options.body = JSON.stringify(data);
                }

                const response = await fetch(url, options);
                const result = await response.json();

                const statusBadge = response.ok ?
                    '<span class="badge badge-success">SUCCESS</span>' :
                    '<span class="badge badge-danger">ERROR</span>';

                responseStatus.innerHTML = statusBadge;
                responseContent.innerHTML = `<pre>${JSON.stringify(result, null, 2)}</pre>`;

            } catch (error) {
                responseStatus.innerHTML = '<span class="badge badge-danger">ERROR</span>';
                responseContent.innerHTML = `<pre style="color: #ff6b6b;">${error.message}</pre>`;
            } finally {
                spinner.style.display = 'none';
            }
        }

        function clearResponse() {
            document.getElementById('responseContent').innerHTML = '<pre>Waiting for API call...</pre>';
            document.getElementById('responseStatus').innerHTML = '';
        }

        function copyResponse() {
            const content = document.getElementById('responseContent').innerText;
            navigator.clipboard.writeText(content).then(() => {
                alert('Response copied to clipboard!');
            });
        }
    </script>
</body>

</html>
