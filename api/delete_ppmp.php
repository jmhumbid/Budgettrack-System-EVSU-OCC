<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $ppmpId = $data['id'] ?? 0;
    $departmentId = $_SESSION['department_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if (!$departmentId && $userRole === 'budget') {
        $stmt = $db->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['department_id']) {
            $departmentId = $row['department_id'];
        } else {
            $stmt = $db->prepare("SELECT u.department_id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'budget' AND u.department_id IS NOT NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $departmentId = $row['department_id'];
        }
    }
    
    if (!$ppmpId) {
        echo json_encode(['success' => false, 'message' => 'PPMP ID required']);
        exit;
    }
    
    // Verify ownership
    $sql = "SELECT * FROM ppmp WHERE id = ? AND department_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$ppmpId, $departmentId]);
    $ppmp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ppmp) {
        echo json_encode(['success' => false, 'message' => 'PPMP not found or you do not have permission to delete it']);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Get PPMP details BEFORE deletion (this is critical!)
        $ppmpNumber = $ppmp['ppmp_number'];
        $fiscalYear = $ppmp['fiscal_year'];
        
        error_log("Attempting to delete PPMP ID: {$ppmpId}, Number: {$ppmpNumber}, Fiscal Year: {$fiscalYear}, Department: {$departmentId}");
        
        // Find the corresponding LIB for this department and fiscal year
        // Use LIKE to match both "2026" and "FY 2026" formats
        $stmt = $db->prepare("
            SELECT id FROM line_item_budgets 
            WHERE department_id = ? AND (fiscal_year = ? OR fiscal_year LIKE ?) 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$departmentId, $fiscalYear, "%{$fiscalYear}%"]);
        $lib = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lib) {
            $libId = $lib['id'];
            error_log("Found LIB ID: {$libId} for department {$departmentId}, fiscal year {$fiscalYear}");
            
            // First, get all PPMP items to know which LIB categories to delete
            $ppmpItemsStmt = $db->prepare("
                SELECT lib_category, lib_particulars, lib_account_code 
                FROM ppmp_items 
                WHERE ppmp_id = ? 
                AND lib_category IS NOT NULL 
                AND lib_particulars IS NOT NULL
            ");
            $ppmpItemsStmt->execute([$ppmpId]);
            $ppmpItems = $ppmpItemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($ppmpItems) . " PPMP items with LIB mappings");
            
            $totalDeleted = 0;
            
            // For each PPMP item, delete the corresponding LIB item
            // This handles the AGGREGATED approach (no PPMP reference in particulars)
            foreach ($ppmpItems as $ppmpItem) {
                // Delete LIB items that match this category/particulars/account_code
                // This will delete the aggregated item created by sync_ppmp_to_lib_helper.php
                $deleteStmt = $db->prepare("
                    DELETE FROM line_item_budget_items 
                    WHERE lib_id = ? 
                    AND category = ? 
                    AND particulars = ? 
                    AND account_code = ?
                ");
                $deleteStmt->execute([
                    $libId,
                    $ppmpItem['lib_category'],
                    $ppmpItem['lib_particulars'],
                    $ppmpItem['lib_account_code']
                ]);
                
                $deletedCount = $deleteStmt->rowCount();
                if ($deletedCount > 0) {
                    $totalDeleted += $deletedCount;
                    error_log("Deleted {$deletedCount} LIB item(s): category='{$ppmpItem['lib_category']}', particulars='{$ppmpItem['lib_particulars']}'");
                }
            }
            
            // ALSO handle the OLD approach with PPMP references (for backwards compatibility)
            // Try multiple patterns to ensure we catch all variations
            $patterns = [
                "(PPMP #{$ppmpNumber} - Item #%",  // Standard pattern
                "%PPMP #{$ppmpNumber}%",  // Broader match
            ];
            
            foreach ($patterns as $pattern) {
                $stmt = $db->prepare("
                    DELETE FROM line_item_budget_items 
                    WHERE lib_id = ? AND particulars LIKE ?
                ");
                $stmt->execute([$libId, $pattern]);
                $deletedCount = $stmt->rowCount();
                
                if ($deletedCount > 0) {
                    $totalDeleted += $deletedCount;
                    error_log("Pattern '{$pattern}' deleted {$deletedCount} LIB items (old approach)");
                }
            }
            
            error_log("Total deleted {$totalDeleted} LIB items linked to PPMP #{$ppmpNumber} from LIB ID {$libId}");
            
        } else {
            error_log("No LIB found for department {$departmentId} and fiscal year {$fiscalYear}");
        }
        
        // NOW delete PPMP (items will be deleted automatically due to CASCADE)
        $sql = "DELETE FROM ppmp WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$ppmpId]);
        
        error_log("PPMP ID {$ppmpId} deleted successfully");
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'PPMP deleted successfully and linked LIB items removed',
            'deleted_lib_items' => $totalDeleted ?? 0
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Transaction rolled back: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in delete_ppmp.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
