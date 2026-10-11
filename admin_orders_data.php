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

// Create orders table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NULL,
    customer_phone VARCHAR(20) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    shipping_method VARCHAR(50) NOT NULL,
    courier_option VARCHAR(100) NULL,
    order_status VARCHAR(50) DEFAULT 'Pending',
    total_price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create order_items table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id_fk VARCHAR(20) NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id_fk) REFERENCES orders(order_id) ON DELETE CASCADE
)");

// Fetch orders with their items
$query = "SELECT o.id, o.order_id, o.customer_name, o.customer_email, o.customer_phone, 
                 o.street_address, o.city, o.postal_code, o.shipping_method, o.courier_option, 
                 o.order_status, o.total_price, o.created_at,
                 GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS items
          FROM orders o
          LEFT JOIN order_items oi ON o.order_id = oi.order_id_fk
          GROUP BY o.order_id
          ORDER BY o.created_at DESC";
$result = $conn->query($query);
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'order_id' => $row['order_id'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'] ?: 'N/A',
            'customer_phone' => $row['customer_phone'],
            'street_address' => $row['street_address'],
            'city' => $row['city'],
            'postal_code' => $row['postal_code'],
            'shipping_method' => $row['shipping_method'],
            'courier_option' => $row['courier_option'] ?: 'N/A',
            'items' => $row['items'] ?: 'No items',
            'total_price' => number_format((float)$row['total_price'], 2),
            'order_status' => $row['order_status'],
            'created_at' => date('M j, Y H:i', strtotime($row['created_at'])),
        ];
    }
}
$conn->close();
echo json_encode(['status' => 'success', 'data' => $orders]);
