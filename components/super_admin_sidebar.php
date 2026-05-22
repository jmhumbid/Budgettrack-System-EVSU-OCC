<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-gradient-to-b from-maroon to-red-800 text-white shadow-xl fixed left-0 top-0 h-screen z-40 overflow-y-auto">
    <div class="p-6">
        <h2 class="text-2xl font-bold mb-8">Super Admin</h2>
        <nav class="space-y-2">
            <a href="super_admin_dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors <?php echo ($currentPage === 'super_admin_dashboard.php') ? 'bg-white bg-opacity-20' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>
            <a href="user_management.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors <?php echo ($currentPage === 'user_management.php') ? 'bg-white bg-opacity-20' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                User Management
            </a>
            <a href="role_management.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors <?php echo ($currentPage === 'role_management.php') ? 'bg-white bg-opacity-20' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                Role Management
            </a>
            <a href="department_management.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors <?php echo ($currentPage === 'department_management.php') ? 'bg-white bg-opacity-20' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Department Management
            </a>
            <a href="admin_dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors <?php echo ($currentPage === 'admin_dashboard.php') ? 'bg-white bg-opacity-20' : ''; ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Return
            </a>
        </nav>
    </div>
</aside>

