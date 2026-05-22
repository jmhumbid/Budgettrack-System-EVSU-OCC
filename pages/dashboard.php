<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}


header('Location: admin_dashboard.php');
exit;

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BudgetTrack | Admin Dashboard</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="nav">
                <div class="brand">
                    <span>BudgetTrack</span>
                </div>
                <nav class="nav-links">
                    <a href="dashboard.php" class="active">Dashboard</a>
                    <a href="#allocations">Allocations</a>
                    <a href="#ppmp">PPMP & LIB</a>
                    <a href="#reports">Reports</a>
                    <a href="#users">Users</a>
                    <a href="../index.php">Home</a>
                    <button onclick="confirmLogout()" class="logout-btn" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-left: 10px;">Logout</button>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <section class="page-header" style="background: linear-gradient(135deg, #800000 0%, #b30000 100%);">
            <div class="container">
                <h1>Admin Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($username); ?>. Monitor allocations, utilization, and workflows in real time.</p>
            </div>
        </section>

        <section class="section" id="kpis">
            <div class="container">
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">💰</div>
                        <h3>Total Allocations (FY)</h3>
                        <p id="kpi-allocations" style="font-size:22px; font-weight:800; color:#800000;">₱ 0.00</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🧾</div>
                        <h3>Total Utilization</h3>
                        <p id="kpi-utilization" style="font-size:22px; font-weight:800; color:#800000;">₱ 0.00</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">📦</div>
                        <h3>Pending PR/PO</h3>
                        <p id="kpi-pending" style="font-size:22px; font-weight:800; color:#800000;">0</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">👥</div>
                        <h3>Active Departments</h3>
                        <p id="kpi-depts" style="font-size:22px; font-weight:800; color:#800000;">0</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="allocations">
            <div class="container">
                <h2>Allocations vs Utilization</h2>
                <p class="lead">Track spending progress by department/category to ensure compliance and efficiency.</p>
                <div class="grid">
                    <div class="card">
                        <h3>By Department</h3>
                        <canvas id="chartDept"></canvas>
                    </div>
                    <div class="card">
                        <h3>By Category</h3>
                        <canvas id="chartCat"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="ppmp">
            <div class="container">
                <h2>PPMP & LIB Workflow</h2>
                <p class="lead">Monitor approvals and bottlenecks across stages to comply with RA 9184.</p>
                <div class="grid">
                    <div class="card">
                        <h3>Pending Approvals</h3>
                        <ul style="margin:0; padding-left:18px; line-height:1.8; color:#374151;">
                            <li>Department Head: <strong>0</strong></li>
                            <li>Budget Office Review: <strong>0</strong></li>
                            <li>BAC/Procurement: <strong>0</strong></li>
                            <li>Accounting/COA: <strong>0</strong></li>
                        </ul>
                    </div>
                    <div class="card">
                        <h3>Recent Activity</h3>
                        <ul style="margin:0; padding-left:18px; line-height:1.8; color:#374151;">
                            <li>New PPMP submitted by CICS</li>
                            <li>LIB updated for Laboratory Supplies</li>
                            <li>PR approved by Budget Office</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="users">
            <div class="container">
                <h2>Administration</h2>
                <div class="grid">
                    <div class="card">
                        <div class="card-icon">🔐</div>
                        <h3>User Management</h3>
                        <p>Create roles for Admin, Budget Staff, Department Heads, and Users. Reset credentials and enforce policies.</p>
                        <a class="cta-btn" href="#">Open Users</a>
                    </div>
                    <div class="card">
                        <div class="card-icon">🏛️</div>
                        <h3>Departments</h3>
                        <p>Configure departments and link fund sources: Fiduciary, Non‑Fiduciary, and TOSI.</p>
                        <a class="cta-btn" href="#">Open Departments</a>
                    </div>
                    <div class="card">
                        <div class="card-icon">📑</div>
                        <h3>Reports</h3>
                        <p>Generate allocation/utilization reports by fiscal year with export options (PDF/CSV).</p>
                        <a class="cta-btn" href="#">Open Reports</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div>© <span id="y"></span> EVSU–Ormoc Campus • BudgetTrack</div>
            <div>Admin: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </footer>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #333;">Confirm Logout</h3>
            <p>Are you sure you want to logout? You will need to login again to access the dashboard.</p>
            <div style="text-align: right; margin-top: 20px;">
                <button onclick="closeLogoutModal()" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancel</button>
                <button onclick="performLogout()" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Logout</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('y').textContent = new Date().getFullYear();

        // Logout modal functions
        function confirmLogout() {
            document.getElementById('logoutModal').style.display = 'block';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function performLogout() {
            window.location.href = '../auth/logout.php';
        }

        // Close modal when clicking outside of it - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (modal && event.target === modal) {
                closeLogoutModal();
            }
        });

        // Demo numbers (replace with PHP values from DB later)
        const pesos = (n) => '₱ ' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
        document.getElementById('kpi-allocations').textContent = pesos(12500000);
        document.getElementById('kpi-utilization').textContent = pesos(8425000);
        document.getElementById('kpi-pending').textContent = 12;
        document.getElementById('kpi-depts').textContent = 9;

        // Charts
        const deptCtx = document.getElementById('chartDept');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: ['CICS', 'COE', 'CET', 'CAS', 'CABA'],
                datasets: [
                    {
                        label: 'Allocated',
                        backgroundColor: 'rgba(128,0,0,0.6)',
                        borderColor: 'rgba(128,0,0,1)',
                        data: [3.2, 2.4, 1.8, 2.0, 1.1],
                    },
                    {
                        label: 'Utilized',
                        backgroundColor: 'rgba(179,0,0,0.6)',
                        borderColor: 'rgba(179,0,0,1)',
                        data: [2.1, 1.7, 1.2, 1.3, 0.7],
                    }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { callback: (val) => val + ' M' } } }
            }
        });

        const catCtx = document.getElementById('chartCat');
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: ['Instruction', 'Research', 'Extension', 'Admin'],
                datasets: [{
                    data: [45, 20, 15, 20],
                    backgroundColor: ['#800000', '#b30000', '#cc3333', '#e27a7a']
                }]
            },
            options: { responsive: true }
        });
    </script>

</body>
</html>


