<?php

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

$id = $_POST['id'] ?? null;

if (!$id || !ctype_digit((string)$id)) {
    echo json_encode(["status" => "error", "message" => "Invalid customer id"]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Unable to prepare delete"]);
    exit();
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Customer deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No customer found with that id"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Delete failed"]);
}

$stmt->close();
$conn->close();