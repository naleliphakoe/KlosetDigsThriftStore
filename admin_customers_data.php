<?php
// admin_customers_data.php


session_start();
header('Content-Type: application/json');

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "klosetdigsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

$result = $conn->query("SELECT id, fullname, email, age, dob, gender, created_at FROM users ORDER BY created_at DESC");

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Query failed"]);
    exit();
}

$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode(["status" => "success", "data" => $customers]);

$conn->close();