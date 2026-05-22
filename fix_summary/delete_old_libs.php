<?php
/**
 * Delete Old LIBs Script
 * This script deletes selected old LIBs to prevent confusion
 */

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: check_lib_items_source.php');
    exit;
}

$deleteLibs = $_POST['delete_libs'] ?? [];

if (empty($deleteLibs)) {
    echo "<p style='color: red;'>No LIBs selected for deletion.</p>";
    echo "<p><a href='check_lib_items_source.php'>← Back</a></p>";
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    $deletedCount = 0;
    $errors = [];
    
    foreach ($deleteLibs as $libId) {
        try {
            // Delete items first
            $deleteItemsQuery = "DELETE FROM line_item_budget_items WHERE lib_id = ?";
            $stmt = $db->prepare($deleteItemsQuery);
            $stmt->execute([$libId]);
            
            // Delete LIB
            $deleteLibQuery = "DELETE FROM line_item_budgets WHERE id = ?";
            $stmt = $db->prepare($deleteLibQuery);
            $stmt->execute([$libId]);
            
            $deletedCount++;
        } catch (Exception $e) {
            $errors[] = "Failed to delete LIB ID $libId: " . $e->getMessage();
        }
    }
    
    $db->commit();
    
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; background: #800000; color: white; text-decoration: none; border-radius: 5px; }
    </style>";
    
    echo "<h1 style='color: #800000;'>🗑️ Delete Old LIBs - Results</h1>";
    echo "<hr>";
    
    if ($deletedCount > 0) {
        echo "<div class='success'>";
        echo "<h3>✅ Success!</h3>";
        echo "<p>Successfully deleted <strong>$deletedCount</strong> LIB(s).</p>";
        echo "</div>";
    }
    
    if (!empty($errors)) {
        echo "<div class='error'>";
        echo "<h3>❌ Errors:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<p><a href='check_lib_items_source.php' class='btn'>← Back to Diagnostic</a></p>";
    echo "<p><a href='pages/lib.php' class='btn'>← Back to LIB Page</a></p>";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<div class='error'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
