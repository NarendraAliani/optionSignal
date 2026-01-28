<?php
require_once __DIR__ . '/../src/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Database connected successfully.\n";

    $tables = ['settings', 'users', 'stocks', 'option_contracts', 'candles'];
    $missing = [];

    foreach ($tables as $table) {
        try {
            $db->query("SELECT 1 FROM $table LIMIT 1");
            echo "Table '$table' exists.\n";
        } catch (PDOException $e) {
            $missing[] = $table;
            echo "Table '$table' MISSING.\n";
        }
    }

    if (empty($missing)) {
        echo "\nDatabase integrity verification: PASS\n";
    } else {
        echo "\nDatabase integrity verification: FAIL. Missing tables: " . implode(', ', $missing) . "\n";
        echo "Please import sql/schema.sql\n";
    }

} catch (Exception $e) {
    echo "Database Check Fatal Error: " . $e->getMessage() . "\n";
}
