@component('mail::message')
# New Contact Form Submission

You have received a new message from your website's contact form.

**From:** {{ $name }} ({{ $email }})  
**Subject:** {{ $subject }}  
**Date:** {{ now()->format('F j, Y, g:i a') }}

## Message:
{{ $messageText }}

---

## Additional Information:
- **IP Address:** {{ $ipAddress }}
- **Browser:** {{ $userAgent }}

@component('mail::button', ['url' => url('/admin/contacts')])
View All Messages
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent