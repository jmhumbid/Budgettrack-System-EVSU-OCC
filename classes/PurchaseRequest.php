<?php
require_once __DIR__ . '/../config/database.php';

class PurchaseRequest {
    private $conn;
    private $table_name = 'purchase_requests';
    private $files_table = 'purchase_request_files';

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Generate unique PR number
     */
    private function generatePRNumber() {
        $year = date('Y');
        $prefix = 'PR-' . $year . '-';
        
        // Get the last PR number for this year
        $query = "SELECT pr_number FROM " . $this->table_name . " 
                  WHERE pr_number LIKE :prefix 
                  ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $prefixPattern = $prefix . '%';
        $stmt->bindParam(':prefix', $prefixPattern);
        $stmt->execute();
        
        $lastPR = $stmt->fetchColumn();
        
        if ($lastPR) {
            // Extract the number part and increment
            $lastNum = (int)substr($lastPR, strlen($prefix));
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new purchase request
     */
    public function createPR($procurement_user_id, $department_id, $fiscal_year, $notes = null) {
        $pr_number = $this->generatePRNumber();
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (pr_number, procurement_user_id, department_id, fiscal_year, notes, status) 
                  VALUES (:pr_number, :procurement_user_id, :department_id, :fiscal_year, :notes, 'pending')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pr_number', $pr_number);
        $stmt->bindParam(':procurement_user_id', $procurement_user_id);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->bindParam(':notes', $notes);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Add file to purchase request
     */
    public function addFile($purchase_request_id, $file_name, $file_path, $file_size, $file_type) {
        $query = "INSERT INTO " . $this->files_table . " 
                  (purchase_request_id, file_name, file_path, file_size, file_type) 
                  VALUES (:pr_id, :file_name, :file_path, :file_size, :file_type)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pr_id', $purchase_request_id);
        $stmt->bindParam(':file_name', $file_name);
        $stmt->bindParam(':file_path', $file_path);
        $stmt->bindParam(':file_size', $file_size);
        $stmt->bindParam(':file_type', $file_type);
        
        return $stmt->execute();
    }

    /**
     * Get purchase request by ID
     */
    public function getPRById($id) {
        $query = "SELECT pr.*, d.dept_name, u.first_name, u.last_name, u.email as procurement_email
                  FROM " . $this->table_name . " pr
                  LEFT JOIN departments d ON pr.department_id = d.id
                  LEFT JOIN users u ON pr.procurement_user_id = u.id
                  WHERE pr.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all PRs for procurement office
     */
    public function getPRsForProcurement($filters = []) {
        $query = "SELECT pr.*, d.dept_name, 
                  (SELECT COUNT(*) FROM " . $this->files_table . " WHERE purchase_request_id = pr.id) as file_count
                  FROM " . $this->table_name . " pr
                  LEFT JOIN departments d ON pr.department_id = d.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['department_id'])) {
            $query .= " AND pr.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = [];
                foreach ($filters['status'] as $idx => $status) {
                    $key = ':status_' . $idx;
                    $placeholders[] = $key;
                    $params[$key] = $status;
                }
                $query .= " AND pr.status IN (" . implode(',', $placeholders) . ")";
            } else {
                $query .= " AND pr.status = :status";
                $params[':status'] = $filters['status'];
            }
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(pr.submitted_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(pr.submitted_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY pr.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get completed/archived PRs for procurement office
     */
    public function getArchivedPRsForProcurement($filters = []) {
        $query = "SELECT pr.*, d.dept_name, 
                  (SELECT COUNT(*) FROM " . $this->files_table . " WHERE purchase_request_id = pr.id) as file_count
                  FROM " . $this->table_name . " pr
                  LEFT JOIN departments d ON pr.department_id = d.id
                  WHERE pr.status = 'complete'";
        
        $params = [];
        
        if (!empty($filters['department_id'])) {
            $query .= " AND pr.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(pr.completed_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(pr.completed_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY pr.completed_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get PRs for a specific department
     */
    public function getPRsForDepartment($department_id) {
        $query = "SELECT pr.*, 
                  (SELECT COUNT(*) FROM " . $this->files_table . " WHERE purchase_request_id = pr.id) as file_count
                  FROM " . $this->table_name . " pr
                  WHERE pr.department_id = :department_id AND pr.status != 'complete'
                  ORDER BY pr.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get archived/completed PRs for a specific department
     */
    public function getArchivedPRsForDepartment($department_id) {
        $query = "SELECT pr.*, 
                  (SELECT COUNT(*) FROM " . $this->files_table . " WHERE purchase_request_id = pr.id) as file_count
                  FROM " . $this->table_name . " pr
                  WHERE pr.department_id = :department_id AND pr.status = 'complete'
                  ORDER BY pr.completed_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get PRs for supply office (all pending/processing/delivered)
     */
    public function getPRsForSupplyOffice($filters = []) {
        $query = "SELECT pr.*, d.dept_name,
                  (SELECT COUNT(*) FROM " . $this->files_table . " WHERE purchase_request_id = pr.id) as file_count
                  FROM " . $this->table_name . " pr
                  LEFT JOIN departments d ON pr.department_id = d.id
                  WHERE pr.status IN ('pending', 'processing', 'delivered')";
        
        $params = [];
        
        if (!empty($filters['department_id'])) {
            $query .= " AND pr.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(pr.submitted_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(pr.submitted_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY pr.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get archived/completed PRs for supply office
     */
    public function getArchivedPRsForSupplyOffice($filters = []) {
        $query = "SELECT pr.*, d.dept_name,
                  (SELECT COUNT(*) FROM " . $this->files_table . " WHERE purchase_request_id = pr.id) as file_count
                  FROM " . $this->table_name . " pr
                  LEFT JOIN departments d ON pr.department_id = d.id
                  WHERE pr.status = 'complete'";
        
        $params = [];
        
        if (!empty($filters['department_id'])) {
            $query .= " AND pr.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(pr.completed_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(pr.completed_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY pr.completed_at DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get files for a purchase request
     */
    public function getPRFiles($purchase_request_id) {
        $query = "SELECT * FROM " . $this->files_table . " 
                  WHERE purchase_request_id = :pr_id 
                  ORDER BY uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pr_id', $purchase_request_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update PR status
     */
    public function updateStatus($id, $status) {
        $statusFieldMap = [
            'processing' => 'processed_at',
            'delivered' => 'delivered_at',
            'received' => 'received_at',
            'complete' => 'completed_at'
        ];
        
        $statusField = $statusFieldMap[$status] ?? null;
        
        if ($statusField) {
            $query = "UPDATE " . $this->table_name . " 
                      SET status = :status, " . $statusField . " = NOW() 
                      WHERE id = :id";
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET status = :status 
                      WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Mark as processing (when procurement submits)
     */
    public function markAsProcessing($id) {
        return $this->updateStatus($id, 'processing');
    }

    /**
     * Mark as delivered (supply office)
     */
    public function markAsDelivered($id) {
        return $this->updateStatus($id, 'delivered');
    }

    /**
     * Mark as received (department)
     */
    public function markAsReceived($id) {
        return $this->updateStatus($id, 'received');
    }

    /**
     * Mark as complete (after received)
     */
    public function markAsComplete($id) {
        return $this->updateStatus($id, 'complete');
    }
}
?>

