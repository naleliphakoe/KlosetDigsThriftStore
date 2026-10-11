<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
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

// Create customer_feedback table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    customer_name VARCHAR(255) NULL,
    rating INT NULL,
    complaints TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (order_id),
    INDEX (created_at)
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit_feedback') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $customer_name = trim($_POST['customer_name'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $complaints = trim($_POST['complaints'] ?? '');
        
        if ($order_id <= 0 || $customer_name === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Order ID and customer name are required']);
            exit();
        }
        
        if ($rating < 0 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Rating must be between 0 and 5']);
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO customer_feedback (order_id, customer_name, rating, complaints) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isds", $order_id, $customer_name, $rating, $complaints);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Feedback submitted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to submit feedback']);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

$conn->close();
?>
