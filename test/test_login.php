<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

echo "<h2>BudgetTrack Login System Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Testing Database Connection</h3>";
try {
    $db = getDB();
    if ($db) {
        echo "✅ Database connection successful!<br>";
    } else {
        echo "❌ Database connection failed!<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

// Test 2: User Class Instantiation
echo "<h3>2. Testing User Class</h3>";
try {
    $user = new User();
    echo "✅ User class instantiated successfully!<br>";
} catch (Exception $e) {
    echo "❌ User class error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if default users exist
echo "<h3>3. Testing Default Users</h3>";
try {
    $users = $user->getAllUsers();
    if (count($users) > 0) {
        echo "✅ Found " . count($users) . " users in database:<br>";
        foreach ($users as $u) {
            echo "&nbsp;&nbsp;- " . $u['email'] . " (" . $u['role_name'] . ")<br>";
        }
    } else {
        echo "❌ No users found in database!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error fetching users: " . $e->getMessage() . "<br>";
}

// Test 4: Test Login Authentication
echo "<h3>4. Testing Login Authentication</h3>";
    $test_credentials = [
        ['budget@evsu.edu.ph', 'budget123'],
        ['school@evsu.edu.ph', 'school123'],
        ['office@evsu.edu.ph', 'office123']
    ];

foreach ($test_credentials as $cred) {
    $email = $cred[0];
    $password = $cred[1];
    
    try {
        $user_data = $user->authenticate($email, $password);
        if ($user_data) {
            echo "✅ Login successful for $email<br>";
            echo "&nbsp;&nbsp;- Name: " . $user_data['first_name'] . " " . $user_data['last_name'] . "<br>";
            echo "&nbsp;&nbsp;- Role: " . $user_data['role_name'] . "<br>";
            echo "&nbsp;&nbsp;- Department: " . ($user_data['dept_name'] ?? 'N/A') . "<br>";
        } else {
            echo "❌ Login failed for $email<br>";
        }
    } catch (Exception $e) {
        echo "❌ Login error for $email: " . $e->getMessage() . "<br>";
    }
}

// Test 5: Test Password Change
echo "<h3>5. Testing Password Change Functionality</h3>";
try {
    // Get first user for testing
    $users = $user->getAllUsers();
    if (count($users) > 0) {
        $test_user = $users[0];
        echo "Testing password change for: " . $test_user['email'] . "<br>";
        
        // Test with wrong current password
        $result = $user->changePassword($test_user['id'], 'wrong_password', 'new_password');
        if (!$result) {
            echo "✅ Correctly rejected wrong current password<br>";
        } else {
            echo "❌ Should have rejected wrong current password<br>";
        }
        
        // Test with correct current password
        $result = $user->changePassword($test_user['id'], 'admin123', 'new_test_password');
        if ($result) {
            echo "✅ Password change successful<br>";
            
            // Test login with new password
            $user_data = $user->authenticate($test_user['email'], 'new_test_password');
            if ($user_data) {
                echo "✅ Login with new password successful<br>";
            } else {
                echo "❌ Login with new password failed<br>";
            }
            
            // Reset password back to original
            $user->resetPassword($test_user['id'], 'admin123');
            echo "✅ Password reset to original<br>";
        } else {
            echo "❌ Password change failed<br>";
        }
    } else {
        echo "❌ No users found to test password change<br>";
    }
} catch (Exception $e) {
    echo "❌ Password change test error: " . $e->getMessage() . "<br>";
}

echo "<h3>Test Complete!</h3>";
echo "<p><a href='../login.php'>Go to Login Page</a></p>";
echo "<p><a href='../setup/install.php'>Run Database Installation</a></p>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System Test - BudgetTrack</title>
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
        a {
            color: #800000;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Test results will be displayed here -->
    </div>
</body>
</html>
