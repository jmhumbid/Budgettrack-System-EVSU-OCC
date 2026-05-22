<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

$db = getDB();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'get_programs':
        getPrograms($db);
        break;
    case 'add_program':
        addProgram($db);
        break;
    case 'delete_program':
        deleteProgram($db);
        break;
    case 'get_entries':
        getEntries($db);
        break;
    case 'save_entry':
        saveEntry($db);
        break;
    case 'delete_entry':
        deleteEntry($db);
        break;
    case 'save_all_entries':
        saveAllEntries($db);
        break;
    case 'auto_save_entries':
        autoSaveEntries($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Get all programs (for dropdowns)
function getPrograms($db) {
    try {
        $type = isset($_GET['type']) ? $_GET['type'] : null;
        
        if ($type) {
            $stmt = $db->prepare("SELECT id, program_name, type FROM cabac_programs WHERE type = ? ORDER BY program_name ASC");
            $stmt->execute([$type]);
        } else {
            $stmt = $db->query("SELECT id, program_name, type FROM cabac_programs ORDER BY type, program_name ASC");
        }
        
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'programs' => $programs
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Add a new program to dropdown
function addProgram($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $programName = isset($data['program_name']) ? trim($data['program_name']) : '';
        $type = isset($data['type']) ? $data['type'] : '';
        
        if (empty($programName) || empty($type)) {
            echo json_encode(['success' => false, 'message' => 'Program name and type are required']);
            return;
        }
        
        // Check if program already exists
        $checkStmt = $db->prepare("SELECT id FROM cabac_programs WHERE program_name = ? AND type = ?");
        $checkStmt->execute([$programName, $type]);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Program already exists']);
            return;
        }
        
        $stmt = $db->prepare("INSERT INTO cabac_programs (program_name, type) VALUES (?, ?)");
        $stmt->execute([$programName, $type]);
        
        $newId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Program added successfully',
            'id' => $newId,
            'program_name' => $programName,
            'type' => $type
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Delete a program from dropdown
function deleteProgram($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $programName = isset($data['program_name']) ? trim($data['program_name']) : '';
        $type = isset($data['type']) ? $data['type'] : '';
        
        if (empty($programName) || empty($type)) {
            echo json_encode(['success' => false, 'message' => 'Program name and type are required']);
            return;
        }
        
        $stmt = $db->prepare("DELETE FROM cabac_programs WHERE program_name = ? AND type = ?");
        $stmt->execute([$programName, $type]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Program deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Program not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get all budget entries
function getEntries($db) {
    try {
        $programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
        
        if ($programId) {
            $stmt = $db->prepare("
                SELECT e.*, p.program_name as parent_program, p.type 
                FROM cabac_program_entries e 
                JOIN cabac_programs p ON e.program_id = p.id 
                WHERE e.program_id = ?
                ORDER BY e.id ASC
            ");
            $stmt->execute([$programId]);
        } else {
            $stmt = $db->query("
                SELECT e.*, p.program_name as parent_program, p.type 
                FROM cabac_program_entries e 
                JOIN cabac_programs p ON e.program_id = p.id 
                ORDER BY p.type, p.program_name, e.id ASC
            ");
        }
        
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'entries' => $entries
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Save a single budget entry
function saveEntry($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = isset($data['id']) ? (int)$data['id'] : null;
        $programId = isset($data['program_id']) ? (int)$data['program_id'] : null;
        $programName = isset($data['program_name']) ? trim($data['program_name']) : '';
        $approvedBudget = isset($data['approved_budget']) ? floatval($data['approved_budget']) : 0;
        $availableAllotment = isset($data['available_allotment']) ? floatval($data['available_allotment']) : 0;
        $balance = $approvedBudget - $availableAllotment;
        
        if (empty($programId)) {
            echo json_encode(['success' => false, 'message' => 'Program ID is required']);
            return;
        }
        
        if ($id) {
            // Update existing entry
            $stmt = $db->prepare("
                UPDATE cabac_program_entries 
                SET program_name = ?, approved_budget = ?, available_allotment = ?, balance = ?
                WHERE id = ?
            ");
            $stmt->execute([$programName, $approvedBudget, $availableAllotment, $balance, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Entry updated successfully',
                'id' => $id
            ]);
        } else {
            // Insert new entry
            $stmt = $db->prepare("
                INSERT INTO cabac_program_entries (program_id, program_name, approved_budget, available_allotment, balance)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$programId, $programName, $approvedBudget, $availableAllotment, $balance]);
            
            $newId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Entry added successfully',
                'id' => $newId
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Delete a budget entry
function deleteEntry($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = isset($data['id']) ? (int)$data['id'] : null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Entry ID is required']);
            return;
        }
        
        $stmt = $db->prepare("DELETE FROM cabac_program_entries WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Entry deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Entry not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Save all entries at once (for SAVE button)
function saveAllEntries($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['entries'])) {
            echo json_encode(['success' => false, 'message' => 'No entries provided']);
            return;
        }
        
        $entries = $data['entries'];
        $programId = isset($data['program_id']) ? (int)$data['program_id'] : null;
        
        if (!$programId) {
            echo json_encode(['success' => false, 'message' => 'Program ID is required']);
            return;
        }
        
        $db->beginTransaction();
        
        // Delete existing entries for this program
        $deleteStmt = $db->prepare("DELETE FROM cabac_program_entries WHERE program_id = ?");
        $deleteStmt->execute([$programId]);
        
        // Insert new entries
        $insertStmt = $db->prepare("
            INSERT INTO cabac_program_entries (program_id, program_name, approved_budget, available_allotment, balance)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($entries as $entry) {
            $programName = isset($entry['program_name']) ? trim($entry['program_name']) : '';
            $approvedBudget = isset($entry['approved_budget']) ? floatval($entry['approved_budget']) : 0;
            $availableAllotment = isset($entry['available_allotment']) ? floatval($entry['available_allotment']) : 0;
            $balance = $approvedBudget - $availableAllotment;
            
            if (!empty($programName) || $approvedBudget > 0 || $availableAllotment > 0) {
                $insertStmt->execute([$programId, $programName, $approvedBudget, $availableAllotment, $balance]);
            }
        }
        
        $db->commit();

        try {
            $programStmt = $db->prepare("SELECT program_name, type FROM cabac_programs WHERE id = ?");
            $programStmt->execute([$programId]);
            $programRow = $programStmt->fetch(PDO::FETCH_ASSOC);
            $selectedProgramName = $programRow && isset($programRow['program_name']) ? $programRow['program_name'] : 'Selected Program';
            $selectedProgramType = $programRow && isset($programRow['type']) ? $programRow['type'] : '';

            $notification = new Notification();
            $programTypeLabel = !empty($selectedProgramType) ? ucfirst(str_replace('-', ' ', $selectedProgramType)) : '';
            $title = 'CABAC ' . $selectedProgramName . ' Updated';
            $who = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'A user';
            $when = date('M j, Y g:i A');
            $message = $who . ' updated ' . $selectedProgramName;
            if (!empty($programTypeLabel)) {
                $message .= ' (' . $programTypeLabel . ')';
            }
            $notification->notifyAllActiveUsers($title, $message, 'info');
        } catch (Exception $e) {
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'All entries saved successfully'
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Auto-save entries (silent, no notifications - for auto-save feature)
function autoSaveEntries($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['entries'])) {
            echo json_encode(['success' => false, 'message' => 'No entries provided']);
            return;
        }
        
        $entries = $data['entries'];
        $programId = isset($data['program_id']) ? (int)$data['program_id'] : null;
        
        if (!$programId) {
            echo json_encode(['success' => false, 'message' => 'Program ID is required']);
            return;
        }
        
        $db->beginTransaction();
        
        // Delete existing entries for this program
        $deleteStmt = $db->prepare("DELETE FROM cabac_program_entries WHERE program_id = ?");
        $deleteStmt->execute([$programId]);
        
        // Insert new entries and collect IDs
        $insertStmt = $db->prepare("
            INSERT INTO cabac_program_entries (program_id, program_name, approved_budget, available_allotment, balance)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $entryIds = [];
        
        foreach ($entries as $entry) {
            $programName = isset($entry['program_name']) ? trim($entry['program_name']) : '';
            $approvedBudget = isset($entry['approved_budget']) ? floatval($entry['approved_budget']) : 0;
            $availableAllotment = isset($entry['available_allotment']) ? floatval($entry['available_allotment']) : 0;
            $balance = $approvedBudget - $availableAllotment;
            
            if (!empty($programName) || $approvedBudget > 0 || $availableAllotment > 0) {
                $insertStmt->execute([$programId, $programName, $approvedBudget, $availableAllotment, $balance]);
                $entryIds[] = $db->lastInsertId();
            }
        }
        
        $db->commit();
        
        // No notifications for auto-save - that's only for the manual Save button
        
        echo json_encode([
            'success' => true,
            'message' => 'Entries auto-saved successfully',
            'entry_ids' => $entryIds
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
