<!-- Add these cards to your admin dashboard -->
<div class="row">
    <!-- Pending Refunds Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 refund-card" data-toggle="tooltip" title="Click to view pending refunds">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pending Refunds
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="dashboard-pending-refunds">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <div class="small text-gray-500 mt-1">
                            Amount: <span id="dashboard-pending-amount">₹0</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-warning stretched-link" href="{{ route('admin.refunds.index') }}?status=pending">
                    Process Now
                </a>
                <div class="small text-warning">
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Refunds This Month -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 refund-card" data-toggle="tooltip" title="Click to view all refunds">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Refunds This Month
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="dashboard-total-refunds">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <div class="small text-gray-500 mt-1">
                            Amount: <span id="dashboard-total-amount">₹0</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-undo fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-info stretched-link" href="{{ route('admin.refunds.index') }}">
                    View Details
                </a>
                <div class="small text-info">
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Success Rate -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 refund-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Success Rate
                        </div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="dashboard-success-rate">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: 0%" aria-valuenow="0" 
                                         aria-valuemin="0" aria-valuemax="100" id="success-rate-progress">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-success stretched-link" href="{{ route('admin.refunds.index') }}?status=processed">
                    View Processed
                </a>
                <div class="small text-success">
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Failed Refunds -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2 refund-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Failed Refunds
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="dashboard-failed-refunds">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <div class="small text-gray-500 mt-1">
                            Amount: <span id="dashboard-failed-amount">₹0</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-danger stretched-link" href="{{ route('admin.refunds.index') }}?status=failed">
                    Review Failed
                </a>
                <div class="small text-danger">
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Buttons Row -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Refund Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="btn-group btn-group-sm" role="group" aria-label="Refund Quick Actions">
                    <a href="{{ route('admin.refunds.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> All Refunds
                    </a>
                    <a href="{{ route('admin.refunds.index') }}?status=pending" class="btn btn-outline-warning">
                        <i class="fas fa-clock"></i> Process Pending
                    </a>
                    <a href="{{ route('admin.refunds.index') }}?type=full_refund" class="btn btn-outline-success">
                        <i class="fas fa-check-circle"></i> Full Refunds
                    </a>
                    <a href="{{ route('admin.refunds.index') }}?type=partial_refund" class="btn btn-outline-info">
                        <i class="fas fa-adjust"></i> Partial Refunds
                    </a>
                    <a href="{{ route('admin.refunds.export') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load dashboard refund statistics
async function loadDashboardRefundStats() {
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
            
            // Update dashboard cards
            document.getElementById('dashboard-pending-refunds').textContent = stats.pending_refunds || 0;
            document.getElementById('dashboard-pending-amount').textContent = '₹' + (stats.pending_refunds_amount || 0).toLocaleString();
            
            document.getElementById('dashboard-total-refunds').textContent = stats.total_refunds || 0;
            document.getElementById('dashboard-total-amount').textContent = '₹' + (stats.total_refund_amount || 0).toLocaleString();
            
            document.getElementById('dashboard-failed-refunds').textContent = stats.failed_refunds || 0;
            document.getElementById('dashboard-failed-amount').textContent = '₹' + (stats.failed_refunds_amount || 0).toLocaleString();
            
            // Update success rate
            const successRate = stats.success_rate || 0;
            document.getElementById('dashboard-success-rate').textContent = successRate + '%';
            document.getElementById('success-rate-progress').style.width = successRate + '%';
            document.getElementById('success-rate-progress').setAttribute('aria-valuenow', successRate);
            
            // Update sidebar badge
            if (document.getElementById('sidebar-pending-count')) {
                document.getElementById('sidebar-pending-count').textContent = stats.pending_refunds || 0;
            }
            
            // Add visual indicators for high pending refunds
            const pendingCard = document.querySelector('.card.border-left-warning');
            if (stats.pending_refunds > 10) {
                pendingCard.classList.add('border-left-danger');
                pendingCard.classList.remove('border-left-warning');
            }
            
        }
    } catch (error) {
        console.error('Error loading dashboard refund stats:', error);
        // Show error state
        document.getElementById('dashboard-pending-refunds').textContent = 'Error';
        document.getElementById('dashboard-total-refunds').textContent = 'Error';
        document.getElementById('dashboard-failed-refunds').textContent = 'Error';
        document.getElementById('dashboard-success-rate').textContent = 'Error';
    }
}

// Initialize dashboard stats
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardRefundStats();
    
    // Refresh every 3 minutes
    setInterval(loadDashboardRefundStats, 180000);
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<style>
.refund-card {
    cursor: pointer;
    transition: transform 0.2s;
}

.refund-card:hover {
    transform: translateY(-2px);
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>