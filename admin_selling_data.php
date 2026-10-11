<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}
$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "KlosetDigsdb";
$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}
$conn->query("CREATE TABLE IF NOT EXISTS seller_profiles (
    user_id INT PRIMARY KEY,
    id_number VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    address VARCHAR(255) NULL,
    bank_name VARCHAR(255) NULL,
    bank_account VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$query = "SELECT sp.user_id, u.fullname AS customer_name, sp.id_number, sp.phone, sp.address, sp.bank_name, sp.bank_account
          FROM seller_profiles sp
          LEFT JOIN logindb.users u ON sp.user_id = u.id
          ORDER BY u.fullname ASC";
$result = $conn->query($query);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'user_id' => $row['user_id'],
            'customer_name' => $row['customer_name'] ?: 'Unknown',
            'id_number' => $row['id_number'] ?: '',
            'phone' => $row['phone'] ?: '',
            'address' => $row['address'] ?: '',
            'bank_name' => $row['bank_name'] ?: '',
            'bank_account' => $row['bank_account'] ?: '',
        ];
    }
}
$conn->close();
echo json_encode(['status' => 'success', 'data' => $rows]);
