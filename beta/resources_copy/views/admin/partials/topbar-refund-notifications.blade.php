<!-- Add this to your admin topbar/navbar for refund notifications -->
<li class="nav-item dropdown no-arrow mx-1">
    <a class="nav-link dropdown-toggle" href="#" id="refundDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-undo fa-fw"></i>
        <!-- Counter - Refunds -->
        <span class="badge badge-danger badge-counter" id="refund-notification-badge" style="display: none;">0</span>
    </a>
    <!-- Dropdown - Refunds -->
    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
         aria-labelledby="refundDropdown">
        <h6 class="dropdown-header">
            <i class="fas fa-undo"></i>
            Refund Center
        </h6>
        
        <!-- Refund Notifications -->
        <div id="refund-notifications-list">
            <!-- Loading state -->
            <div class="dropdown-item d-flex align-items-center" id="refund-loading">
                <div class="mr-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div>
                    <span class="small">Loading refund notifications...</span>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="dropdown-item bg-light">
            <div class="row text-center">
                <div class="col-4">
                    <div class="small text-muted">Pending</div>
                    <div class="font-weight-bold text-warning" id="topbar-pending-count">0</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted">Today</div>
                    <div class="font-weight-bold text-info" id="topbar-today-count">0</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted">Failed</div>
                    <div class="font-weight-bold text-danger" id="topbar-failed-count">0</div>
                </div>
            </div>
        </div>
        
        <!-- Footer Links -->
        <div class="dropdown-item bg-light">
            <div class="row">
                <div class="col-6">
                    <a class="btn btn-sm btn-outline-primary btn-block" href="{{ route('admin.refunds.index') }}?status=pending">
                        <i class="fas fa-clock fa-sm"></i> Process
                    </a>
                </div>
                <div class="col-6">
                    <a class="btn btn-sm btn-outline-secondary btn-block" href="{{ route('admin.refunds.index') }}">
                        <i class="fas fa-list fa-sm"></i> View All
                    </a>
                </div>
            </div>
        </div>
    </div>
</li>

<script>
// Load refund notifications for topbar
async function loadTopbarRefundNotifications() {
    try {
        // Load recent pending refunds
        const response = await fetch('/api/admin/refunds?status=pending&limit=5', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        const notificationsList = document.getElementById('refund-notifications-list');
        const badge = document.getElementById('refund-notification-badge');
        
        if (result.success && result.data.data.length > 0) {
            const refunds = result.data.data;
            
            // Update badge
            badge.textContent = refunds.length;
            badge.style.display = 'inline';
            
            // Build notifications HTML
            notificationsList.innerHTML = refunds.map(refund => `
                <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.refunds.index') }}/${refund.id}">
                    <div class="mr-3">
                        <div class="icon-circle bg-warning">
                            <i class="fas fa-undo text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">${formatTimeAgo(refund.created_at)}</div>
                        <span class="font-weight-bold">₹${refund.refund_amount.toLocaleString()} refund pending</span>
                        <div class="small text-gray-500">
                            ${refund.user?.name || 'Customer'} • ${refund.refund_reference}
                        </div>
                    </div>
                </a>
            `).join('');
            
        } else {
            // No pending refunds
            badge.style.display = 'none';
            notificationsList.innerHTML = `
                <div class="dropdown-item text-center">
                    <div class="text-gray-500">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <div>No pending refunds</div>
                        <small>All caught up!</small>
                    </div>
                </div>
            `;
        }
        
        // Load additional stats
        loadTopbarRefundStats();
        
    } catch (error) {
        console.error('Error loading topbar refund notifications:', error);
        document.getElementById('refund-notifications-list').innerHTML = `
            <div class="dropdown-item text-center text-danger">
                <i class="fas fa-exclamation-triangle"></i>
                Error loading notifications
            </div>
        `;
    }
}

// Load refund stats for topbar
async function loadTopbarRefundStats() {
    try {
        const response = await fetch('/api/admin/refunds/statistics/admin?period=day', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const stats = result.data;
            document.getElementById('topbar-pending-count').textContent = stats.pending_refunds || 0;
            document.getElementById('topbar-today-count').textContent = stats.total_refunds || 0;
            document.getElementById('topbar-failed-count').textContent = stats.failed_refunds || 0;
        }
    } catch (error) {
        console.error('Error loading topbar refund stats:', error);
    }
}

// Format time ago helper
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return `${Math.floor(diffInMinutes / 1440)}d ago`;
}

// Initialize topbar refund notifications
document.addEventListener('DOMContentLoaded', function() {
    loadTopbarRefundNotifications();
    
    // Refresh every 2 minutes
    setInterval(loadTopbarRefundNotifications, 120000);
    
    // Add click handler for dropdown
    document.getElementById('refundDropdown').addEventListener('click', function() {
        loadTopbarRefundNotifications();
    });
});
</script>

<style>
.icon-circle {
    height: 2rem;
    width: 2rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dropdown-list {
    min-width: 20rem;
}

.badge-counter {
    font-size: 0.7rem;
    position: absolute;
    top: -2px;
    right: -6px;
}
</style>