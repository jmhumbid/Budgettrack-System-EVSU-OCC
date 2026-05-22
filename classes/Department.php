<?php
/**
 * Department Class
 * BudgetTrack System - EVSU Ormoc Campus
 */

require_once __DIR__ . '/../config/database.php';

class Department {
    private $conn;
    private $table_name = "departments";

    public $id;
    public $dept_name;
    public $dept_code;
    public $dept_description;
    public $is_active;

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Get all departments
     */
    public function getAllDepartments() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 1 ORDER BY dept_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get department by ID
     */
    public function getDepartmentById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Create new department
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (dept_name, dept_code, dept_description) 
                  VALUES (:dept_name, :dept_code, :dept_description)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':dept_name', $this->dept_name);
        $stmt->bindParam(':dept_code', $this->dept_code);
        $stmt->bindParam(':dept_description', $this->dept_description);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Update department
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET dept_name = :dept_name, dept_code = :dept_code, 
                      dept_description = :dept_description, is_active = :is_active
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':dept_name', $this->dept_name);
        $stmt->bindParam(':dept_code', $this->dept_code);
        $stmt->bindParam(':dept_description', $this->dept_description);
        $stmt->bindParam(':is_active', $this->is_active);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Delete department (soft delete)
     */
    public function delete() {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Check if department code exists
     */
    public function deptCodeExists($dept_code, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE dept_code = :dept_code";
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':dept_code', $dept_code);
        
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
