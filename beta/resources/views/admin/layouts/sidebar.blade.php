<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo" align="center" style="max-width:150px;margin:auto">
            <img src="{{ asset('admin/images/dark_logo.png') }}" style="width:150px;margin:auto" alt="Admin">
        </div>
        <button id="sidebar-toggle" class="sidebar-toggler d-md-none">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-image">
            <img src="{{ Auth::user()->profile_picture ? asset('storage/' . Auth::user()->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="Admin">
        </div>
        <div class="user-info">
            <h5>{{ Auth::user()->name }}</h5>
            <p>{{ Auth::user()->userRole ? Auth::user()->userRole->display_name : 'Administrator' }}</p>
        </div>
    </div>

    <ul class="sidebar-menu">
        @if(hasPermission('dashboard_view_dashboard'))
        <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        @endif

        <!-- Appointments Management -->
        @if(hasPermission('appointments_view_appointments'))
        <li class="menu-item {{ request()->routeIs('admin.appointments*') ? 'active' : '' }}">
            <a href="{{ route('admin.appointments.index') }}">
                <i class="fas fa-calendar-check"></i>
                <span>Appointments</span>
            </a>
        </li>
        @endif

        <!-- Financial Management -->
        @if(hasPermission('payments_view_payments') || hasPermission('payouts_view_payouts'))
        <li class="menu-header">Financial Management</li>
        @endif

        @if(hasPermission('payments_view_payments'))
        <li class="menu-item {{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
            <a href="{{ route('admin.payments.index') }}">
                <i class="fas fa-inr"></i>
                <span>Payments</span>
            </a>
        </li>
        @endif

        @if(hasPermission('payouts_view_payouts'))
        <li class="menu-item {{ request()->routeIs('admin.payouts*') ? 'active' : '' }}">
            <a href="{{ route('admin.payouts.index') }}">
                <i class="fas fa-wallet"></i>
                <span>Payouts</span>
            </a>
        </li>
        @endif

        @if(hasPermission('refunds_view_refunds'))
        <li class="menu-item {{ request()->routeIs('admin.refunds*') ? 'active' : '' }}">
            <a href="{{ route('admin.refunds.index') }}">
                <i class="fas fa-undo-alt"></i>
                <span>Refunds</span>
            </a>
        </li>
        @endif

        <!-- User Management -->
        @if(hasPermission('users_view_users') || hasPermission('kyc_view_kyc_submissions'))
        <li class="menu-header">User Management</li>
        @endif

        <!-- System Users with submenu -->
        @if(hasPermission('users_view_users'))
        <li class="menu-item has-submenu {{ (request()->routeIs('admin.users*') || request()->routeIs('admin.vendors*') || request()->routeIs('admin.customers*')) ? 'active open' : '' }}">
            <a href="#" class="submenu-toggle">
                <i class="fas fa-users"></i>
                <span>Users</span>
                <i class="submenu-indicator fas fa-chevron-right"></i>
            </a>
            <ul class="submenu">
                <li class="{{ request()->routeIs('admin.vendors*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.vendors') }}">
                        <i class="fas fa-store"></i>
                        <span>Vendors</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('admin.customers*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.customers') }}">
                        <i class="fas fa-user-friends"></i>
                        <span>Customers</span>
                    </a>
                </li>
                @if(hasPermission('users_manage_user_roles'))
                <li class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}">
                        <i class="fas fa-user-cog"></i>
                        <span>All Users</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if(hasPermission('kyc_view_kyc_submissions'))
        <li class="menu-item {{ request()->routeIs('admin.kyc*') ? 'active' : '' }}">
            <a href="{{ route('admin.kyc.index') }}">
                <i class="fas fa-id-card"></i>
                <span>KYC Verification</span>
                @php
                $pendingKyc = \App\Models\User::where('is_kyc_completed', false)->where('is_kyc_uploaded', true)->where('role', 'vendor')->count();
                @endphp
                @if($pendingKyc > 0)
                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingKyc }}</span>
                @endif
            </a>
        </li>
        @endif

        <!-- Access Control with submenu -->
        @if(hasPermission('roles_view_roles') || hasPermission('permissions_view_permissions'))
        <li class="menu-header">Access Control</li>
        <li class="menu-item has-submenu {{ request()->routeIs('admin.roles*') || request()->routeIs('admin.permissions*') ? 'active open' : '' }}">
            <a href="#" class="submenu-toggle">
                <i class="fas fa-shield-alt"></i>
                <span>Access Control</span>
                <i class="submenu-indicator fas fa-chevron-right"></i>
            </a>
            <ul class="submenu">
                @if(hasPermission('roles_view_roles'))
                <li class="{{ request()->routeIs('admin.roles.index') || request()->routeIs('admin.roles.show') || request()->routeIs('admin.roles.edit') ? 'active' : '' }}">
                    <a href="{{ route('admin.roles.index') }}">
                        <i class="fas fa-user-tag"></i>
                        <span>Manage Roles</span>
                    </a>
                </li>
                @endif

                @if(hasPermission('roles_create_roles'))
                <li class="{{ request()->routeIs('admin.roles.create') ? 'active' : '' }}">
                    <a href="{{ route('admin.roles.create') }}">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create Role</span>
                    </a>
                </li>
                @endif

               <!-- @if(hasPermission('permissions_view_permissions'))
                <li class="{{ request()->routeIs('admin.permissions.index') || request()->routeIs('admin.permissions.show') || request()->routeIs('admin.permissions.edit') ? 'active' : '' }}">
                    <a href="{{ route('admin.permissions.index') }}">
                        <i class="fas fa-key"></i>
                        <span>Manage Permissions</span>
                    </a>
                </li>
                @endif

                @if(hasPermission('permissions_create_permissions'))
                <li class="{{ request()->routeIs('admin.permissions.create') ? 'active' : '' }}">
                    <a href="{{ route('admin.permissions.create') }}">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create Permission</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('admin.permissions.bulk-create') ? 'active' : '' }}">
                    <a href="{{ route('admin.permissions.bulk-create') }}">
                        <i class="fas fa-upload"></i>
                        <span>Bulk Create Permissions</span>
                    </a>
                </li>
                @endif-->
            </ul>
        </li>
        @endif

        <!-- Business Management -->
        @if(hasPermission('services_view_services') || hasPermission('business_categories_view'))
        <li class="menu-header">Business Management</li>
        @endif

        @if(hasPermission('business_categories_view'))
        <li class="menu-item {{ request()->routeIs('admin.business-categories*') ? 'active' : '' }}">
            <a href="{{ route('admin.business-categories.index') }}">
                <i class="fas fa-briefcase"></i>
                <span>Business Categories</span>
            </a>
        </li>
        @endif

        @if(hasPermission('services_view_services'))
        <li class="menu-item {{ request()->routeIs('admin.services*') ? 'active' : '' }}">
            <a href="{{ route('admin.services.index') }}">
                <i class="fas fa-concierge-bell"></i>
                <span>Services</span>
            </a>
        </li>
        @endif

        @if(hasPermission('offers_view_offers'))
        <li class="menu-item {{ request()->routeIs('admin.offers*') ? 'active' : '' }}">
            <a href="{{ route('admin.offers.index') }}">
                <i class="fas fa-percent"></i>
                <span>Admin Offers</span>
            </a>
        </li>
        @endif

        <!-- Content Management -->
        @if(hasPermission('content_manage_blogs') || hasPermission('content_manage_faqs') || hasPermission('content_manage_pages'))
        <li class="menu-header">Content Management</li>
        @endif

        @if(hasPermission('content_manage_blogs'))
        <li class="menu-item {{ request()->routeIs('admin.blogs*') ? 'active' : '' }}">
            <a href="{{ route('admin.blogs.index') }}">
                <i class="fas fa-blog"></i>
                <span>Blog Posts</span>
            </a>
        </li>
        @endif

        @if(hasPermission('content_manage_faqs'))
        <li class="menu-item {{ request()->routeIs('admin.faqs*') ? 'active' : '' }}">
            <a href="{{ route('admin.faqs.index') }}">
                <i class="fas fa-question-circle"></i>
                <span>FAQs</span>
            </a>
        </li>
        @endif

        @if(hasPermission('content_manage_pages'))
        <li class="menu-item {{ request()->routeIs('admin.pages*') ? 'active' : '' }}">
            <a href="{{ route('admin.pages.index') }}">
                <i class="fas fa-file-alt"></i>
                <span>Pages</span>
            </a>
        </li>
        @endif

        <!-- Reports -->
        @if(hasPermission('reports_view_reports'))
        <li class="menu-header">Reports</li>
        <li class="menu-item {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
            <a href="{{ route('admin.reports.index') }}">
                <i class="fas fa-chart-bar"></i>
                <span>Reports Dashboard</span>
            </a>
        </li>

        <!-- Reports with submenu -->
        <li class="menu-item has-submenu {{ (request()->routeIs('admin.reports.appointments') || request()->routeIs('admin.reports.users') || request()->routeIs('admin.reports.revenue') || request()->routeIs('admin.reports.payouts')) && !request()->routeIs('admin.reports.index') ? 'active open' : '' }}">
            <a href="#" class="submenu-toggle">
                <i class="fas fa-chart-line"></i>
                <span>Detailed Reports</span>
                <i class="submenu-indicator fas fa-chevron-right"></i>
            </a>
            <ul class="submenu">
                <li class="{{ request()->routeIs('admin.reports.appointments') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.appointments') }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('admin.reports.users') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.users') }}">
                        <i class="fas fa-users-cog"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('admin.reports.revenue') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.revenue') }}">
                        <i class="fas fa-rupee-sign"></i>
                        <span>Revenue</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('admin.reports.payouts') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.payouts') }}">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Payouts</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif

        <!-- Support & Settings -->
        <li class="menu-header">System</li>

        @if(hasPermission('contacts_view_contacts'))
        <li class="menu-item {{ request()->routeIs('admin.contacts*') ? 'active' : '' }}">
            <a href="{{ route('admin.contacts.index') }}">
                <i class="fas fa-ticket-alt"></i>
                <span>Contact Support</span>
            </a>
        </li>
        @endif

        @if(hasPermission('newsletters_view_newsletters'))
        <li class="menu-item {{ request()->routeIs('admin.newsletters*') ? 'active' : '' }}">
            <a href="{{ route('admin.newsletters.index') }}">
                <i class="fas fa-envelope-open-text"></i>
                <span>Newsletter</span>
            </a>
        </li>
        @endif

        @if(hasPermission('settings_view_settings'))
        <li class="menu-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
            <a href="{{ route('admin.settings.index') }}">
                <i class="fas fa-cogs"></i>
                <span>Settings</span>
            </a>
        </li>
        @endif
    </ul>
</div>
