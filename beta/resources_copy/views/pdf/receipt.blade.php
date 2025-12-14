<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 12px;
            line-height: 1.5;
        }
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
        }
        .receipt-header {
            background-color: #4361ee;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
        }
        .receipt-subheader {
            background-color: #3a56d4;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
            text-align: center;
        }
        .receipt-content {
            padding: 15px;
        }
        .business-info {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .business-logo {
            max-width: 150px;
            max-height: 60px;
            margin: 0 auto;
            display: block;
        }
        .business-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .business-address, .business-contact {
            font-size: 12px;
            color: #666;
            margin: 2px 0;
        }
        .receipt-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .receipt-details-left, .receipt-details-right {
            width: 48%;
        }
        .section {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #4361ee;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .label {
            font-weight: 500;
            color: #555;
        }
        .value {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            font-size: 14px;
            padding-top: 8px;
            border-top: 2px solid #eee;
            margin-top: 8px;
        }
        .status {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            color: white;
            background-color: #ccc;
        }
        .status.paid {
            background-color: #4cd964;
        }
        .status.pending {
            background-color: #ff9500;
        }
        .status.cancelled {
            background-color: #ff3b30;
        }
        .status.refunded {
            background-color: #007aff;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 11px;
            color: #888;
            border-top: 1px solid #eee;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        .discount {
            color: #4cd964;
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="receipt-header">
        <h1>Payment Receipt</h1>
    </div>
    <div class="receipt-subheader">
        Receipt #{{ $receipt_number }}
    </div>

    <div class="receipt-content">
        <div class="business-info">
            @if(isset($business['logo']) && $business['logo'])
            <img src="{{ $business['logo'] }}" class="business-logo" alt="Business Logo">
            @endif
            <div class="business-name">{{ $business['name'] ?? 'Business Name' }}</div>
            @if(isset($business['address']) && $business['address'])
            <div class="business-address">{{ $business['address'] }}</div>
            @endif
            @if(isset($business['phone']) && $business['phone'])
            <div class="business-contact">Phone: {{ $business['phone'] }}</div>
            @endif
            @if(isset($business['email']) && $business['email'])
            <div class="business-contact">Email: {{ $business['email'] }}</div>
            @endif
        </div>

        <div class="receipt-details clearfix">
            <div class="receipt-details-left">
                <div class="section-title">Receipt Information</div>
                <div class="row">
                    <span class="label">Receipt Date:</span>
                    <span class="value">{{ $receipt_date }}</span>
                </div>
                <div class="row">
                    <span class="label">Transaction ID:</span>
                    <span class="value">{{ $payment['transaction_id'] ?? 'N/A' }}</span>
                </div>
                <div class="row">
                    <span class="label">Payment Method:</span>
                    <span class="value">{{ ucfirst($payment['method'] ?? 'N/A') }}</span>
                </div>
                <div class="row">
                    <span class="label">Payment Status:</span>
                    <span class="value">
                            <span class="status {{ strtolower($payment['status'] ?? 'pending') }}">
                                {{ ucfirst($payment['status'] ?? 'Pending') }}
                            </span>
                        </span>
                </div>
            </div>

            <div class="receipt-details-right">
                <div class="section-title">Client Information</div>
                <div class="row">
                    <span class="label">Name:</span>
                    <span class="value">{{ $client->name ?? 'N/A' }}</span>
                </div>
                <div class="row">
                    <span class="label">Email:</span>
                    <span class="value">{{ $client->email ?? 'N/A' }}</span>
                </div>
                @if(isset($client->phone) && $client->phone)
                <div class="row">
                    <span class="label">Phone:</span>
                    <span class="value">{{ $client->phone }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">Appointment Details</div>
            <div class="row">
                <span class="label">Service:</span>
                <span class="value">{{ $service['name'] ?? 'Service' }}</span>
            </div>
            <div class="row">
                <span class="label">Date:</span>
                <span class="value">{{ $appointment_date }}</span>
            </div>
            <div class="row">
                <span class="label">Time:</span>
                <span class="value">{{ $appointment_time }}</span>
            </div>
            @if($appointment->visit_type)
            <div class="row">
                <span class="label">Visit Type:</span>
                <span class="value">
                        {{ $appointment->visit_type === 'home' ? 'Home Visit' :
                           ($appointment->visit_type === 'online' ? 'Video Call' : 'Office Visit') }}
                    </span>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">Payment Breakdown</div>

            <div class="row">
                <span class="label">Service Price:</span>
                <span class="value">₹{{ number_format($payment['original_price'] ?? 0, 2) }}</span>
            </div>

            @if(isset($payment['home_visit_fee']) && $payment['home_visit_fee'] > 0)
            <div class="row">
                <span class="label">Home Visit Fee:</span>
                <span class="value">₹{{ number_format($payment['home_visit_fee'], 2) }}</span>
            </div>
            @endif

            @if(isset($payment['discount_amount']) && $payment['discount_amount'] > 0)
            <div class="row">
                <span class="label">Discount:</span>
                <span class="value discount">-₹{{ number_format($payment['discount_amount'], 2) }}</span>
            </div>

            @if(isset($payment['coupon_code']) && $payment['coupon_code'])
            <div class="row">
                <span class="label">Coupon Applied:</span>
                <span class="value">{{ $payment['coupon_code'] }}</span>
            </div>
            @endif

            @if(isset($payment['offer_title']) && $payment['offer_title'])
            <div class="row">
                <span class="label">Offer Applied:</span>
                <span class="value">{{ $payment['offer_title'] }}</span>
            </div>
            @endif
            @endif

            <div class="row">
                <span class="label">Base Price:</span>
                <span class="value">₹{{ number_format($payment['booking_price'] ?? 0, 2) }}</span>
            </div>

            @if(isset($payment['platform_fee']) && $payment['platform_fee'] > 0)
            <div class="row">
                <span class="label">Platform Fee:</span>
                <span class="value">₹{{ number_format($payment['platform_fee'], 2) }}</span>
            </div>
            @endif

            @if(isset($payment['other_charges']) && $payment['other_charges'] > 0)
            <div class="row">
                <span class="label">Other Charges:</span>
                <span class="value">₹{{ number_format($payment['other_charges'], 2) }}</span>
            </div>
            @endif

            @if(isset($payment['gst_amount']) && $payment['gst_amount'] > 0)
            <div class="row">
                <span class="label">GST:</span>
                <span class="value">₹{{ number_format($payment['gst_amount'], 2) }}</span>
            </div>
            @endif

            <div class="row total-row">
                <span class="label">Total Amount:</span>
                <span class="value">{{ $payment['formatted_total'] }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a computer-generated receipt and does not require a signature.</p>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</div>
</body>
</html>
