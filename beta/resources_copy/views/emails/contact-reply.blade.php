<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $replySubject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .original-message {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-top: 20px;
        }
        .footer {
            font-size: 12px;
            color: #6c757d;
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }} - Support Reply</h2>
        <p>Hello {{ $recipientName }},</p>
        <p>Thank you for contacting us. We have received your message and here is our response:</p>
    </div>

    <div class="content">
        <h3>{{ $replySubject }}</h3>
        <div style="white-space: pre-line;">{{ $replyMessage }}</div>
        
        <div class="original-message">
            <h4>Your Original Message:</h4>
            <p><strong>Subject:</strong> {{ $contact->subject }}</p>
            <p><strong>Date:</strong> {{ $contact->created_at->format('M d, Y \a\t h:i A') }}</p>
            <div style="white-space: pre-line;">{{ $contact->message }}</div>
        </div>
    </div>

    <div class="footer">
        <p>Best regards,<br>
        {{ $adminName }}<br>
        {{ config('app.name') }} Support Team</p>
        
        <hr style="margin: 20px 0;">
        
        <p>This email was sent in response to your support ticket #{{ $contact->id }}.</p>
        <p>If you have any further questions, please don't hesitate to contact us.</p>
        
        @if(config('app.url'))
        <p><a href="{{ config('app.url') }}" class="btn">Visit Our Website</a></p>
        @endif
    </div>
</body>
</html>