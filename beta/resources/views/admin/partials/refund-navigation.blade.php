<!-- Refund Management Navigation Items -->

<!-- 1. Main Sidebar Navigation Item -->
<li class="nav-item {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRefunds"
       aria-expanded="{{ request()->routeIs('admin.refunds.*') ? 'true' : 'false' }}" aria-controls="collapseRefunds">
        <i class="fas fa-undo"></i>
        <span>Refund Management</span>
    </a>
    <div id="collapseRefunds" class="collapse {{ request()->routeIs('admin.refunds.*') ? 'show' : '' }}"
         aria-labelledby="headingRefunds" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Refund Options:</h6>
            <a class="collapse-item {{ request()->routeIs('admin.refunds.index') ? 'active' : '' }}" 
               href="{{ route('admin.refunds.index') }}">
                <i class="fas fa-list"></i> All Refunds
            </a>
            <a class="collapse-item" href="{{ route('admin.refunds.index') }}?status=pending">
                <i class="fas fa-clock"></i> Pending Refunds
                <span class="badge badge-warning badge-sm ml-1" id="pending-refunds-count">0</span>
            </a>
            <a class="collapse-item" href="{{ route('admin.refunds.index') }}?status=processed">
                <i class="fas fa-check"></i> Processed Refunds
            </a>
            <a class="collapse-item" href="{{ route('admin.refunds.index') }}?status=failed">
                <i class="fas fa-times"></i> Failed Refunds
            </a>
            <div class="collapse-divider"></div>
            <a class="collapse-item" href="{{ route('admin.refunds.export') }}">
                <i class="fas fa-download"></i> Export Refunds
            </a>
        </div>
    </div>
</li>

<!-- 2. Quick Access Button for Dashboard -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.refunds.index') }}" class="btn btn-warning btn-sm shadow-sm">
            <i class="fas fa-undo fa-sm text-white-50"></i> Manage Refunds
        </a>
        <a href="{{ route('admin.refunds.index') }}?status=pending" class="btn btn-danger btn-sm shadow-sm">
            <i class="fas fa-clock fa-sm text-white-50"></i> 
            Pending Refunds <span class="badge badge-light ml-1" id="pending-badge">0</span>
        </a>
    </div>
</div>

<!-- 3. Top Navigation Bar Item -->
<li class="nav-item dropdown no-arrow">
    <a class="nav-link dropdown-toggle" href="#" id="refundDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-undo fa-fw"></i>
        <span class="badge badge-danger badge-counter" id="refund-notification-count" style="display: none;">0</span>
    </a>
    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
         aria-labelledby="refundDropdown">
        <h6 class="dropdown-header">
            Refund Alerts
        </h6>
        <div id="refund-notifications">
            <!-- Refund notifications will be loaded here -->
        </div>
        <a class="dropdown-item text-center small text-gray-500" href="{{ route('admin.refunds.index') }}">
            View All Refunds
        </a>
    </div>
</li>

<!-- 4. Breadcrumb Navigation -->
@if(request()->routeIs('admin.refunds.*'))
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">
            @if(request()->routeIs('admin.refunds.index'))
                Refund Management
            @elseif(request()->routeIs('admin.refunds.show'))
                Refund Details
            @else
                Refunds
            @endif
        </li>
    </ol>
</nav>
@endif

<!-- 5. Dashboard Cards with Quick Links -->
<div class="row">
    <!-- Refunds Overview Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pending Refunds
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="dashboard-pending-refunds">
                            Loading...
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-warning stretched-link" href="{{ route('admin.refunds.index') }}?status=pending">
                    Process Refunds
                </a>
                <div class="small text-warning">
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Refunds Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Refunds (Month)
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="dashboard-total-refunds">
                            Loading...
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-undo fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-info stretched-link" href="{{ route('admin.refunds.index') }}">
                    View All Refunds
                </a>
                <div class="small text-info">
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 6. Quick Action Buttons -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div class="btn-group" role="group" aria-label="Refund Quick Actions">
        <a href="{{ route('admin.refunds.index') }}" class="btn btn-primary">
            <i class="fas fa-list"></i> All Refunds
        </a>
        <a href="{{ route('admin.refunds.index') }}?status=pending" class="btn btn-warning">
            <i class="fas fa-clock"></i> Pending
        </a>
        <a href="{{ route('admin.refunds.index') }}?type=full_refund" class="btn btn-success">
            <i class="fas fa-check-circle"></i> Full Refunds
        </a>
        <a href="{{ route('admin.refunds.index') }}?type=partial_refund" class="btn btn-info">
            <i class="fas fa-adjust"></i> Partial Refunds
        </a>
        <a href="{{ route('admin.refunds.export') }}" class="btn btn-secondary">
            <i class="fas fa-download"></i> Export
        </a>
    </div>
</div>

<script>
// Load refund counts for navigation badges
async function loadRefundCounts() {
    try {
        const response = await fetch('/api/admin/refunds/statistics/admin?period=month', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const stats = result.data;
            
            // Update navigation badges
            document.getElementById('pending-refunds-count').textContent = stats.pending_refunds;
            document.getElementById('pending-badge').textContent = stats.pending_refunds;
            document.getElementById('dashboard-pending-refunds').textContent = stats.pending_refunds;
            document.getElementById('dashboard-total-refunds').textContent = stats.total_refunds;
            
            // Show notification badge if there are pending refunds
            const notificationCount = document.getElementById('refund-notification-count');
            if (stats.pending_refunds > 0) {
                notificationCount.textContent = stats.pending_refunds;
                notificationCount.style.display = 'inline';
            } else {
                notificationCount.style.display = 'none';
            }
            
            // Load recent refund notifications
            loadRefundNotifications();
        }
    } catch (error) {
        console.error('Error loading refund counts:', error);
    }
}

// Load recent refund notifications
async function loadRefundNotifications() {
    try {
        const response = await fetch('/api/admin/refunds?status=pending&limit=5', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data.data.length > 0) {
            const notifications = document.getElementById('refund-notifications');
            notifications.innerHTML = result.data.data.map(refund => `
                <a class="dropdown-item d-flex align-items-center" href="/admin/refunds/${refund.id}">
                    <div class="mr-3">
                        <div class="icon-circle bg-warning">
                            <i class="fas fa-undo text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">${new Date(refund.created_at).toLocaleDateString()}</div>
                        <span class="font-weight-bold">₹${refund.refund_amount.toLocaleString()} refund pending</span>
                        <div class="small text-gray-500">${refund.user?.name || 'Customer'}</div>
                    </div>
                </a>
            `).join('');
        } else {
            document.getElementById('refund-notifications').innerHTML = `
                <div class="dropdown-item text-center small text-gray-500">
                    No pending refunds
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading refund notifications:', error);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRefundCounts();
    
    // Refresh every 2 minutes
    setInterval(loadRefundCounts, 120000);
});
</script>