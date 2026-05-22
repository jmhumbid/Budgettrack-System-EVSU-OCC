<!-- Standard Procurement Sidebar -->
<nav class="mt-6">
    <a href="proc_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'proc_dashboard.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
        </svg>
        <span class="sidebar-text ml-3">Dashboard</span>
    </a>
    <a href="allocations_view.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'allocations_view.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
        </svg>
        <span class="sidebar-text ml-3">Allocation</span>
    </a>
    
    <!-- Utilization Dropdown -->
    <div class="relative" id="utilizationDropdown">
        <button type="button" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon w-full justify-between <?php echo in_array(basename($_SERVER['PHP_SELF']), ['utilization__view.php', 'lib.php', 'ppmp.php']) ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>" data-dropdown-toggle="utilizationMenu">
            <div class="flex items-center">
                <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"></path>
                </svg>
                <span class="sidebar-text ml-3">Utilization</span>
            </div>
            <svg class="w-4 h-4 text-gray-500 sidebar-dropdown-arrow transition-transform duration-200 <?php echo in_array(basename($_SERVER['PHP_SELF']), ['utilization__view.php', 'lib.php', 'ppmp.php']) ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <?php $utilizationMenuOpen = in_array(basename($_SERVER['PHP_SELF']), ['utilization__view.php', 'lib.php', 'ppmp.php']); ?>
        <div id="utilizationMenu" class="ml-6 mt-1 space-y-1 <?php echo $utilizationMenuOpen ? '' : 'hidden'; ?>">
            <a href="utilization__view.php" class="flex items-center px-6 py-2 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'utilization__view.php' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                <span class="sidebar-text ml-3">Utilization</span>
            </a>
            <a href="lib.php" class="flex items-center px-6 py-2 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'lib.php' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                <span class="sidebar-text ml-3">LIB</span>
            </a>
            <a href="ppmp.php" class="flex items-center px-6 py-2 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'ppmp.php' ? 'text-maroon bg-red-50 border-r-4 border-maroon font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-maroon'; ?>">
                <span class="sidebar-text ml-3">PPMP</span>
            </a>
        </div>
    </div>
    
    <a href="file_submission.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'file_submission.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <span class="sidebar-text ml-3">File Submission</span>
    </a>
    <a href="submit_documents.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'submit_documents.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
        </svg>
        <span class="sidebar-text ml-3">Upload</span>
    </a>
    <a href="track_requests.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'track_requests.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <span class="sidebar-text ml-3">Track Requests</span>
    </a>
    <a href="cabac_view.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'cabac_view.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"></path>
        </svg>
        <span class="sidebar-text ml-3">CABAC Viewer</span>
    </a>
    <a href="proc_notifications.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'proc_notifications.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 102.828 2.828l6.414 6.414a2 2 0 01-2.828 2.828L4.828 7z"></path>
        </svg>
        <span class="sidebar-text ml-3">Notifications</span>
    </a>
    <a href="pr_submission.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'pr_submission.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6l2 5H7l2-5zM7 10l1 4h8l1-4m-4 5v4m-4-4v4"></path>
        </svg>
        <span class="sidebar-text ml-3">PR Submission</span>
    </a>
    <a href="proc_announcements.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'proc_announcements.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 18h4m-6-4h8m-10-4h12M5 6h14l-2 5H7L5 6z"></path>
        </svg>
        <span class="sidebar-text ml-3">Announcements</span>
    </a>
    <a href="automated_reports.php" class="hidden flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon <?php echo (basename($_SERVER['PHP_SELF']) === 'automated_reports.php') ? 'text-maroon bg-red-50 border-r-4 border-maroon' : ''; ?>">
        <svg class="w-5 h-5 sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M3 12h18M3 19h18"></path>
        </svg>
        <span class="sidebar-text ml-3">Report</span>
    </a>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const utilizationToggle = document.querySelector('[data-dropdown-toggle="utilizationMenu"]');
    const utilizationMenu = document.getElementById('utilizationMenu');
    if (utilizationToggle && utilizationMenu) {
        utilizationToggle.addEventListener('click', function(event) {
            event.preventDefault();
            const willOpen = utilizationMenu.classList.contains('hidden');
            utilizationMenu.classList.toggle('hidden');
            const arrow = utilizationToggle.querySelector('.sidebar-dropdown-arrow');
            if (arrow) {
                arrow.classList.toggle('rotate-180', willOpen);
            }
        });
    }
});
</script>

