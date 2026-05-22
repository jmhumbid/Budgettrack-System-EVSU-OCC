<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

if (!$db) {
    die("Database connection failed");
}

try {
    // Disable foreign key checks temporarily
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Drop existing tables and recreate with correct structure
    $db->exec("DROP TABLE IF EXISTS `cabac_program_entries`");
    $db->exec("DROP TABLE IF EXISTS `cabac_programs`");
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // Create cabac_programs table for dropdown options
    $db->exec("
        CREATE TABLE `cabac_programs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `program_name` VARCHAR(255) NOT NULL,
            `type` ENUM('fiduciary', 'non-fiduciary') NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_program` (`program_name`, `type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table cabac_programs created successfully.<br>";

    // Create cabac_program_entries table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `cabac_program_entries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `program_id` INT NOT NULL,
            `program_name` VARCHAR(255) NOT NULL,
            `approved_budget` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `available_allotment` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_cabac_program`
                FOREIGN KEY (`program_id`)
                REFERENCES `cabac_programs`(`id`)
                ON UPDATE CASCADE
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table cabac_program_entries created successfully.<br>";

    // Insert default Non-Fiduciary programs
    $nonFiduciaryPrograms = [
        'Faculty and Staff Development',
        'Curriculum Development',
        'Student Development',
        'Facilities Development',
        'Research',
        'Production',
        'Extension',
        'Administrator',
        'Mandatory Reserve',
        'Petition'
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO cabac_programs (program_name, type) VALUES (?, 'non-fiduciary')");
    foreach ($nonFiduciaryPrograms as $program) {
        $stmt->execute([$program]);
    }
    echo "Non-Fiduciary programs inserted.<br>";

    // Insert default Fiduciary programs
    $fiduciaryPrograms = [
        'Athletics',
        'Library Fee',
        'Laboratory Fee',
        'NSTP',
        'SCUAA Fee',
        'Computer Fee',
        'Internet Fee',
        'CCNA',
        'Cultural',
        'Development Fee',
        'Student Activity Fee',
        'Student Council Fee',
        'School Organ Fee',
        'Guidance Fee',
        'Medical Dental Fee',
        'Insurance Fee',
        'School ID Fee',
        'Graduation Fee',
        'Handbook',
        'OJT Fee',
        'Documentary Stamp',
        'Trust Fund',
        'Other Services Income',
        'Rent Income'
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO cabac_programs (program_name, type) VALUES (?, 'fiduciary')");
    foreach ($fiduciaryPrograms as $program) {
        $stmt->execute([$program]);
    }
    echo "Fiduciary programs inserted.<br>";

    echo "<br><strong>Setup completed successfully!</strong>";
    echo "<br><a href='../pages/cabac.php'>Go to CABAC page</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
