<?php
require_once __DIR__ . '/../config/database.php';

class DepartmentBudget {
    private $conn;
    private $table_name = 'department_budgets';

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Get department budget
     */
    public function getDepartmentBudget($department_id, $fiscal_year = null) {
        if (!$fiscal_year) {
            $fiscal_year = date('Y');
        }

        $query = "SELECT db.*, d.dept_name
                  FROM " . $this->table_name . " db
                  LEFT JOIN departments d ON db.department_id = d.id
                  WHERE db.department_id = :department_id AND db.fiscal_year = :fiscal_year";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update department budget
     */
    public function updateBudget($department_id, $fiscal_year, $total_allocated, $total_utilized) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (department_id, fiscal_year, total_allocated, total_utilized) 
                  VALUES (:department_id, :fiscal_year, :total_allocated, :total_utilized)
                  ON DUPLICATE KEY UPDATE 
                  total_allocated = VALUES(total_allocated),
                  total_utilized = VALUES(total_utilized)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->bindParam(':total_allocated', $total_allocated);
        $stmt->bindParam(':total_utilized', $total_utilized);

        return $stmt->execute();
    }

    /**
     * Get all department budgets
     */
    public function getAllBudgets($fiscal_year = null) {
        if (!$fiscal_year) {
            $fiscal_year = date('Y');
        }

        $query = "SELECT db.*, d.dept_name
                  FROM " . $this->table_name . " db
                  LEFT JOIN departments d ON db.department_id = d.id
                  WHERE db.fiscal_year = :fiscal_year
                  ORDER BY db.total_allocated DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Format currency amount
     */
    public function formatCurrency($amount) {
        if ($amount == 0) {
            return '₱0';
        } elseif ($amount >= 1000000) {
            return '₱' . number_format($amount / 1000000, 1) . 'M';
        } elseif ($amount >= 1000) {
            return '₱' . number_format($amount / 1000, 1) . 'K';
        } else {
            return '₱' . number_format($amount, 2);
        }
    }
}
?>
