<!-- Refund Statistics Widget for Admin Dashboard -->
<div class="col-xl-4 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        Refunds This Month
                    </div>
                    <div class="row no-gutters align-items-center">
                        <div class="col-auto">
                            <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="refund-count">
                                Loading...
                            </div>
                        </div>
                        <div class="col">
                            <div class="progress progress-sm mr-2">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: 0%" aria-valuenow="0" 
                                     aria-valuemin="0" aria-valuemax="100" id="refund-progress">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="small text-gray-500 mt-1">
                        Total Amount: <span id="refund-amount">₹0</span>
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

<script>
// Load refund statistics for dashboard widget
async function loadRefundWidget() {
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
            
            // Update widget values
            document.getElementById('refund-count').textContent = stats.total_refunds;
            document.getElementById('refund-amount').textContent = '₹' + stats.total_refund_amount.toLocaleString();
            
            // Calculate progress percentage (example: based on target of 100 refunds)
            const progressPercentage = Math.min((stats.total_refunds / 100) * 100, 100);
            const progressBar = document.getElementById('refund-progress');
            progressBar.style.width = progressPercentage + '%';
            progressBar.setAttribute('aria-valuenow', progressPercentage);
            
            // Add color coding based on refund volume
            if (stats.total_refunds > 50) {
                progressBar.className = 'progress-bar bg-danger';
            } else if (stats.total_refunds > 20) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-info';
            }
        }
    } catch (error) {
        console.error('Error loading refund widget:', error);
        document.getElementById('refund-count').textContent = 'Error';
        document.getElementById('refund-amount').textContent = 'Error';
    }
}

// Load widget data when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadRefundWidget();
    
    // Refresh every 5 minutes
    setInterval(loadRefundWidget, 300000);
});
</script>