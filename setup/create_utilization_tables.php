<?php
/**
 * Setup script to create Budget Utilization tables
 * Run this once to set up the database tables
 * Access via: http://localhost/Budgettrack/setup/create_utilization_tables.php
 */

require_once __DIR__ . '/../config/database.php';

// Allow access without authentication for setup
// session_start();
// if (!isset($_SESSION['user_role'])) {
//     // Allow setup to run
// }

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Create Utilization Tables</title></head><body>";
    echo "<h2>Creating Budget Utilization Tables</h2>";
    echo "<pre>";
    
    // Create budget_utilization_entries table (create first since other tables reference it)
    $sql1 = "CREATE TABLE IF NOT EXISTS `budget_utilization_entries` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `department_id` INT(11) NOT NULL,
      `expense_category` VARCHAR(255) NOT NULL,
      `allocated_budget` DECIMAL(15,2) DEFAULT 0.00,
      `deductions` DECIMAL(15,2) DEFAULT 0.00,
      `total_balance` DECIMAL(15,2) DEFAULT 0.00,
      `fiscal_year` YEAR(4) NOT NULL,
      `created_by` INT(11) NOT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_department` (`department_id`),
      KEY `idx_fiscal_year` (`fiscal_year`),
      KEY `idx_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql1);
    echo "✓ Created budget_utilization_entries table\n";
    
    // Add foreign keys after table creation
    try {
        $db->exec("ALTER TABLE `budget_utilization_entries` 
            ADD CONSTRAINT `fk_util_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE");
        echo "✓ Added foreign key for department_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for department_id: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $db->exec("ALTER TABLE `budget_utilization_entries` 
            ADD CONSTRAINT `fk_util_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        echo "✓ Added foreign key for created_by\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for created_by: " . $e->getMessage() . "\n";
        }
    }
    
    // Create utilization_purchase_requests table
    $sql2 = "CREATE TABLE IF NOT EXISTS `utilization_purchase_requests` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `department_id` INT(11) NOT NULL,
      `purchase_request` VARCHAR(255) NOT NULL,
      `particulars` TEXT,
      `pr_number` VARCHAR(100),
      `po_number` VARCHAR(100),
      `date` DATE,
      `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `deducted_from_entry_id` INT(11) DEFAULT NULL,
      `is_deducted` TINYINT(1) DEFAULT 0,
      `fiscal_year` YEAR(4) NOT NULL,
      `created_by` INT(11) NOT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_department` (`department_id`),
      KEY `idx_fiscal_year` (`fiscal_year`),
      KEY `idx_deducted_from` (`deducted_from_entry_id`),
      KEY `idx_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql2);
    echo "✓ Created utilization_purchase_requests table\n";
    
    // Add foreign keys
    try {
        $db->exec("ALTER TABLE `utilization_purchase_requests` 
            ADD CONSTRAINT `fk_pr_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE");
        echo "✓ Added foreign key for department_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for department_id: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $db->exec("ALTER TABLE `utilization_purchase_requests` 
            ADD CONSTRAINT `fk_pr_entry` FOREIGN KEY (`deducted_from_entry_id`) REFERENCES `budget_utilization_entries` (`id`) ON DELETE SET NULL");
        echo "✓ Added foreign key for deducted_from_entry_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for deducted_from_entry_id: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $db->exec("ALTER TABLE `utilization_purchase_requests` 
            ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        echo "✓ Added foreign key for created_by\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for created_by: " . $e->getMessage() . "\n";
        }
    }
    
    // Create utilization_travels table
    $sql3 = "CREATE TABLE IF NOT EXISTS `utilization_travels` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `department_id` INT(11) NOT NULL,
      `travelled` VARCHAR(255) NOT NULL,
      `event_activity` TEXT,
      `date` DATE,
      `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `deducted_from_entry_id` INT(11) DEFAULT NULL,
      `fiscal_year` YEAR(4) NOT NULL,
      `created_by` INT(11) NOT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_department` (`department_id`),
      KEY `idx_fiscal_year` (`fiscal_year`),
      KEY `idx_deducted_from` (`deducted_from_entry_id`),
      KEY `idx_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql3);
    echo "✓ Created utilization_travels table\n";
    
    // Add deducted_from_entry_id column if it doesn't exist (for existing tables)
    try {
        $checkColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'deducted_from_entry_id'");
        if ($checkColumn->rowCount() === 0) {
            $db->exec("ALTER TABLE `utilization_travels` 
                ADD COLUMN `deducted_from_entry_id` INT(11) DEFAULT NULL AFTER `amount`");
            echo "✓ Added deducted_from_entry_id column\n";
        } else {
            echo "✓ deducted_from_entry_id column already exists\n";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "⚠ Column deducted_from_entry_id: " . $e->getMessage() . "\n";
        }
    }
    
    // Add foreign keys
    try {
        $db->exec("ALTER TABLE `utilization_travels` 
            ADD CONSTRAINT `fk_travel_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE");
        echo "✓ Added foreign key for department_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for department_id: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $db->exec("ALTER TABLE `utilization_travels` 
            ADD CONSTRAINT `fk_travel_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        echo "✓ Added foreign key for created_by\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for created_by: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $db->exec("ALTER TABLE `utilization_travels` 
            ADD CONSTRAINT `fk_travel_entry` FOREIGN KEY (`deducted_from_entry_id`) REFERENCES `budget_utilization_entries` (`id`) ON DELETE SET NULL");
        echo "✓ Added foreign key for deducted_from_entry_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "⚠ Foreign key for deducted_from_entry_id: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ All tables created successfully!\n";
    echo "</pre>";
    echo "<p style='color: green; font-weight: bold;'>Setup complete! You can now use the Utilization system.</p>";
    echo "<p><a href='../pages/utilization.php'>Go to Utilization Page</a></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "❌ Error creating tables: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "</pre>";
    echo "<p style='color: red;'>Please check your database connection and try again.</p>";
}
