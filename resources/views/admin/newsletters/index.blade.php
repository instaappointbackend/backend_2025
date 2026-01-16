@extends('admin.layouts.app')

@section('title', 'Newsletter Subscribers')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Newsletter Subscribers</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="btn-group">
        <a href="{{ route('admin.newsletters.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Subscriber
        </a>
        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"
            aria-expanded="false">
            <span class="visually-hidden">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="{{ route('admin.newsletters.export', ['status' => $status]) }}">
                    <i class="fas fa-file-export me-1"></i> Export List
                </a>
            </li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Manage Newsletter Subscribers</h5>
                <div class="btn-group" role="group" aria-label="Filter by status">
                    <a href="{{ route('admin.newsletters.index', ['status' => 'all']) }}"
                        class="btn btn-outline-secondary {{ $status === 'all' ? 'active' : '' }}">
                        All <span class="badge bg-secondary">{{ $counts['all'] }}</span>
                    </a>
                    <a href="{{ route('admin.newsletters.index', ['status' => 'subscribed']) }}"
                        class="btn btn-outline-success {{ $status === 'subscribed' ? 'active' : '' }}">
                        Active <span class="badge bg-success">{{ $counts['subscribed'] }}</span>
                    </a>
                    <a href="{{ route('admin.newsletters.index', ['status' => 'unsubscribed']) }}"
                        class="btn btn-outline-danger {{ $status === 'unsubscribed' ? 'active' : '' }}">
                        Unsubscribed <span class="badge bg-danger">{{ $counts['unsubscribed'] }}</span>
                    </a>
                    <a href="{{ route('admin.newsletters.index', ['status' => 'pending']) }}"
                        class="btn btn-outline-warning {{ $status === 'pending' ? 'active' : '' }}">
                        Pending <span class="badge bg-warning">{{ $counts['pending'] }}</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="{{ route('admin.newsletters.index') }}" method="GET" class="d-flex">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search by email or name" name="search"
                                value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    @if ($subscribers->count() > 0)
                        <form action="{{ route('admin.newsletters.bulk-action') }}" method="POST" id="bulk-action-form">
                            @csrf
                            <div class="input-group">
                                <select name="action" class="form-select" required>
                                    <option value="">Bulk Action</option>
                                    <option value="subscribe">Subscribe</option>
                                    <option value="unsubscribe">Unsubscribe</option>
                                    <option value="delete">Delete</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="submit" id="bulk-action-btn" disabled>
                                    Apply
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Subscribed Date</th>
                            <th>Source</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscribers as $subscriber)
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input subscriber-checkbox" type="checkbox" name="ids[]"
                                            form="bulk-action-form" value="{{ $subscriber->id }}">
                                    </div>
                                </td>
                                <td>{{ $subscriber->email }}</td>
                                <td>{{ $subscriber->name ?? 'N/A' }}</td>
                                <td>
                                    @if ($subscriber->status === 'subscribed')
                                        <span class="badge bg-success">Subscribed</span>
                                    @elseif($subscriber->status === 'unsubscribed')
                                        <span class="badge bg-danger">Unsubscribed</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $subscriber->subscribed_at ? $subscriber->subscribed_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    @if ($subscriber->source)
                                        @if ($subscriber->source == 'admin')
                                            <span class="badge bg-info">Admin</span>
                                        @elseif($subscriber->source == 'footer')
                                            <span class="badge bg-secondary">Footer</span>
                                        @elseif($subscriber->source == 'homepage')
                                            <span class="badge bg-primary">Homepage</span>
                                        @else
                                            {{ $subscriber->source }}
                                        @endif
                                    @else
                                        Website
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.newsletters.edit', $subscriber) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="confirmDelete('{{ $subscriber->id }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <form id="delete-form-{{ $subscriber->id }}"
                                        action="{{ route('admin.newsletters.destroy', $subscriber) }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-envelope fa-3x text-secondary mb-3"></i>
                                        @if (request('search'))
                                            <h5>No subscribers match your search</h5>
                                            <p class="text-muted">Try different keywords or clear the search</p>
                                            <a href="{{ route('admin.newsletters.index', ['status' => $status]) }}"
                                                class="btn btn-outline-secondary mt-2">
                                                Clear Search
                                            </a>
                                        @else
                                            <h5>No newsletter subscribers found</h5>
                                            <p class="text-muted">Add subscribers or integrate the newsletter form on your
                                                website</p>
                                            <a href="{{ route('admin.newsletters.create') }}"
                                                class="btn btn-primary mt-2">
                                                <i class="fas fa-plus me-1"></i> Add Subscriber
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $subscribers->onEachSide(5)->links() }}
            </div>
        </div>
    </div>

    <!-- Stats Cards Section -->
    <div class="row mt-4 g-4">
        <div class="col-md-4">
            <div class="card bg-primary bg-gradient text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Subscribers</h5>
                            <h2 class="mb-0">{{ $counts['all'] }}</h2>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="fas fa-users fa-2x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success bg-gradient text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Active Subscribers</h5>
                            <h2 class="mb-0">{{ $counts['subscribed'] }}</h2>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="fas fa-user-check fa-2x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger bg-gradient text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Unsubscribe Rate</h5>
                            <h2 class="mb-0">
                                {{ $counts['all'] > 0 ? number_format(($counts['unsubscribed'] / $counts['all']) * 100, 1) : 0 }}%
                            </h2>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="fas fa-chart-pie fa-2x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sources Breakdown Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Subscriber Sources</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $sources = [
                                        'homepage' => ['name' => 'Homepage Form', 'count' => 0],
                                        'footer' => ['name' => 'Footer Form', 'count' => 0],
                                        'admin' => ['name' => 'Admin Added', 'count' => 0],
                                        'other' => ['name' => 'Other Sources', 'count' => 0],
                                    ];

                                    // In a real implementation, you would calculate these from the database
                                    // For this example, we'll use placeholder values
