<?php
if (!function_exists('adminSidebarLinkClasses')) {
    function adminSidebarLinkClasses(bool $isActive): string {
        $base = 'flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon transition-colors sidebar-link';
        return $isActive
            ? $base . ' text-maroon bg-red-50 border-r-4 border-maroon font-semibold'
            : $base;
    }
}

$activeSidebar = $activeSidebar ?? '';
?>
<aside id="sidebar" class="fixed left-0 top-0 h-screen bg-white shadow-lg border-r border-gray-200 transition-all duration-300 z-40 overflow-y-auto w-64">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-maroon sidebar-text">BudgetTrack</h2>
            <p class="text-sm text-gray-600 sidebar-text">Administration Panel</p>
        </div>
        <button id="sidebarToggle" type="button" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Toggle sidebar">
            <svg class="w-5 h-5 text-gray-600 sidebar-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5l-7 7 7 7M20 5l-7 7 7 7"></path>
            </svg>
        </button>
    </div>

    <nav class="mt-6 space-y-1">
        <a href="admin_dashboard.php" class="<?php echo adminSidebarLinkClasses($activeSidebar === 'dashboard'); ?>">
            <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
            </svg>
            <span class="sidebar-text ml-3">Dashboard</span>
        </a>

        <div class="relative" id="budgetDropdown">
            <button type="button" class="<?php echo adminSidebarLinkClasses(in_array($activeSidebar, ['allocations', 'cabac', 'utilization', 'utilization_view_admin', 'lib', 'ppmp', 'ppmp_view'], true)); ?> justify-between" data-dropdown-toggle="budgetWorkflowMenu">
                <div class="flex items-center">
                <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                    <span class="sidebar-text ml-3">Budget Workflow</span>
                </div>
                <svg class="w-4 h-4 text-gray-500 sidebar-dropdown-arrow transition-transform duration-200 <?php echo $budgetMenuOpen ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <?php $budgetMenuOpen = in_array($activeSidebar, ['allocations', 'cabac', 'utilization', 'utilization_view_admin', 'lib', 'ppmp', 'ppmp_view'], true); ?>
            <div id="budgetWorkflowMenu" class="ml-6 mt-1 space-y-1 <?php echo $budgetMenuOpen ? '' : 'hidden'; ?>">
                <a href="allocations.php" class="flex items-center px-6 py-2 text-sm <?php echo $activeSidebar === 'allocations' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                    <span class="sidebar-text ml-3">Allocations</span>
                </a>
                <a href="cabac.php" class="flex items-center px-6 py-2 text-sm <?php echo $activeSidebar === 'cabac' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                    <span class="sidebar-text ml-3">CABAC</span>
                </a>
                <a href="utilization.php" class="flex items-center px-6 py-2 text-sm <?php echo $activeSidebar === 'utilization' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                    <span class="sidebar-text ml-3">Utilization</span>
                </a>
                <a href="utilization_view_admin.php" class="flex items-center px-6 py-2 text-sm <?php echo $activeSidebar === 'utilization_view_admin' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                    <span class="sidebar-text ml-3">Utilization View</span>
                </a>
                <a href="lib.php" class="flex items-center px-6 py-2 text-sm <?php echo $activeSidebar === 'lib' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                    <span class="sidebar-text ml-3">LIB</span>
                </a>
                <a href="ppmp.php" class="flex items-center px-6 py-2 text-sm <?php echo $activeSidebar === 'ppmp' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                    <span class="sidebar-text ml-3">PPMP</span>
                </a>
                <a href="ppmp_view.php" class="flex items-center px-6 py-2 text-sm <?php echo $activeSidebar === 'ppmp_view' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                    <span class="sidebar-text ml-3">PPMP & LIB View</span>
                </a>
            </div>
        </div>

        <a href="file_submission.php" class="<?php echo adminSidebarLinkClasses($activeSidebar === 'file_submission'); ?>">
                <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            <span class="sidebar-text ml-3">File Submission</span>
                </a>

        <a href="submit_documents.php" class="<?php echo adminSidebarLinkClasses($activeSidebar === 'upload'); ?>">
            <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
            </svg>
            <span class="sidebar-text ml-3">Upload</span>
        </a>

        <a href="admin_pr_submission.php" class="<?php echo adminSidebarLinkClasses($activeSidebar === 'pr_submission'); ?>">
            <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6l2 5H7l2-5zM7 10l1 4h8l1-4m-4 5v4m-4-4v4"></path>
            </svg>
            <span class="sidebar-text ml-3">PR Submission</span>
        </a>
        <a href="notifications_admin.php" class="<?php echo adminSidebarLinkClasses($activeSidebar === 'notifications'); ?>">
            <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 102.828 2.828l6.414 6.414a2 2 0 01-2.828 2.828L4.828 7z"></path>
            </svg>
            <span class="sidebar-text ml-3">Notifications</span>
        </a>
        <a href="admin_reports.php" class="hidden <?php echo adminSidebarLinkClasses($activeSidebar === 'reports'); ?>">
            <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17h6a1 1 0 001-1v-5a1 1 0 00-1-1h-4V5h-4v10H6a1 1 0 00-1 1v1a1 1 0 001 1h5z"></path>
            </svg>
            <span class="sidebar-text ml-3">Reports</span>
        </a>

            </nav>
</aside>

<style>
:root {
    --sidebar-expanded-width: 256px;
    --sidebar-collapsed-width: 80px;
}

[data-main-content] {
    margin-left: var(--sidebar-expanded-width);
    transition: margin-left 0.3s ease;
}

#sidebar {
    width: var(--sidebar-expanded-width);
}

body.sidebar-collapsed [data-main-content] {
    margin-left: var(--sidebar-collapsed-width);
}

body.sidebar-collapsed #sidebar {
    width: var(--sidebar-collapsed-width);
}

body.sidebar-collapsed #sidebar .sidebar-text {
    display: none;
}

body.sidebar-collapsed #sidebar .sidebar-icon {
    margin-right: 0;
}

body.sidebar-collapsed #sidebar .sidebar-toggle-icon {
    transform: rotate(180deg);
}

@media (max-width: 1024px) {
    [data-main-content] {
        margin-left: var(--sidebar-expanded-width);
    }

    body.sidebar-collapsed [data-main-content] {
        margin-left: var(--sidebar-collapsed-width);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const body = document.body;
    const storageKey = 'adminSidebarCollapsed';

    function applyState(collapsed) {
        if (collapsed) {
            body.classList.add('sidebar-collapsed');
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    }

    const initialState = localStorage.getItem(storageKey) === 'true';
    applyState(initialState);

    toggleBtn?.addEventListener('click', function() {
        const collapsed = !body.classList.contains('sidebar-collapsed');
        applyState(collapsed);
        localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
    });

    const budgetToggle = document.querySelector('[data-dropdown-toggle="budgetWorkflowMenu"]');
    const budgetMenu = document.getElementById('budgetWorkflowMenu');
    if (budgetToggle && budgetMenu) {
        budgetToggle.addEventListener('click', function(event) {
            event.preventDefault();
            const willOpen = budgetMenu.classList.contains('hidden');
            budgetMenu.classList.toggle('hidden');
            const arrow = budgetToggle.querySelector('.sidebar-dropdown-arrow');
            if (arrow) {
                arrow.classList.toggle('rotate-180', willOpen);
            }
        });
    }
});
</script>


