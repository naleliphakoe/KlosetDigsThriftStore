<?php
// run_migration.php
// Simple runner for SQL migrations in this folder. Run from CLI: php run_migration.php

$db_server = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'productsdb';

function out(string $s): void { echo $s . PHP_EOL; }

if (!file_exists(__DIR__ . '/add_is_sold.sql')) {
    out('Migration file not found: add_is_sold.sql');
    exit(1);
}

// Connect
$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    out('DB connection failed: ' . $conn->connect_error);
    exit(1);
}

// Check whether products table exists
$tableRes = $conn->query("SHOW TABLES LIKE 'products'");
if (!($tableRes && $tableRes->num_rows > 0)) {
    out('Products table not found. Nothing to migrate.');
    $conn->close();
    exit(0);
}

// Check if column exists
$colRes = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_sold'");
$row = $colRes->fetch_assoc();
$exists = intval($row['cnt']) > 0;
if ($exists) {
    out('Column `is_sold` already exists — nothing to do.');
    $conn->close();
    exit(0);
}

// Attempt to add the column
$alter = "ALTER TABLE products ADD COLUMN is_sold TINYINT(1) NOT NULL DEFAULT 0";
if ($conn->query($alter) === TRUE) {
    out('Migration applied successfully: is_sold column added.');
} else {
    out('Migration failed: ' . $conn->error);
}

$conn->close();

?>
