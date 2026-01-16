@extends('admin.layouts.app')

@section('title', 'Support Tickets')

@section('styles')
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stats-card:hover::before {
            opacity: 1;
        }

        .stats-card.bg-info {
            background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
        }

        .stats-card.bg-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-card.bg-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stats-card.bg-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stats-card.bg-secondary {
            background: linear-gradient(135deg, #f2f2f2 0%, #d4d4d4 100%);
            color: #333;
        }

        .stats-card.bg-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: 0.3;
        }

        .enhanced-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .enhanced-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .enhanced-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
            border: none;
        }

        .enhanced-card-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.4rem;
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }

        .filter-form .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .filter-form .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .filter-form .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-form .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .filter-form .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .ticket-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .ticket-table thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .ticket-table tbody tr {
            transition: all 0.3s ease;
            border: none;
        }

        .ticket-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .ticket-table tbody td {
            padding: 15px;
            border-top: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        .ticket-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-buttons .btn {
            margin: 2px;
            border-radius: 6px;
            padding: 6px 10px;
            transition: all 0.3s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .bulk-actions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #e9ecef;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-details {
            flex: 1;
        }

        .ticket-priority-indicator {
            width: 4px;
            height: 30px;
            border-radius: 2px;
            margin-right: 10px;
        }

        .subject-cell {
            max-width: 300px;
        }

        .date-info {
            min-width: 120px;
        }

        .pagination-info {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .badge-lg {
            font-size: 0.9rem;
            padding: 8px 12px;
        }

        .bg-light-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }

        .ticket-badge {
            display: inline-flex;
            align-items: center;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-primary {
            background-color: #007bff;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        .empty-icon {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .form-label {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #495057;
        }

        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }

            .filter-section .row>div {
                margin-bottom: 15px;
            }

            .action-buttons .btn {
                margin: 1px;
                padding: 4px 8px;
            }

            .user-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-avatar {
                margin-bottom: 5px;
            }
        }

        /* Tooltip styling */
        .tooltip {
            font-size: 0.8rem;
        }

        /* Custom scrollbar for table */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-headset text-primary mr-2"></i>
                            Support Tickets
                        </h1>
                        <p class="text-muted mb-0">Manage customer support requests and inquiries</p>
                    </div>
                    <div>
                        <span class="badge badge-info badge-lg">{{ $contacts->total() }} Total Tickets</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stats-card bg-info">
                    <div class="stats-number">{{ $counts['all'] }}</div>
                    <div class="stats-label">Total Tickets</div>
                    <div class="stats-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stats-card bg-warning">
                    <div class="stats-number">{{ $counts['unread'] }}</div>
                    <div class="stats-label">Unread</div>
                    <div class="stats-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stats-card bg-success">
                    <div class="stats-number">{{ $counts['replied'] }}</div>
                    <div class="stats-label">Replied</div>
                    <div class="stats-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stats-card bg-primary">
                    <div class="stats-number">{{ $counts['users'] }}</div>
                    <div class="stats-label">From Users</div>
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stats-card bg-secondary">
                    <div class="stats-number">{{ $counts['guests'] }}</div>
                    <div class="stats-label">From Guests</div>
                    <div class="stats-icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stats-card bg-danger">
                    <div class="stats-number">{{ $counts['spam'] }}</div>
                    <div class="stats-label">Spam</div>
                    <div class="stats-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <form method="GET" action="{{ route('admin.contacts.index') }}" class="filter-form">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="form-label font-weight-bold">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
                            <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                            <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>Replied
                            </option>
                            <option value="spam" {{ request('status') === 'spam' ? 'selected' : '' }}>Spam</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-weight-bold">Source</label>
                        <select name="source" class="form-control">
                            <option value="">All Sources</option>
                            <option value="users" {{ request('source') === 'users' ? 'selected' : '' }}>Registered Users
                            </option>
                            <option value="guests" {{ request('source') === 'guests' ? 'selected' : '' }}>Guests</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Search</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by name, email, subject..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search mr-1"></i> Filter
                        </button>
                        <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Content Card -->
        <div class="row">
            <div class="col-12">
                <div class="enhanced-card">
                    <div class="enhanced-card-header">
                        <h3>
                            <i class="fas fa-list mr-2"></i>
                            Support Tickets List
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        @if ($contacts->count() > 0)
                            <div class="table-responsive">
                                <table class="table ticket-table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="select-all">
                                                    <label class="custom-control-label" for="select-all"></label>
                                                </div>
                                            </th>
                                            <th>
                                                <i class="fas fa-hashtag mr-1"></i>
                                                Ticket #
                                            </th>
                                            <th>
                                                <i class="fas fa-user mr-1"></i>
                                                From
                                            </th>
                                            <th>
                                                <i class="fas fa-envelope mr-1"></i>
                                                Subject
                                            </th>
                                            <th>
                                                <i class="fas fa-flag mr-1"></i>
                                                Status
                                            </th>
                                            <th>
                                                <i class="fas fa-source mr-1"></i>
                                                Source
                                            </th>
                                            <th>
                                                <i class="fas fa-calendar mr-1"></i>
                                                Date
                                            </th>
                                            <th width="200">
                                                <i class="fas fa-cogs mr-1"></i>
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($contacts as $contact)
                                            <tr class="{{ $contact->status === 'unread' ? 'bg-light-warning' : '' }}">
                                                <td>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox"
                                                            class="custom-control-input contact-checkbox"
                                                            id="contact-{{ $contact->id }}"
                                                            value="{{ $contact->id }}">
                                                        <label class="custom-control-label"
                                                            for="contact-{{ $contact->id }}"></label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div
                                                            class="ticket-priority-indicator bg-{{ $contact->status === 'unread' ? 'warning' : ($contact->status === 'replied' ? 'success' : 'info') }}">
                                                        </div>
                                                        <strong class="text-primary">#{{ $contact->id }}</strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                        </div>
                                                        <div class="user-details ml-2">
                                                            <div class="font-weight-bold">{{ $contact->getSenderName() }}
                                                            </div>
                                                            <small
                                                                class="text-muted">{{ $contact->getSenderEmail() }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="subject-cell">
                                                        <a href="{{ route('admin.contacts.show', $contact) }}"
                                                            class="text-decoration-none text-dark font-weight-medium">
                                                            {{ Str::limit($contact->subject, 45) }}
                                                        </a>
                                                        @if ($contact->status === 'unread')
                                                            <span class="badge badge-warning badge-pill ml-2">
                                                                <i class="fas fa-star fa-xs"></i> New
                                                            </span>
                                                        @endif
                                                        <div class="text-muted small mt-1">
                                                            {{ Str::limit($contact->message, 60) }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="ticket-badge badge-{{ $contact->status === 'unread' ? 'warning' : ($contact->status === 'replied' ? 'success' : ($contact->status === 'spam' ? 'danger' : 'info')) }}">
                                                        @if ($contact->status === 'unread')
                                                            <i class="fas fa-envelope mr-1"></i>
                                                        @elseif($contact->status === 'replied')
                                                            <i class="fas fa-reply mr-1"></i>
                                                        @elseif($contact->status === 'spam')
                                                            <i class="fas fa-ban mr-1"></i>
                                                        @else
                                                            <i class="fas fa-eye mr-1"></i>
                                                        @endif
                                                        {{ ucfirst($contact->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($contact->isFromAuthenticatedUser())
                                                        <span class="badge badge-primary badge-pill">
                                                            <i class="fas fa-user-check mr-1"></i>
                                                            User
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary badge-pill">
                                                            <i class="fas fa-user mr-1"></i>
                                                            Guest
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="date-info">
                                                        <div class="font-weight-medium">
                                                            {{ $contact->created_at->format('M d, Y') }}</div>
                                                        <small
                                                            class="text-muted">{{ $contact->created_at->format('h:i A') }}</small>
                                                        <div class="text-muted small">
                                                            {{ $contact->created_at->diffForHumans() }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="{{ route('admin.contacts.show', $contact) }}"
                                                            class="btn btn-outline-info btn-sm" title="View Details"
                                                            data-toggle="tooltip">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('admin.contacts.reply', $contact) }}"
                                                            class="btn btn-outline-primary btn-sm" title="Send Reply"
                                                            data-toggle="tooltip">
                                                            <i class="fas fa-reply"></i>
                                                        </a>
                                                        @if ($contact->status !== 'replied')
                                                            <form method="POST"
                                                                action="{{ route('admin.contacts.update-status', $contact) }}"
                                                                style="display: inline;">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="replied">
                                                                <button type="submit"
                                                                    class="btn btn-outline-success btn-sm"
                                                                    title="Mark as Replied" data-toggle="tooltip">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <form method="POST"
                                                            action="{{ route('admin.contacts.destroy', $contact) }}"
                                                            style="display: inline;"
                                                            onsubmit="return confirm('Are you sure you want to delete this ticket?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                title="Delete Ticket" data-toggle="tooltip">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Enhanced Bulk Actions -->
                            <div class="bulk-actions">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <form method="POST" action="{{ route('admin.contacts.bulk-action') }}"
                                            id="bulk-action-form" class="d-flex align-items-center">
                                            @csrf
                                            <label class="font-weight-bold mr-3">Bulk Actions:</label>
                                            <select name="action" class="form-control mr-3" style="width: auto;"
                                                required>
                                                <option value="">Choose Action</option>
                                                <option value="mark_read">
                                                    <i class="fas fa-eye"></i> Mark as Read
                                                </option>
                                                <option value="mark_unread">Mark as Unread</option>
                                                <option value="mark_replied">Mark as Replied</option>
                                                <option value="mark_spam">Mark as Spam</option>
                                                <option value="delete">Delete Selected</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-play mr-1"></i> Apply
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <div class="pagination-info mb-2">
                                            Showing {{ $contacts->firstItem() ?? 0 }} to {{ $contacts->lastItem() ?? 0 }}
                                            of {{ $contacts->total() }} tickets
                                        </div>
                                        {{ $contacts->onEachSide(5)->links() }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h4 class="mt-3">No Support Tickets Found</h4>
                                <p class="text-muted">There are no support tickets matching your current filters.</p>
                                <a href="{{ route('admin.contacts.index') }}" class="btn btn-primary mt-3">
                                    <i class="fas fa-refresh mr-1"></i> Clear Filters
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Select all checkbox
            $('#select-all').change(function() {
                $('.contact-checkbox').prop('checked', $(this).prop('checked'));
            });

            // Bulk action form
            $('#bulk-action-form').submit(function(e) {
                var selectedIds = $('.contact-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one ticket.');
                    return false;
                }

                // Add selected IDs to form
                selectedIds.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'ids[]',
                        value: id
                    }).appendTo('#bulk-action-form');
                });

                var action = $('select[name="action"]').val();
                if (action === 'delete') {
                    if (!confirm(
                            'Are you sure you want to delete the selected tickets? This action cannot be undone.'
                            )) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
    </script>
@endsection
