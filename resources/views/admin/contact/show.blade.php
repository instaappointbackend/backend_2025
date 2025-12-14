@extends('admin.layouts.app')

@section('title', 'Support Ticket #' . $contact->id)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Support Ticket #{{ $contact->id }}</h3>
                    <div>
                        <a href="{{ route('admin.contacts.reply', $contact) }}" class="btn btn-primary">
                            <i class="fas fa-reply"></i> Reply
                        </a>
                        <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Ticket Details -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Ticket Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Subject:</strong></div>
                                        <div class="col-sm-9">{{ $contact->subject }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>From:</strong></div>
                                        <div class="col-sm-9">
                                            {{ $contact->getSenderName() }}
                                            @if($contact->isFromAuthenticatedUser())
                                                <span class="badge badge-primary ml-2">Registered User</span>
                                            @else
                                                <span class="badge badge-secondary ml-2">Guest</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Email:</strong></div>
                                        <div class="col-sm-9">
                                            <a href="mailto:{{ $contact->getSenderEmail() }}">{{ $contact->getSenderEmail() }}</a>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Date:</strong></div>
                                        <div class="col-sm-9">{{ $contact->created_at->format('M d, Y \a\t h:i A') }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Status:</strong></div>
                                        <div class="col-sm-9">
                                            <span class="badge badge-{{ $contact->status === 'unread' ? 'warning' : ($contact->status === 'replied' ? 'success' : ($contact->status === 'spam' ? 'danger' : 'info')) }}">
                                                {{ ucfirst($contact->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($contact->read_at)
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Read At:</strong></div>
                                            <div class="col-sm-9">{{ $contact->read_at->format('M d, Y \a\t h:i A') }}</div>
                                        </div>
                                    @endif
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-3"><strong>Message:</strong></div>
                                        <div class="col-sm-9">
                                            <div class="border p-3 bg-light" style="white-space: pre-line;">{{ $contact->message }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Quick Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('admin.contacts.reply', $contact) }}" class="btn btn-primary btn-block mb-2">
                                            <i class="fas fa-reply"></i> Send Reply
                                        </a>
                                        
                                        @if($contact->status !== 'replied')
                                            <form method="POST" action="{{ route('admin.contacts.update-status', $contact) }}" class="mb-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="replied">
                                                <button type="submit" class="btn btn-success btn-block">
                                                    <i class="fas fa-check"></i> Mark as Replied
                                                </button>
                                            </form>
                                        @endif

                                        @if($contact->status !== 'read')
                                            <form method="POST" action="{{ route('admin.contacts.update-status', $contact) }}" class="mb-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="read">
                                                <button type="submit" class="btn btn-info btn-block">
                                                    <i class="fas fa-eye"></i> Mark as Read
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.contacts.update-status', $contact) }}" class="mb-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="spam">
                                            <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('Mark this ticket as spam?')">
                                                <i class="fas fa-ban"></i> Mark as Spam
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" class="mb-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to delete this ticket?')">
                                                <i class="fas fa-trash"></i> Delete Ticket
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket Information -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Additional Information</h5>
                                </div>
                                <div class="card-body">
                                    @if($contact->ip_address)
                                        <p><strong>IP Address:</strong><br>{{ $contact->ip_address }}</p>
                                    @endif
                                    @if($contact->user_agent)
                                        <p><strong>User Agent:</strong><br><small>{{ $contact->user_agent }}</small></p>
                                    @endif
                                    @if($contact->isFromAuthenticatedUser() && $contact->user)
                                        <p><strong>User Details:</strong><br>
                                        ID: {{ $contact->user->id }}<br>
                                        Role: {{ ucfirst($contact->user->role) }}<br>
                                        Mobile: {{ $contact->user->mobile ?? 'N/A' }}<br>
                                        Joined: {{ $contact->user->created_at->format('M d, Y') }}</p>
                                    @endif
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