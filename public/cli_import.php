<?php
// Wrapper to run import logic without POST
$_POST['run'] = true;

// Mock HTML output to avoid cluttering CLI too much, or just capture it
ob_start();
require_once __DIR__ . '/import_local.php';
$output = ob_get_clean();

// Extract simple text messages from the HTML output if possible
$output = strip_tags($output, '<br>');
$output = str_replace('<br>', "\n", $output);
echo "Import Output:\n" . trim($output) . "\n";

// Check final count
require_once __DIR__ . '/../src/Database.php';
$pdo = Database::getInstance()->getConnection();
$count = $pdo->query("SELECT COUNT(*) FROM option_contracts")->fetchColumn();
echo "Final Option Contracts Count: $count\n";
