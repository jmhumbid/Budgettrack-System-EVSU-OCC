<?php
/**
 * Database Installation Script
 * BudgetTrack System - EVSU Ormoc Campus
 * 
 * This script will create the database and all necessary tables.
 * Run this script once to set up the database.
 */

require_once __DIR__ . '/../config/database.php';

// Database configuration
$host = 'localhost';
$db_name = 'budgettrack_db';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>BudgetTrack Database Installation</h2>";
    echo "<p>Setting up database and tables...</p>";

    // Read and execute the schema file
    $schema_file = __DIR__ . '/../database/schema.sql';
    
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }

    $sql = file_get_contents($schema_file);
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $success_count++;
                echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                $error_count++;
                echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
            }
        }
    }

    echo "<hr>";
    echo "<h3>Installation Summary</h3>";
    echo "<p><strong>Successful statements:</strong> $success_count</p>";
    echo "<p><strong>Failed statements:</strong> $error_count</p>";
    
    if ($error_count == 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Database installation completed successfully!</p>";
        echo "<p>You can now:</p>";
        echo "<ul>";
        echo "<li>Login with admin@evsu.edu.ph / admin123</li>";
        echo "<li>Login with budget@evsu.edu.ph / budget123</li>";
        echo "<li>Access the user management system</li>";
        echo "<li>Create new user accounts and assign roles</li>";
        echo "</ul>";
        echo "<p><a href='../login.php' style='background: #800000; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Installation completed with errors. Please check the error messages above.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Database Installation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #800000;
            border-bottom: 2px solid #800000;
            padding-bottom: 10px;
        }
        h3 {
            color: #333;
            margin-top: 30px;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Installation results will be displayed here -->
    </div>
</body>
</html>
