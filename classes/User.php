<?php

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $middle_name;
    public $employee_id;
    public $department_id;
    public $role_id;
    public $is_active;
    public $last_login;
    public $created_by;
    public $password_change_required;

    public function __construct() {
        $this->conn = getDB();
        if ($this->conn === null) {
            throw new Exception("Database connection failed. Please check your database configuration.");
        }
    }


    public function authenticate($email, $password) {
        $query = "SELECT u.*, r.role_name, d.dept_name 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE u.email = :email AND u.is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password_hash'])) {
                // Update last login
                $this->updateLastLogin($row['id']);
                
                return $row;
            }
        }
        return false;
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_login = CURRENT_TIMESTAMP 
                  WHERE id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }

    /**
     * Create new user
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (email, password_hash, first_name, last_name, middle_name, 
                   employee_id, department_id, role_id, created_by) 
                  VALUES 
                  (:email, :password_hash, :first_name, :last_name, :middle_name, 
                   :employee_id, :department_id, :role_id, :created_by)";

        $stmt = $this->conn->prepare($query);

        // Hash password
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);

        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':middle_name', $this->middle_name);
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':department_id', $this->department_id);
        $stmt->bindParam(':role_id', $this->role_id);
        $stmt->bindParam(':created_by', $this->created_by);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Get all users with role and department info
     */
    public function getAllUsers() {
        $query = "SELECT u.*, r.role_name, d.dept_name, 
                         CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN users creator ON u.created_by = creator.id
                  ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $query = "SELECT u.*, r.role_name, d.dept_name 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE u.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Update user
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET email = :email, first_name = :first_name, last_name = :last_name, 
                      middle_name = :middle_name, employee_id = :employee_id, 
                      department_id = :department_id, role_id = :role_id, 
                      is_active = :is_active
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':middle_name', $this->middle_name);
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':department_id', $this->department_id);
        $stmt->bindParam(':role_id', $this->role_id);
        $stmt->bindParam(':is_active', $this->is_active);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Delete user (soft delete)
     */
    public function delete() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 0 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Hard delete user (permanent deletion)
     * Handles foreign key constraints by reassigning or deleting related records
     */
    public function hardDelete() {
        try {
            $this->conn->beginTransaction();
            
            $userId = $this->id;
            
            // Get the first budget office user as a fallback for reassigning records
            $fallbackStmt = $this->conn->prepare("SELECT u.id FROM users u 
                                                   INNER JOIN roles r ON u.role_id = r.id 
                                                   WHERE r.role_name = 'budget' AND u.is_active = 1 
                                                   ORDER BY u.id ASC LIMIT 1");
            $fallbackStmt->execute();
            $fallbackUser = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
            $fallbackUserId = $fallbackUser ? $fallbackUser['id'] : null;
            
            // Handle announcements table - reassign to fallback user or delete if no fallback
            try {
                if ($fallbackUserId && $fallbackUserId != $userId) {
                    // Reassign announcements to fallback user
                    $announcementStmt = $this->conn->prepare("UPDATE announcements SET created_by = :fallback_id WHERE created_by = :user_id");
                    $announcementStmt->bindParam(':fallback_id', $fallbackUserId);
                    $announcementStmt->bindParam(':user_id', $userId);
                    $announcementStmt->execute();
                } else {
                    // No fallback user available, delete announcements
                    $deleteAnnouncementStmt = $this->conn->prepare("DELETE FROM announcements WHERE created_by = :user_id");
                    $deleteAnnouncementStmt->bindParam(':user_id', $userId);
                    $deleteAnnouncementStmt->execute();
                }
            } catch (PDOException $e) {
                // Table might not exist, continue
            }
            
            // Handle budget_allocations table - reassign to fallback user
            try {
                if ($fallbackUserId && $fallbackUserId != $userId) {
                    $budgetStmt = $this->conn->prepare("UPDATE budget_allocations SET created_by = :fallback_id WHERE created_by = :user_id");
                    $budgetStmt->bindParam(':fallback_id', $fallbackUserId);
                    $budgetStmt->bindParam(':user_id', $userId);
                    $budgetStmt->execute();
                }
            } catch (PDOException $e) {
                // Table might not exist, continue
            }
            
            // Now delete the user
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Get user by verification code (temporary password)
     */
    public function getUserByVerificationCode($verification_code) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE password_change_required = 1 
                  AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Check if the verification code matches the stored password hash
            if (password_verify($verification_code, $user['password_hash'])) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Set up new password for user (completes account setup)
     */
    public function setupNewPassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table_name . " 
                  SET password_hash = :password_hash, 
                      password_change_required = 0 
                  WHERE id = :user_id 
                  AND password_change_required = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if employee ID exists
     */
    public function employeeIdExists($employee_id, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE employee_id = :employee_id";
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($user_id) {
        $query = "SELECT p.permission_name, p.module
                  FROM permissions p
                  INNER JOIN role_permissions rp ON p.id = rp.permission_id
                  INNER JOIN users u ON rp.role_id = u.role_id
                  WHERE u.id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($user_id, $permission_name) {
        $query = "SELECT COUNT(*) as count
                  FROM permissions p
                  INNER JOIN role_permissions rp ON p.id = rp.permission_id
                  INNER JOIN users u ON rp.role_id = u.role_id
                  WHERE u.id = :user_id AND p.permission_name = :permission_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':permission_name', $permission_name);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Change user password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        // First verify current password
        $query = "SELECT password_hash FROM " . $this->table_name . " WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $row['password_hash'])) {
                // Current password is correct, update to new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_query = "UPDATE " . $this->table_name . " 
                                SET password_hash = :new_password_hash 
                                WHERE id = :user_id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':new_password_hash', $new_password_hash);
                $update_stmt->bindParam(':user_id', $user_id);
                
                if ($update_stmt->execute()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Reset user password (for admin use)
     */
    public function resetPassword($user_id, $new_password) {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table_name . " 
                  SET password_hash = :new_password_hash 
                  WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':new_password_hash', $new_password_hash);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Update a specific user field
     */
    public function updateUserField($user_id, $field, $value) {
        // Validate field name
        $allowedFields = ['first_name', 'last_name', 'middle_name', 'employee_id', 'email'];
        if (!in_array($field, $allowedFields)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . " SET {$field} = :value, updated_at = NOW() WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUsersWithDepartments() {
        $query = "SELECT u.*, d.dept_name, r.role_name 
                  FROM " . $this->table_name . " u
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE u.department_id IS NOT NULL AND u.is_active = 1
                  ORDER BY d.dept_name, u.first_name, u.last_name";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update user profile photo
     */
    public function updateProfilePhoto($user_id, $photo_path) {
        $query = "UPDATE " . $this->table_name . " SET profile_photo = :photo_path, updated_at = NOW() WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':photo_path', $photo_path);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    /**
     * Get user profile photo
     */
    public function getProfilePhoto($user_id) {
        $query = "SELECT profile_photo FROM " . $this->table_name . " WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['profile_photo'] : null;
    }
}
?>