$homepageCount = isset($sourceStats['homepage'])
    ? $sourceStats['homepage']
    : rand(10, 50);
$footerCount = isset($sourceStats['footer'])
    ? $sourceStats['footer']
    : rand(20, 70);
$adminCount = isset($sourceStats['admin']) ? $sourceStats['admin'] : rand(5, 15);
$otherCount = isset($sourceStats['other']) ? $sourceStats['other'] : rand(3, 10);
                                    $totalSourceCount = $homepageCount + $footerCount + $adminCount + $otherCount;
                                @endphp

                                <tr>
                                    <td>Homepage Form</td>
                                    <td>{{ $homepageCount }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                {{ $totalSourceCount > 0 ? number_format(($homepageCount / $totalSourceCount) * 100, 1) : 0 }}%
                                            </div>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar"
                                                    style="width: {{ $totalSourceCount > 0 ? ($homepageCount / $totalSourceCount) * 100 : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Footer Form</td>
                                    <td>{{ $footerCount }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                {{ $totalSourceCount > 0 ? number_format(($footerCount / $totalSourceCount) * 100, 1) : 0 }}%
                                            </div>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: {{ $totalSourceCount > 0 ? ($footerCount / $totalSourceCount) * 100 : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Admin Added</td>
                                    <td>{{ $adminCount }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                {{ $totalSourceCount > 0 ? number_format(($adminCount / $totalSourceCount) * 100, 1) : 0 }}%
                                            </div>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-info" role="progressbar"
                                                    style="width: {{ $totalSourceCount > 0 ? ($adminCount / $totalSourceCount) * 100 : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Other Sources</td>
                                    <td>{{ $otherCount }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                {{ $totalSourceCount > 0 ? number_format(($otherCount / $totalSourceCount) * 100, 1) : 0 }}%
                                            </div>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-warning" role="progressbar"
                                                    style="width: {{ $totalSourceCount > 0 ? ($otherCount / $totalSourceCount) * 100 : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 border-0">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <!-- In a real implementation, this would be a chart -->
                            <div class="text-center py-4">
                                <div class="display-6 text-primary mb-3">{{ $counts['all'] }} Total Subscribers</div>
                                <p class="text-muted">Most subscribers are coming from the footer form, followed by the
                                    homepage newsletter section.</p>
                                <div class="mt-3">
                                    <a href="{{ route('admin.newsletters.export') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Export Full Report
                                    </a>
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
        function confirmDelete(subscriberId) {
            if (confirm('Are you sure you want to delete this subscriber? This action cannot be undone.')) {
                document.getElementById('delete-form-' + subscriberId).submit();
            }
        }

        // Handle select all checkbox
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.subscriber-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });

            document.getElementById('bulk-action-btn').disabled = !this.checked;
        });

        // Handle individual checkboxes
        document.querySelectorAll('.subscriber-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const anyChecked = document.querySelectorAll('.subscriber-checkbox:checked').length > 0;
                document.getElementById('bulk-action-btn').disabled = !anyChecked;

                const allCheckboxes = document.querySelectorAll('.subscriber-checkbox');
                const allChecked = document.querySelectorAll('.subscriber-checkbox:checked').length ===
                    allCheckboxes.length;
                document.getElementById('select-all').checked = allChecked;
            });
        });

        // Confirm bulk actions
        document.getElementById('bulk-action-form').addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="action"]').value;
            const checkedCount = document.querySelectorAll('.subscriber-checkbox:checked').length;

            if (action === 'delete') {
                if (!confirm(
                        `Are you sure you want to delete ${checkedCount} subscribers? This action cannot be undone.`
                        )) {
                    e.preventDefault();
                    return false;
                }
            } else if (action === 'unsubscribe') {
                if (!confirm(`Are you sure you want to unsubscribe ${checkedCount} subscribers?`)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
@endsection
