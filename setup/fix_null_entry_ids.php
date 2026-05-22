<?php
/**
 * Script to fix NULL entry_id values in budget_utilization_entries
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Fix NULL entry_id</title>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; }</style>";
    echo "</head><body>";
    echo "<h2>Fixing NULL entry_id Values</h2>";
    echo "<pre>";
    
    // Get all entries with NULL entry_id, grouped by department and fiscal_year
    $nullEntries = $db->query("
        SELECT id, department_id, fiscal_year 
        FROM budget_utilization_entries 
        WHERE entry_id IS NULL 
        ORDER BY department_id, fiscal_year, id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($nullEntries) === 0) {
        echo "✓ No NULL entry_id values found. All entries have entry_id set.\n";
    } else {
        echo "Found " . count($nullEntries) . " entries with NULL entry_id\n\n";
        
        // Group by department and fiscal_year to maintain sequential numbering
        $groups = [];
        foreach ($nullEntries as $entry) {
            $key = $entry['department_id'] . '_' . $entry['fiscal_year'];
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $entry;
        }
        
        foreach ($groups as $key => $entries) {
            list($deptId, $fiscalYear) = explode('_', $key);
            
            // Get max entry_id for this department/fiscal year
            $maxStmt = $db->prepare("
                SELECT COALESCE(MAX(entry_id), 0) 
                FROM budget_utilization_entries 
                WHERE department_id = :dept_id AND fiscal_year = :year
            ");
            $maxStmt->execute([':dept_id' => $deptId, ':year' => $fiscalYear]);
            $maxEntryId = (int)$maxStmt->fetchColumn();
            $nextEntryId = $maxEntryId + 1;
            
            echo "Department $deptId, Fiscal Year $fiscalYear: Starting from entry_id = $nextEntryId\n";
            
            foreach ($entries as $entry) {
                $updateStmt = $db->prepare("
                    UPDATE budget_utilization_entries 
                    SET entry_id = :entry_id 
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':entry_id' => $nextEntryId,
                    ':id' => $entry['id']
                ]);
                echo "  Updated id {$entry['id']} -> entry_id $nextEntryId\n";
                $nextEntryId++;
            }
        }
        
        echo "\n✓ Updated " . count($nullEntries) . " entries with NULL entry_id\n";
    }
    
    echo "</pre>";
    echo "<p><strong>✅ Fix completed!</strong></p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}

