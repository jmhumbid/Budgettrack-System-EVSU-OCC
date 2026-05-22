<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'procurement') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/PurchaseRequest.php';
require_once __DIR__ . '/../components/profile_avatar.php';

$prId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$prId) {
    header('Location: pr_submission.php');
    exit;
}

$pr = new PurchaseRequest();
$prDetails = $pr->getPRById($prId);
$prFiles = $pr->getPRFiles($prId);

if (!$prDetails) {
    header('Location: pr_submission.php');
    exit;
}

$username = $_SESSION['user_name'] ?? 'Procurement';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View PR Files - <?php echo htmlspecialchars($prDetails['pr_number']); ?></title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($prDetails['pr_number']); ?></h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($prDetails['dept_name']); ?></p>
            </div>
            <a href="pr_submission.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back</a>
        </div>
        
        <div class="space-y-4">
            <?php foreach ($prFiles as $file): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($file['file_name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($file['uploaded_at'])); ?></p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="viewFile('<?php echo htmlspecialchars($file['file_path']); ?>', '<?php echo htmlspecialchars($file['file_name']); ?>')" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">View</button>
                            <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" download class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">Download</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- File Viewer Modal -->
    <div id="fileViewerModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-6xl w-full max-h-screen overflow-auto">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 id="fileViewerTitle" class="text-lg font-semibold"></h3>
                    <button onclick="closeFileViewer()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="fileViewerContent" class="p-4"></div>
            </div>
        </div>
    </div>
    
    <script>
        function viewFile(filePath, fileName) {
            const modal = document.getElementById('fileViewerModal');
            const title = document.getElementById('fileViewerTitle');
            const content = document.getElementById('fileViewerContent');
            
            title.textContent = fileName;
            content.innerHTML = '<p class="text-center py-8">Loading...</p>';
            modal.classList.remove('hidden');
            
            const ext = fileName.split('.').pop().toLowerCase();
            const fullPath = '../' + filePath;
            
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'jfif'].includes(ext)) {
                content.innerHTML = `<img src="${fullPath}" class="max-w-full h-auto mx-auto" alt="${fileName}">`;
            } else if (ext === 'pdf') {
                content.innerHTML = `<iframe src="${fullPath}" class="w-full h-screen" style="min-height: 600px;"></iframe>`;
            } else if (['xlsx', 'xls', 'csv'].includes(ext)) {
                content.innerHTML = `<iframe src="../ajax/view_excel.php?file=${encodeURIComponent(filePath)}" class="w-full h-screen" style="min-height: 600px;"></iframe>`;
            } else {
                content.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-gray-600 mb-4">Preview not available for this file type.</p>
                        <a href="${fullPath}" download class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Download File</a>
                    </div>
                `;
            }
        }
        
        function closeFileViewer() {
            document.getElementById('fileViewerModal').classList.add('hidden');
        }
    </script>

</body>
</html>

