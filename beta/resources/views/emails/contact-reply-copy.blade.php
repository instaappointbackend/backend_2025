<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copy: {{ $replySubject }}</title>
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
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
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
    </style>
</head>
<body>
    <div class="header">
        <h2>📧 Copy of Support Reply Sent</h2>
        <p><strong>Admin:</strong> {{ $adminName }}</p>
        <p><strong>Sent to:</strong> {{ $recipientName }} ({{ $recipientEmail }})</p>
        <p><strong>Date:</strong> {{ now()->format('M d, Y \a\t h:i A') }}</p>
    </div>

    <div class="content">
        <h3>Reply Sent:</h3>
        <p><strong>Subject:</strong> {{ $replySubject }}</p>
        <div style="white-space: pre-line; background-color: #f8f9fa; padding: 15px; border-radius: 5px;">{{ $replyMessage }}</div>
        
        <div class="original-message">
            <h4>Customer's Original Message:</h4>
            <p><strong>Ticket ID:</strong> #{{ $contact->id }}</p>
            <p><strong>Subject:</strong> {{ $contact->subject }}</p>
            <p><strong>Date:</strong> {{ $contact->created_at->format('M d, Y \a\t h:i A') }}</p>
            <div style="white-space: pre-line;">{{ $contact->message }}</div>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated copy of the support reply sent to the customer.</p>
        <p>{{ config('app.name') }} Admin Panel</p>
    </div>
</body>
</html>