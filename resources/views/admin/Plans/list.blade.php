@extends('admin.layouts.app')

@section('title', 'Plans')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Plans</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Create Plan
    </a>
@endsection

@section('content')
    <style>
        .main-content,
        .content-wrapper {
            overflow-x: auto !important;
        }

        .plans-table {
            min-width: 1400px;
            /* force width */
        }

        .plans-table th,
        .plans-table td {
            white-space: nowrap;
        }

        .plans-table .col-description {
            white-space: normal !important;
            min-width: 250px;
            /* gives space to expand */
            max-width: 400px;
            /* prevents too wide */
        }
    </style>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Plans List</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover plans-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Disc. Price</th>
                            <th>Duration</th>
                            <th>Highlight</th>
                            <th>Badge</th>
                            <th class="col-description">Tagline</th>
                            <th>Button Text</th>
                            {{-- <th>Button Class</th> --}}
                            {{-- <th>Border Class</th> --}}
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plans as $index => $plan)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $plan->id }}</td>
                                <td>{{ $plan->type }}</td>
                                <td><strong>{{ $plan->title }}</strong></td>
                                <td>₹{{ number_format($plan->original_price, 2) }}</td>
                                <td>₹{{ number_format($plan->discounted_price, 2) }}</td>
                                <td>{{ $plan->duration }}</td>
                                <td>{{ $plan->highlight }}</td>
                                <td><span class="badge bg-primary">{{ $plan->badge }}</span></td>
                                <td class="col-description">
                                    <span class="short-text">
                                        {{ \Illuminate\Support\Str::limit($plan->tagline, 25) }}
                                    </span>
                                    @if (strlen($plan->tagline) > 60)
                                        <span class="full-text d-none">
                                            {{ $plan->tagline }}
                                        </span>
                                        <a href="javascript:void(0);" class="toggle-text text-primary ms-1">
                                            Read more
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $plan->button_text }}</td>
                                {{-- <td><code>{{ $plan->button_class }}</code></td> --}}
                                {{-- <td><code>{{ $plan->border_class }}</code></td> --}}
                                <td>{{ $plan->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.plans.show', $plan->id) }}"
                                            class="btn btn-sm btn-info text-white">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.plans.edit', $plan->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-text').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    let parent = this.closest('td');
                    let shortText = parent.querySelector('.short-text');
                    let fullText = parent.querySelector('.full-text');

                    if (fullText.classList.contains('d-none')) {
                        fullText.classList.remove('d-none');
                        shortText.classList.add('d-none');
                        this.innerText = 'Read less';
                    } else {
                        fullText.classList.add('d-none');
                        shortText.classList.remove('d-none');
                        this.innerText = 'Read more';
                    }
                });
            });
        });
    </script>
@endsection
