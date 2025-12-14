
<!-- resources/views/admin/layouts/navbar.blade.php -->
<nav class="navbar">
    <div class="container-fluid">
        <div class="navbar-start">
            <button id="mobile-sidebar-toggle" class="navbar-toggler d-md-none">
                <i class="fas fa-bars"></i>
            </button>
            <div class="breadcrumb">
                @yield('breadcrumb')
            </div>
        </div>

        <div class="navbar-end">
            <!-- Notifications Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownNotif" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    @php
                    // Get real notification count - you can customize this query
                    $notificationCount = \App\Models\User::where('is_kyc_completed', false)
                    ->where('is_kyc_uploaded', true)
                    ->where('role', 'vendor')
                    ->count();

                    // Add other notification types
                    $recentAppointments = \App\Models\Appointment::where('created_at', '>=', now()->subDay())->count();
                    $totalNotifications = $notificationCount + $recentAppointments;
                    @endphp
                    @if($totalNotifications > 0)
                    <span class="badge bg-danger rounded-pill">{{ $totalNotifications }}</span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="navbarDropdownNotif">
                    <li><h6 class="dropdown-header">Notifications ({{ $totalNotifications }})</h6></li>
                    <li><hr class="dropdown-divider"></li>

                    <!-- KYC Pending Notifications -->
                    @if($notificationCount > 0)
                    <li>
                        <a class="dropdown-item notification-clickable" href="{{ route('admin.kyc.pending') }}">
                            <div class="notification-item">
                                <div class="notification-icon bg-warning">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <div class="notification-details">
                                    <p class="mb-0">{{ $notificationCount }} KYC verification{{ $notificationCount > 1 ? 's' : '' }} pending</p>
                                    <small class="text-muted">Click to review</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    @endif

                    <!-- Recent Appointments -->
                    @if($recentAppointments > 0)
                    <li>
                        <a class="dropdown-item notification-clickable" href="{{ route('admin.appointments.index') }}?recent=today">
                            <div class="notification-item">
                                <div class="notification-icon bg-success">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="notification-details">
                                    <p class="mb-0">{{ $recentAppointments }} new appointment{{ $recentAppointments > 1 ? 's' : '' }} today</p>
                                    <small class="text-muted">Click to view</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    @endif

                    <!-- Recent User Registrations -->
                    @php
                    $recentUsers = \App\Models\User::where('created_at', '>=', now()->subDay())
                    ->where('role', '!=', 'admin')
                    ->count();
                    @endphp
                    @if($recentUsers > 0)
                    <li>
                        <a class="dropdown-item notification-clickable" href="{{ route('admin.users.index') }}?recent=today">
                            <div class="notification-item">
                                <div class="notification-icon bg-primary">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="notification-details">
                                    <p class="mb-0">{{ $recentUsers }} new user{{ $recentUsers > 1 ? 's' : '' }} registered</p>
                                    <small class="text-muted">Click to view</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    @endif

                    <!-- Pending Payouts (if user has permission) -->
                    @if(hasPermission('payouts_view_payouts'))
                    @php
                    $pendingPayouts = \App\Models\PayoutRequest::where('status', 'pending')->count() ?? 0;
                    @endphp
                    @if($pendingPayouts > 0)
                    <li>
                        <a class="dropdown-item notification-clickable" href="{{ route('admin.payouts.pending') }}">
                            <div class="notification-item">
                                <div class="notification-icon bg-info">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="notification-details">
                                    <p class="mb-0">{{ $pendingPayouts }} payout{{ $pendingPayouts > 1 ? 's' : '' }} pending</p>
                                    <small class="text-muted">Click to process</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    @endif
                    @endif

                    <!-- If no notifications -->
                    @if($totalNotifications == 0)
                    <li>
                        <div class="dropdown-item-text text-center py-3">
                            <i class="fas fa-bell-slash text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No new notifications</p>
                        </div>
                    </li>
                    @endif

                    <li><hr class="dropdown-divider"></li>

                    <!-- View All Notifications -->
                    <li>
                        <a class="dropdown-item text-center text-primary fw-bold" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-eye me-1"></i> View Dashboard
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User Profile Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ Auth::user()->profile_picture ? asset('storage/' . Auth::user()->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="Admin" class="avatar-img">
                    <span class="d-none d-md-inline ms-1">{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                    <li><h6 class="dropdown-header">Admin Settings</h6></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.users.show',1) }}">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
