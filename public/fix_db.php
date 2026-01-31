<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../src/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "<h2>Database Schema Fixer</h2>";
    
    // Check 'token' in 'stocks'
    $check = $pdo->query("SHOW COLUMNS FROM stocks LIKE 'token'");
    if ($check->rowCount() == 0) {
        echo "Missing column 'token' in 'stocks'. Adding it...<br>";
        $pdo->exec("ALTER TABLE stocks ADD COLUMN token VARCHAR(50) DEFAULT NULL AFTER name");
        $pdo->exec("ALTER TABLE stocks ADD INDEX (token)");
        echo "<span style='color:green'>Success: Added 'token' column.</span><br>";
    } else {
        echo "<span style='color:blue'>Column 'token' already exists in 'stocks'. OK.</span><br>";
    }
    
    // Check 'symbol' index in 'option_contracts'
    // Good practice index if missing
    // $pdo->exec("ALTER TABLE option_contracts ADD INDEX (symbol)"); 
    
    echo "<h3>Done. Database is ready for import.</h3>";
    echo "<a href='import_local.php'>Go to Import Local</a>";

} catch (Exception $e) {
    echo "<span style='color:red'>Error: " . $e->getMessage() . "</span>";
}
