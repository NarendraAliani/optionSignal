<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>Database Connection Test</h3>";
echo "Checking constants...<br>";

if (!file_exists(__DIR__ . '/../config/constants.php')) {
    die("ERROR: config/constants.php not found!");
}

require_once __DIR__ . '/../config/constants.php';

echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PASS: [HIDDEN] (Length: " . strlen(DB_PASS) . ")<br><hr>";

try {
    echo "Attempting Connection... ";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<strong style='color:green'>SUCCESS! Connected.</strong><br>";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables);
    
} catch (PDOException $e) {
    echo "<strong style='color:red'>FAILED.</strong><br>";
    echo "Error Message: " . $e->getMessage();
}
?>
