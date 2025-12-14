@extends('admin.layouts.app')

@section('title', 'Reply to Support Ticket')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Reply to Support Ticket #{{ $contact->id }}</h3>
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Tickets
                    </a>
                </div>

                <div class="card-body">
                    <!-- Original Message -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="mb-0">Original Message</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>From:</strong> {{ $contact->getSenderName() }}</p>
                                    <p><strong>Email:</strong> {{ $contact->getSenderEmail() }}</p>
                                    <p><strong>Subject:</strong> {{ $contact->subject }}</p>
                                    <p><strong>Date:</strong> {{ $contact->created_at->format('M d, Y \a\t h:i A') }}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge badge-{{ $contact->status === 'unread' ? 'warning' : ($contact->status === 'replied' ? 'success' : 'info') }}">
                                            {{ ucfirst($contact->status) }}
                                        </span>
                                    </p>
                                    <hr>
                                    <p><strong>Message:</strong></p>
                                    <div class="border p-3 bg-white" style="white-space: pre-line;">{{ $contact->message }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Reply Form -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Send Reply</h5>
                                </div>
                                <div class="card-body">
                                    @if(session('error'))
                                        <div class="alert alert-danger">
                                            {{ session('error') }}
                                        </div>
                                    @endif

                                    <form action="{{ route('admin.contacts.send-reply', $contact) }}" method="POST">
                                        @csrf
                                        
                                        <div class="form-group">
                                            <label for="reply_subject">Reply Subject <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control @error('reply_subject') is-invalid @enderror" 
                                                   id="reply_subject" 
                                                   name="reply_subject" 
                                                   value="{{ old('reply_subject', 'Re: ' . $contact->subject) }}" 
                                                   required>
                                            @error('reply_subject')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="reply_message">Reply Message <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error('reply_message') is-invalid @enderror" 
                                                      id="reply_message" 
                                                      name="reply_message" 
                                                      rows="10" 
                                                      placeholder="Type your reply here..."
                                                      required>{{ old('reply_message', "Dear " . $contact->getSenderName() . ",\n\nThank you for contacting us.\n\n\n\nBest regards,\n" . (auth()->user()->name ?? 'Support Team')) }}</textarea>
                                            @error('reply_message')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       class="form-check-input" 
                                                       id="send_copy_to_admin" 
                                                       name="send_copy_to_admin" 
                                                       value="1"
                                                       {{ old('send_copy_to_admin') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="send_copy_to_admin">
                                                    Send a copy to my email
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i> Send Reply
                                            </button>
                                            <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary ml-2">
                                                Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-resize textarea
    $('#reply_message').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Confirm before sending
    $('form').on('submit', function(e) {
        if (!confirm('Are you sure you want to send this reply?')) {
            e.preventDefault();
        }
    });
});
</script>
@endsection