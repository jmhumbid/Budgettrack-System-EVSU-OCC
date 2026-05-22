<?php
session_start();
if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Department • Announcements</title>
<link rel="icon" type="image/png" href="../img/evsu_logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{maroon:'#800000','maroon-dark':'#5a0000'}}}}</script>
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
  <aside class="w-64 bg-white border-r">
    <div class="p-6 border-b">
      <h2 class="text-2xl font-bold text-maroon">BudgetTrack</h2>
      <p class="text-sm text-gray-600">Department Portal</p>
    </div>
    <nav class="mt-6">
      <a href="dept_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Dashboard</a>
      <a href="lib_submit.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Submit LIB</a>
      <a href="ppmp_submit.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Submit PPMP</a>
      <a href="track_requests.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Track Requests</a>
      <a href="reports.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Reports</a>
      <a href="cabac_view.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">CABAC Viewer</a>
      <a href="notifications.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Notifications</a>
      <a href="announcements.php" class="flex items-center px-6 py-3 text-maroon bg-red-50 border-r-4 border-maroon">Announcements</a>
      <a href="account_settings.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Account Settings</a>
      <button onclick="confirmLogout()" class="flex w-full items-center px-6 py-3 text-white bg-red-600 hover:bg-red-700 mt-4">Logout</button>
    </nav>
  </aside>
  <main class="flex-1">
    <header class="bg-white border-b px-6 py-4">
      <h1 class="text-2xl font-bold text-gray-900">Announcements</h1>
      <p class="text-gray-600">Latest updates for your department.</p>
    </header>
    <section class="p-6">
      <div class="bg-white border rounded-xl p-6">
        <p class="text-gray-600">Stub page. Add announcements list here.</p>
      </div>
    </section>
  </main>
</div>
<div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Confirm Logout</h3>
        <button onclick="closeLogoutModal()" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>
      <p class="text-gray-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex justify-end gap-3">
        <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
        <button onclick="performLogout()" class="px-4 py-2 bg-red-600 text-white rounded">Logout</button>
      </div>
    </div>
  </div>
</div>
<script>
function confirmLogout(){document.getElementById('logoutModal').classList.remove('hidden')}
function closeLogoutModal(){document.getElementById('logoutModal').classList.add('hidden')}
function performLogout(){window.location.href='../auth/logout.php'}
</script>

</body>
</html>