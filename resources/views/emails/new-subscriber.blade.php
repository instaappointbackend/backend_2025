@component('mail::message')
# New Newsletter Subscriber

A new user has subscribed to your newsletter!

**Email:** {{ $subscriber->email }}  
**Name:** {{ $subscriber->name ?? 'Not provided' }}  
**Subscribed On:** {{ $subscribedAt }}  
**Source:** {{ $source }}  
**IP Address:** {{ $ipAddress }}  

You can manage all subscribers in your admin dashboard.

@component('mail::button', ['url' => url('/admin/newsletters')])
View All Subscribers
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent