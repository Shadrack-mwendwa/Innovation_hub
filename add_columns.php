<?php
require_once 'config/database.php';

echo "<h2>Adding Password Reset Columns</h2>";

try {
    // Add reset_token column
    $pdo->exec("ALTER TABLE users ADD reset_token VARCHAR(255) NULL");
    echo "✓ reset_token added<br>";
} catch(PDOException $e) {
    if(strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ reset_token already exists<br>";
    } else {
        echo "✗ Error: " . $e->getMessage() . "<br>";
    }
}

try {
    // Add reset_token_expiry column
    $pdo->exec("ALTER TABLE users ADD reset_token_expiry DATETIME NULL");
    echo "✓ reset_token_expiry added<br>";
} catch(PDOException $e) {
    if(strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ reset_token_expiry already exists<br>";
    } else {
        echo "✗ Error: " . $e->getMessage() . "<br>";
    }
}

try {
    // Add last_password_change column
    $pdo->exec("ALTER TABLE users ADD last_password_change DATETIME NULL");
    echo "✓ last_password_change added<br>";
} catch(PDOException $e) {
    if(strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ last_password_change already exists<br>";
    } else {
        echo "✗ Error: " . $e->getMessage() . "<br>";
    }
}

// Update existing users
try {
    $count = $pdo->exec("UPDATE users SET last_password_change = NOW() WHERE last_password_change IS NULL");
    echo "✓ Updated $count users<br>";
} catch(PDOException $e) {
    echo "✗ Update error: " . $e->getMessage() . "<br>";
}

echo "<h3>Done! Check your users table now.</h3>";
echo "<a href='forgot_password.php'>Test Forgot Password</a>";
?>