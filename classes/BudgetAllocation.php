<?php
require_once __DIR__ . '/../config/database.php';

class BudgetAllocation {
    private $conn;
    private $table_name = 'budget_allocations';

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Get all budget allocations with department and category details
     */
    public function getAllAllocations($fiscal_year = null) {
        if (!$fiscal_year) {
            $fiscal_year = date('Y');
        }

        $query = "SELECT 
                    ba.*,
                    d.dept_name,
                    bc.category_name,
                    bc.category_code,
                    u.first_name,
                    u.last_name
                  FROM " . $this->table_name . " ba
                  LEFT JOIN departments d ON ba.department_id = d.id
                  LEFT JOIN budget_categories bc ON ba.category_id = bc.id
                  LEFT JOIN users u ON ba.created_by = u.id
                  WHERE ba.fiscal_year = :fiscal_year
                  ORDER BY d.dept_name, bc.category_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get allocations for a specific department
     */
    public function getDepartmentAllocations($department_id, $fiscal_year = null) {
        if (!$fiscal_year) {
            $fiscal_year = date('Y');
        }

        $query = "SELECT 
                    ba.*,
                    bc.category_name,
                    bc.category_code
                  FROM " . $this->table_name . " ba
                  LEFT JOIN budget_categories bc ON ba.category_id = bc.id
                  WHERE ba.department_id = :department_id 
                  AND ba.fiscal_year = :fiscal_year
                  ORDER BY bc.category_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new budget allocation
     */
    public function createAllocation($department_id, $category_id, $fiscal_year, $allocated_amount, $created_by) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (department_id, category_id, fiscal_year, allocated_amount, created_by) 
                  VALUES (:department_id, :category_id, :fiscal_year, :allocated_amount, :created_by)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->bindParam(':allocated_amount', $allocated_amount);
        $stmt->bindParam(':created_by', $created_by);

        return $stmt->execute();
    }

    /**
     * Update budget allocation
     */
    public function updateAllocation($id, $allocated_amount) {
        $query = "UPDATE " . $this->table_name . " 
                  SET allocated_amount = :allocated_amount 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':allocated_amount', $allocated_amount);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Get budget summary by department
     */
    public function getBudgetSummary($fiscal_year = null) {
        if (!$fiscal_year) {
            $fiscal_year = date('Y');
        }

        $query = "SELECT 
                    d.dept_name,
                    SUM(ba.allocated_amount) as total_allocated,
                    SUM(ba.utilized_amount) as total_utilized,
                    SUM(ba.remaining_amount) as total_remaining
                  FROM " . $this->table_name . " ba
                  LEFT JOIN departments d ON ba.department_id = d.id
                  WHERE ba.fiscal_year = :fiscal_year
                  GROUP BY d.id, d.dept_name
                  ORDER BY total_allocated DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Format currency amount with proper suffix
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
