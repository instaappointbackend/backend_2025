<!-- Add this to your admin sidebar navigation -->
<li class="nav-item {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRefunds"
       aria-expanded="{{ request()->routeIs('admin.refunds.*') ? 'true' : 'false' }}" aria-controls="collapseRefunds">
        <i class="fas fa-undo"></i>
        <span>Refunds</span>
    </a>
    <div id="collapseRefunds" class="collapse {{ request()->routeIs('admin.refunds.*') ? 'show' : '' }}"
         aria-labelledby="headingRefunds" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Refund Management:</h6>
            <a class="collapse-item {{ request()->routeIs('admin.refunds.index') && !request()->has('status') ? 'active' : '' }}" 
               href="{{ route('admin.refunds.index') }}">
                <i class="fas fa-list fa-sm"></i> All Refunds
            </a>
            <a class="collapse-item {{ request()->get('status') == 'pending' ? 'active' : '' }}" 
               href="{{ route('admin.refunds.index') }}?status=pending">
                <i class="fas fa-clock fa-sm"></i> Pending 
                <span class="badge badge-warning badge-sm ml-1" id="sidebar-pending-count">0</span>
            </a>
            <a class="collapse-item {{ request()->get('status') == 'processed' ? 'active' : '' }}" 
               href="{{ route('admin.refunds.index') }}?status=processed">
                <i class="fas fa-check fa-sm"></i> Processed
            </a>
            <a class="collapse-item {{ request()->get('status') == 'failed' ? 'active' : '' }}" 
               href="{{ route('admin.refunds.index') }}?status=failed">
                <i class="fas fa-times fa-sm"></i> Failed
            </a>
            <div class="collapse-divider"></div>
            <a class="collapse-item" href="{{ route('admin.refunds.export') }}">
                <i class="fas fa-download fa-sm"></i> Export Data
            </a>
        </div>
    </div>
</li>