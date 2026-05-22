<?php
/**
 * Role Class
 * BudgetTrack System - EVSU Ormoc Campus
 */

require_once __DIR__ . '/../config/database.php';

class Role {
    private $conn;
    private $table_name = "roles";

    public $id;
    public $role_name;
    public $role_description;

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Get all roles
     */
    public function getAllRoles() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY role_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get role by ID
     */
    public function getRoleById($id) {
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
     * Create new role
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (role_name, role_description) VALUES (:role_name, :role_description)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':role_name', $this->role_name);
        $stmt->bindParam(':role_description', $this->role_description);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Update role
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET role_name = :role_name, role_description = :role_description 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':role_name', $this->role_name);
        $stmt->bindParam(':role_description', $this->role_description);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Check if role is in use (has users assigned)
     */
    public function isInUse() {
        $query = "SELECT COUNT(*) as user_count FROM users WHERE role_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['user_count'] > 0;
    }
    
    /**
     * Get count of users using this role
     */
    public function getUserCount() {
        $query = "SELECT COUNT(*) as user_count FROM users WHERE role_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['user_count'];
    }

    /**
     * Delete role
     * Returns array with 'success' boolean and 'message' string
     */
    public function delete() {
        // Check if role is in use
        if ($this->isInUse()) {
            $userCount = $this->getUserCount();
            return [
                'success' => false,
                'message' => "Cannot delete role. There are {$userCount} user(s) assigned to this role. Please reassign or remove these users first."
            ];
        }
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        try {
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Role deleted successfully.'
                ];
            }
            return [
                'success' => false,
                'message' => 'Failed to delete role.'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Cannot delete role. ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if role name exists
     */
    public function roleNameExists($role_name, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE role_name = :role_name";
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_name', $role_name);
        
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
