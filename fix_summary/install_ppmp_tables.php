<?php
/**
 * PPMP Tables Installation Script
 * Access this file via browser to automatically create PPMP tables
 * URL: http://localhost/BudgetTrack/install_ppmp_tables.php
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPMP Tables Installation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #800000;
            border-bottom: 3px solid #800000;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
            margin: 10px 0;
        }
        .sql-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #800000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #5a0000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 PPMP Tables Installation</h1>
        
        <?php
        try {
            $db = getDB();
            $errors = [];
            $success = [];
            
            // SQL for PPMP main table
            $sql1 = "CREATE TABLE IF NOT EXISTS ppmp (
                id INT AUTO_INCREMENT PRIMARY KEY,
                department_id INT NOT NULL,
                fiscal_year VARCHAR(10) NOT NULL,
                ppmp_number VARCHAR(50) NOT NULL,
                is_indicative BOOLEAN DEFAULT 0,
                is_final BOOLEAN DEFAULT 0,
                status ENUM('draft', 'approved', 'rejected') DEFAULT 'draft',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (department_id) REFERENCES departments(id),
                FOREIGN KEY (created_by) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            // SQL for PPMP items table
            $sql2 = "CREATE TABLE IF NOT EXISTS ppmp_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ppmp_id INT NOT NULL,
                general_description TEXT NOT NULL,
                project_type VARCHAR(100) NOT NULL,
                quantity DECIMAL(10,2) NOT NULL,
                unit VARCHAR(50) NOT NULL,
                recommended_mode VARCHAR(100) NOT NULL,
                pre_procurement_conference VARCHAR(10) DEFAULT 'N',
                start_procurement DATE,
                end_ads_posting DATE,
                expected_delivery DATE,
                source_of_funds VARCHAR(100) NOT NULL,
                estimated_budget DECIMAL(15,2) NOT NULL,
                allocated_supporting_funds DECIMAL(15,2) DEFAULT 0,
                remarks TEXT,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ppmp_id) REFERENCES ppmp(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            // SQL for PPMP history table
            $sql3 = "CREATE TABLE IF NOT EXISTS ppmp_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ppmp_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                changed_by INT NOT NULL,
                changes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ppmp_id) REFERENCES ppmp(id) ON DELETE CASCADE,
                FOREIGN KEY (changed_by) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            // Execute table creation
            echo '<div class="info"><strong>📋 Creating PPMP tables...</strong></div>';
            
            // Create ppmp table
            try {
                $db->exec($sql1);
                $success[] = '✅ Table "ppmp" created successfully';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $success[] = '✅ Table "ppmp" already exists';
                } else {
                    $errors[] = '❌ Error creating "ppmp" table: ' . $e->getMessage();
                }
            }
            
            // Create ppmp_items table
            try {
                $db->exec($sql2);
                $success[] = '✅ Table "ppmp_items" created successfully';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $success[] = '✅ Table "ppmp_items" already exists';
                } else {
                    $errors[] = '❌ Error creating "ppmp_items" table: ' . $e->getMessage();
                }
            }
            
            // Create ppmp_history table
            try {
                $db->exec($sql3);
                $success[] = '✅ Table "ppmp_history" created successfully';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $success[] = '✅ Table "ppmp_history" already exists';
                } else {
                    $errors[] = '❌ Error creating "ppmp_history" table: ' . $e->getMessage();
                }
            }
            
            // Display results
            if (!empty($success)) {
                echo '<div class="success">';
                echo '<strong>✨ Installation Results:</strong><br>';
                foreach ($success as $msg) {
                    echo $msg . '<br>';
                }
                echo '</div>';
            }
            
            if (!empty($errors)) {
                echo '<div class="error">';
                echo '<strong>⚠️ Errors:</strong><br>';
                foreach ($errors as $msg) {
                    echo $msg . '<br>';
                }
                echo '</div>';
            }
            
            if (empty($errors)) {
                echo '<div class="success">';
                echo '<strong>🎉 Installation Complete!</strong><br>';
                echo 'All PPMP tables have been created successfully. You can now use the PPMP feature.';
                echo '</div>';
                
                echo '<div class="info">';
                echo '<strong>📊 Tables Created:</strong><br>';
                echo '1. <strong>ppmp</strong> - Main PPMP records<br>';
                echo '2. <strong>ppmp_items</strong> - PPMP procurement items<br>';
                echo '3. <strong>ppmp_history</strong> - PPMP change history<br>';
                echo '</div>';
                
                echo '<a href="pages/ppmp.php" class="btn">Go to PPMP Page →</a>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Database Connection Error:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div class="info" style="margin-top: 30px;">
            <strong>ℹ️ Note:</strong><br>
            • This script is safe to run multiple times<br>
            • Existing tables will not be modified<br>
            • For security, consider deleting this file after installation<br>
            • Access PPMP from: Utilization dropdown (Departments/Procurement) or Budget Workflow (Admin)
        </div>
    </div>
</body>
</html>
