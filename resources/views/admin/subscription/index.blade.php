@extends('admin.layouts.app')

@section('title', 'Subscription')


@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Subscription</li>
        </ol>
    </nav>
@endsection

{{-- @section('page-actions')
    <a href="{{ route('admin.web-blogs.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Create Blog Post
    </a>
@endsection --}}

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Subscription</h5>

            <div class="search-filter">
                <form action="{{ route('admin.web-blogs.index') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..."
                            value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published
                        </option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>

                    <select name="user_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Authors</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="10%">Sr. No</th>
                            <th width="15%">Vendor Name</th>
                            <th width="15%">Plan Name</th>
                            <th width="20%">Transaction Id</th>
                            <th width="10%">Payment Status</th>
                            <th width="15%">Start At</th>
                            <th width="15%">Expire At</th>
                            <th width="15%">Created At</th>
                            <th width="20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse($users as $index=>$user)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $user->user->name ?? 'N/A' }}
                                </td>

                                <td>{{ $user->plan_name ?? '-' }}</td>
                                <td>{{ $user->transaction_id }}</td>
                                <td>{{ $user->payment_status ?? '-' }}</td>

                                <td>{{ $user->starts_at ?? '-' }}</td>
                                <td>{{ $user->expires_at ?? '-' }}</td>
                                <td>{{ $user->created_at ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No blog posts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }}
                    users
                </div>
                <div class="d-flex justify-content-end mt-3">
                    {{ $users->onEachSide(5)->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

@endsection
