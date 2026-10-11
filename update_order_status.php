<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$orderId = isset($_POST['orderId']) ? $_POST['orderId'] : '';
$newStatus = isset($_POST['status']) ? $_POST['status'] : '';

if (empty($orderId) || empty($newStatus)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "productsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Allowed status values
$allowed_statuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];

if (!in_array($newStatus, $allowed_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order status']);
    exit();
}

// Update order status
$stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ss", $newStatus, $orderId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // If order is cancelled, restore all ordered products back to available
        if ($newStatus === 'Cancelled') {
            // Fetch all product ids that belong to this order
            $itemRes = $conn->prepare("SELECT DISTINCT product_id FROM order_items WHERE order_id_fk = ?");
            if ($itemRes) {
                $itemRes->bind_param("s", $orderId);
                $itemRes->execute();
                $items = [];
                $itemRes->bind_result($pid);
                while ($itemRes->fetch()) {
                    $pid = intval($pid);
                    if ($pid > 0) $items[] = $pid;
                }
                $itemRes->close();

                if (!empty($items)) {
                    // Build a safe IN clause
                    $in = implode(',', array_fill(0, count($items), '?'));
                    $types = str_repeat('i', count($items));
                    $restoreStmt = $conn->prepare("UPDATE products SET is_sold = 0 WHERE id IN ($in)");
                    if ($restoreStmt) {
                        $restoreStmt->bind_param($types, ...$items);
                        $restoreStmt->execute();
                        $restoreStmt->close();
                    }
                }
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Order status updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();

