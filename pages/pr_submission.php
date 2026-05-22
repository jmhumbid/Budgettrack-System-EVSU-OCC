<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'procurement') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Department.php';
require_once __DIR__ . '/../classes/PurchaseRequest.php';
include __DIR__ . '/../components/profile_avatar.php';

$username = $_SESSION['user_name'] ?? 'Procurement';
$userEmail = $_SESSION['user_email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$portalLabel = 'Procurement Portal';

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);

// Get departments for dropdown
$department = new Department();
$departments = $department->getAllDepartments();

// Get existing PRs
$pr = new PurchaseRequest();
$existingPRs = $pr->getPRsForProcurement([]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - PR Submission</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        maroon: '#800000',
                        'maroon-dark': '#5a0000'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter">
    <div class="flex min-h-screen">
        <div id="sidebar" class="fixed left-0 top-0 h-screen bg-white shadow-lg border-r border-gray-200 transition-all duration-300 z-40 overflow-y-auto w-64">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-maroon sidebar-text">BudgetTrack</h2>
                    <p class="text-sm text-gray-600 sidebar-text"><?php echo htmlspecialchars($portalLabel); ?></p>
                </div>
                <button id="sidebarToggle" type="button" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Toggle sidebar">
                    <svg class="w-5 h-5 text-gray-600 sidebar-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5l-7 7 7 7M20 5l-7 7 7 7"></path>
                    </svg>
                </button>
            </div>
            <?php include __DIR__ . '/../components/proc_sidebar.php'; ?>
        </div>
        <div class="flex-1 flex flex-col" data-main-content>
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="text-white max-w-2xl">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1">Purchase Request Submission</h1>
                                    <p class="text-red-100 text-sm">Submit Purchase Requests for departments</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <?php include __DIR__ . '/../components/notification_bell.php'; ?>
                            <div class="relative">
                                <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 bg-white bg-opacity-20 backdrop-blur-sm px-4 py-2 rounded-xl hover:bg-opacity-30 transition-colors border border-white border-opacity-30">
                                    <?php render_profile_avatar(['classes' => 'bg-white bg-opacity-30 text-white font-semibold border border-white border-opacity-50']); ?>
                                    <div class="text-white text-sm">
                                        <div class="font-medium"><?php echo htmlspecialchars($username); ?></div>
                                        <div class="text-xs text-red-100"><?php echo htmlspecialchars($userEmail); ?></div>
                                    </div>
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="profileDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl z-50 hidden border border-gray-100">
                                    <div class="py-2">
                                        <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Profile
                                        </a>
                                        <a href="change_password.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            </svg>
                                            Change Password
                                        </a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <button onclick="confirmLogout()" class="flex items-center w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Logout
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-1 p-6 space-y-6">
                <!-- Upload Section -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Submit New Purchase Request</h2>
                    
                    <form id="prUploadForm" enctype="multipart/form-data">
                        <!-- Department Selection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Department *</label>
                            <select id="departmentSelect" name="department_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-maroon focus:border-maroon">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- File Upload -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Files *</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-maroon transition-colors">
                                <input type="file" id="fileInput" name="files[]" multiple accept="*/*" class="hidden" required>
                                <button type="button" onclick="document.getElementById('fileInput').click()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors mb-2">
                                    Choose Files
                                </button>
                                <p class="text-sm text-gray-500">You can upload multiple files. All file types are supported.</p>
                                <div id="fileList" class="mt-4 text-left space-y-2"></div>
                            </div>
                        </div>
                        
                        <!-- Notes (Optional) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                            <textarea id="notesInput" name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-maroon focus:border-maroon" placeholder="Add any additional notes or comments..."></textarea>
                        </div>
                        
                        <button type="button" id="submitBtn" onclick="showConfirmationModal()" class="w-full px-6 py-3 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                            Submit Purchase Request
                        </button>
                    </form>
                </div>
                
                <!-- Existing PRs Section -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Submitted Purchase Requests</h2>
                        <div class="flex gap-3">
                            <select id="filterDepartment" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filterStatus" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="delivered">Delivered</option>
                            </select>
                            <input type="date" id="filterDate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Filter by date">
                            <button onclick="filterPRs()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">Filter</button>
                            <button onclick="openArchivedModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                                Completed & Archived
                            </button>
                        </div>
                    </div>
                    
                    <div id="prListContainer" class="space-y-4">
                        <!-- PR list will be loaded here -->
                    </div>
                </div>
            </div>
            
            <!-- Archived PRs Modal -->
            <div id="archivedModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center p-6 border-b border-gray-200">
                            <h3 class="text-xl font-bold text-gray-900">Completed & Archived Purchase Requests</h3>
                            <button onclick="closeArchivedModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex gap-3">
                                <select id="archivedFilterDepartment" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" id="archivedFilterDate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Filter by date">
                                <button onclick="filterArchivedPRs()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">Filter</button>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-6">
                            <div id="archivedPRListContainer" class="space-y-4">
                                <!-- Archived PR list will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Confirmation Modal -->
            <div id="confirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm Submission</h3>
                        <p class="text-gray-600 mb-6">Are you sure the files are complete?</p>
                        <div class="flex justify-end space-x-3">
                            <button onclick="closeConfirmationModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                No
                            </button>
                            <button onclick="submitPR()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                                Yes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- PR Submission Success Modal -->
    <div id="prSuccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Request Submitted</h3>
                    <button onclick="closePRSuccessModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="flex items-center justify-center mb-4">
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-gray-600 text-center mb-2" id="prSuccessMessage">Purchase Request submitted successfully!</p>
                    <p class="text-gray-900 text-center font-semibold" id="prNumberDisplay"></p>
                </div>
                <div class="flex justify-end">
                    <button onclick="closePRSuccessModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Logout</h3>
                    <button onclick="closeLogoutModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-4">
                    <p class="text-gray-600 mb-6">Are you sure you want to logout? You will need to login again to access the dashboard.</p>
                    <div class="flex justify-end space-x-3">
                        <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button onclick="performLogout()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function performLogout() {
            window.location.href = '../auth/logout.php';
        }

        function toggleProfileDropdown() {
            document.getElementById('profileDropdown')?.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');

            if (!button && dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const body = document.body;
            const mainContent = document.querySelector('[data-main-content]');
            const storageKey = 'sidebarCollapsed';

            function applyState(collapsed) {
                if (collapsed) {
                    body.classList.add('sidebar-collapsed');
                } else {
                    body.classList.remove('sidebar-collapsed');
                }
            }

            const initialState = localStorage.getItem(storageKey) === 'true';
            applyState(initialState);

            if (mainContent) {
                setTimeout(function() {
                    mainContent.classList.add('sidebar-ready');
                }, 10);
            }

            toggleBtn?.addEventListener('click', function() {
                const collapsed = !body.classList.contains('sidebar-collapsed');
                applyState(collapsed);
                localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            });
            
            // Load initial PR list
            loadPRList();
        });
        
        // PR Submission functionality
        let selectedFiles = [];
        
        document.getElementById('fileInput').addEventListener('change', function(e) {
            selectedFiles = Array.from(e.target.files);
            displayFileList();
            updateSubmitButton();
        });
        
        document.getElementById('departmentSelect').addEventListener('change', function() {
            updateSubmitButton();
        });
        
        function displayFileList() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            if (selectedFiles.length === 0) {
                return;
            }
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200';
                fileItem.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm text-gray-700">${file.name}</span>
                        <span class="text-xs text-gray-500">(${(file.size / 1024).toFixed(2)} KB)</span>
                    </div>
                    <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;
                fileList.appendChild(fileItem);
            });
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            const fileInput = document.getElementById('fileInput');
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
            displayFileList();
            updateSubmitButton();
        }
        
        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            const deptSelected = document.getElementById('departmentSelect').value !== '';
            const hasFiles = selectedFiles.length > 0;
            submitBtn.disabled = !(deptSelected && hasFiles);
        }
        
        function showConfirmationModal() {
            if (!document.getElementById('departmentSelect').value || selectedFiles.length === 0) {
                alert('Please select a department and upload at least one file.');
                return;
            }
            document.getElementById('confirmationModal').classList.remove('hidden');
        }
        
        function closeConfirmationModal() {
            document.getElementById('confirmationModal').classList.add('hidden');
        }
        
        function showPRSuccessModal() {
            document.getElementById('prSuccessModal').classList.remove('hidden');
        }
        
        function closePRSuccessModal() {
            document.getElementById('prSuccessModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('prSuccessModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closePRSuccessModal();
            }
        });
        
        function submitPR() {
            const form = document.getElementById('prUploadForm');
            const formData = new FormData(form);
            formData.append('fiscal_year', new Date().getFullYear());
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            fetch('../ajax/submit_pr.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Server error');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success modal
                    document.getElementById('prNumberDisplay').textContent = 'PR #' + data.pr_number;
                    showPRSuccessModal();
                    // Reset form
                    form.reset();
                    selectedFiles = [];
                    displayFileList();
                    updateSubmitButton();
                    closeConfirmationModal();
                    // Reload PR list
                    loadPRList();
                } else {
                    alert('Error: ' + data.message);
                }
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Purchase Request';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Purchase Request';
            });
        }
        
        function loadPRList() {
            const deptFilter = document.getElementById('filterDepartment').value;
            const statusFilter = document.getElementById('filterStatus').value;
            const filterDate = document.getElementById('filterDate').value;
            
            let url = '../ajax/get_pr_list.php?';
            if (deptFilter) url += 'department_id=' + deptFilter + '&';
            if (statusFilter) url += 'status=' + statusFilter + '&';
            if (filterDate) {
                url += 'date_from=' + filterDate + '&';
                url += 'date_to=' + filterDate + '&';
            }
            
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Filter out completed PRs from main list
                    const activePRs = data.prs.filter(pr => pr.status !== 'complete');
                    displayPRList(activePRs);
                }
            })
            .catch(error => {
                console.error('Error loading PRs:', error);
            });
        }
        
        function loadArchivedPRList() {
            const deptFilter = document.getElementById('archivedFilterDepartment').value;
            const filterDate = document.getElementById('archivedFilterDate').value;
            
            let url = '../ajax/get_archived_pr_list.php?';
            if (deptFilter) url += 'department_id=' + deptFilter + '&';
            if (filterDate) {
                url += 'date_from=' + filterDate + '&';
                url += 'date_to=' + filterDate + '&';
            }
            
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayArchivedPRList(data.prs);
                }
            })
            .catch(error => {
                console.error('Error loading archived PRs:', error);
            });
        }
        
        function openArchivedModal() {
            document.getElementById('archivedModal').classList.remove('hidden');
            loadArchivedPRList();
        }
        
        function closeArchivedModal() {
            document.getElementById('archivedModal').classList.add('hidden');
        }
        
        function filterArchivedPRs() {
            loadArchivedPRList();
        }
        
        function displayArchivedPRList(prs) {
            const container = document.getElementById('archivedPRListContainer');
            if (!prs || prs.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">No archived purchase requests found.</p>';
                return;
            }
            
            container.innerHTML = prs.map(pr => {
                const statusColor = 'bg-gray-100 text-gray-800';
                const completedDate = pr.completed_at ? new Date(pr.completed_at).toLocaleString() : 'N/A';
                const submittedDate = pr.submitted_at ? new Date(pr.submitted_at).toLocaleString() : 'N/A';
                const fileCount = pr.file_count || 0;
                
                return `
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-semibold text-gray-900">${pr.pr_number || 'N/A'}</h3>
                                <p class="text-sm text-gray-600">${pr.dept_name || 'Unknown Department'}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusColor}">COMPLETE</span>
                        </div>
                        <div class="text-sm text-gray-500 mb-3">
                            <p>Submitted: ${submittedDate}</p>
                            <p>Completed: ${completedDate}</p>
                            <p>Files: ${fileCount}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="viewPRFiles(${pr.id})" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">View Files</button>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function displayPRList(prs) {
            const container = document.getElementById('prListContainer');
            if (!prs || prs.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">No purchase requests found.</p>';
                return;
            }
            
            container.innerHTML = prs.map(pr => {
                const statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'processing': 'bg-blue-100 text-blue-800',
                    'delivered': 'bg-purple-100 text-purple-800',
                    'received': 'bg-green-100 text-green-800',
                    'complete': 'bg-gray-100 text-gray-800'
                };
                const statusLabels = {
                    'pending': 'Pending',
                    'processing': 'Processing',
                    'delivered': 'Delivered - Awaiting Pickup',
                    'received': 'Received',
                    'complete': 'Complete'
                };
                const statusColor = statusColors[pr.status] || 'bg-gray-100 text-gray-800';
                const statusLabel = statusLabels[pr.status] || (pr.status || 'pending').toUpperCase();
                const submittedDate = pr.submitted_at ? new Date(pr.submitted_at).toLocaleString() : 'N/A';
                const fileCount = pr.file_count || 0;
                
                return `
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-semibold text-gray-900">${pr.pr_number || 'N/A'}</h3>
                                <p class="text-sm text-gray-600">${pr.dept_name || 'Unknown Department'}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusColor}">${statusLabel}</span>
                        </div>
                        <div class="text-sm text-gray-500 mb-3">
                            <p>Submitted: ${submittedDate}</p>
                            <p>Files: ${fileCount}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="viewPRFiles(${pr.id})" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">View Files</button>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function filterPRs() {
            loadPRList();
        }
        
        function viewPRFiles(prId) {
            fetch('../ajax/get_pr_files.php?pr_id=' + prId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create modal for file viewing
                    const modal = document.createElement('div');
                    modal.id = 'prFileModal';
                    modal.className = 'fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex items-center justify-center p-4';
                    modal.innerHTML = `
                        <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-auto">
                            <div class="flex justify-between items-center p-4 border-b">
                                <h3 class="text-lg font-semibold">PR Files</h3>
                                <button onclick="this.closest('#prFileModal').remove()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="p-4 space-y-3">
                                ${data.files.map(file => `
                                    <div class="border border-gray-200 rounded-lg p-3 flex justify-between items-center">
                                        <div>
                                            <h4 class="font-semibold text-gray-900">${file.file_name}</h4>
                                            <p class="text-sm text-gray-500">${new Date(file.uploaded_at).toLocaleString()}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="viewFileInModal('${file.file_path}', '${file.file_name}')" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">View</button>
                                            <a href="../${file.file_path}" download class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">Download</a>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading files');
            });
        }
        
        function viewFileInModal(filePath, fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            const fullPath = '../' + filePath;
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg max-w-6xl w-full max-h-screen overflow-auto">
                    <div class="flex justify-between items-center p-4 border-b">
                        <h3 class="text-lg font-semibold">${fileName}</h3>
                        <button onclick="this.closest('div').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4" id="fileContent">
                        ${['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'jfif'].includes(ext) ? 
                            `<img src="${fullPath}" class="max-w-full h-auto mx-auto" alt="${fileName}">` :
                            ext === 'pdf' ? 
                                `<iframe src="${fullPath}" class="w-full" style="min-height: 600px;"></iframe>` :
                                ['xlsx', 'xls', 'csv'].includes(ext) ?
                                    `<iframe src="../ajax/view_excel.php?file=${encodeURIComponent(filePath)}" class="w-full" style="min-height: 600px;"></iframe>` :
                                    `<div class="text-center py-8"><p class="text-gray-600 mb-4">Preview not available.</p><a href="${fullPath}" download class="px-4 py-2 bg-blue-600 text-white rounded-lg">Download</a></div>`
                        }
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Load PR list on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPRList();
        });
    </script>
    <style>
        :root {
            --sidebar-expanded-width: 256px;
            --sidebar-collapsed-width: 80px;
        }

        [data-main-content] {
            margin-left: var(--sidebar-expanded-width);
        }

        [data-main-content].sidebar-ready {
            transition: margin-left 0.3s ease;
        }

        #sidebar {
            width: var(--sidebar-expanded-width);
            transition: width 0.3s ease;
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

        body.sidebar-collapsed #sidebar nav a {
            justify-content: center;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        body.sidebar-collapsed #sidebar .sidebar-toggle-icon {
            transform: rotate(180deg);
        }
    </style>

</body>
</html>

