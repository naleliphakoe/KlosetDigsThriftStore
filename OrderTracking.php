<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = intval($_SESSION['user_id']);
$user_name = !empty($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest User';

header('Content-Type: text/html; charset=utf-8');

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "klosetdigsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die('Database connection failed');
}

// Ensure tables exist (same schema as admin endpoint)
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

// Fetch only this customer's orders
$stmt = $conn->prepare("SELECT 
        o.order_id,
        o.customer_name,
        o.customer_phone,
        o.street_address,
        o.city,
        o.postal_code,
        o.shipping_method,
        o.courier_option,
        o.order_status,
        o.total_price,
        o.created_at,
        GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS items
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id_fk
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC");

$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = [
        'order_id' => $row['order_id'],
        'customer_name' => $row['customer_name'],
        'customer_phone' => $row['customer_phone'],
        'address' => trim($row['street_address'] . ', ' . $row['city'] . ' ' . $row['postal_code']),
        'shipping_method' => $row['shipping_method'],
        'courier_option' => $row['courier_option'] ?: 'N/A',
        'order_status' => $row['order_status'],
        'total_price' => number_format((float)$row['total_price'], 2, '.', ''),
        'created_at' => $row['created_at'],
        'items' => $row['items'] ?: 'No items'
    ];
}

$stmt->close();
$conn->close();

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking</title>
    <style>
        body{
            font-family:Arial, sans-serif; 
            margin:0; 
            background:#bdc9e0;
        }
        .wrap{
            display:flex; 
            min-height:100vh;
        }
        .sidebar{
            width:220px; 
            background:#9eb3ec; 
            color:#fff; 
            padding:20px;
        }
        .sidebar h2{
            text-align:center; 
            margin-bottom:30px;
        }
        .sidebar a{
            display:block; 
            color:#fff; 
            text-decoration:none; 
            margin:10px 0; 
            padding:10px; 
            border-radius:8px;
        }
        .sidebar a:hover{
            background:#3e99e9;
        }
        .main{
            flex:1; 
            padding:20px;
        }
        .header{
            display:flex; 
            justify-content:space-between; 
            align-items:center; 
            margin-bottom:20px;
        }
        .header h2{
            color:#688bec;
        }
        table{
            width:100%; 
            border-collapse:collapse; 
            background:#fff; 
            border-radius:12px; 
            overflow:hidden;
        }
        th,td{
            padding:12px; 
            border-bottom:1px solid #eee; 
            vertical-align:top;
        }
        th{
            background:#e2e8f0; 
            text-align:left;
        }
        .muted{
            color:#64748b; 
            font-size:12px;
        }
        .status-pill{
            display:inline-block; 
            padding:6px 10px; 
            border-radius:999px; 
            background:#f1f5f9; 
            font-weight:700; 
            font-size:12px;
        }
        .status-Pending{
            background:#fff7ed; 
            color:#9a3412;
        }
        .status-Confirmed{
            background:#eff6ff; 
            color:#1d4ed8;
        }
        .status-Shipped{
            background:#f0fdf4; 
            color:#166534;
        }
        .status-Delivered{
            background:#ecfdf5; 
            color:#047857;
        }
        .status-Cancelled{
            background:#fee2e2; 
            color:#b91c1c;
        }
        .no-orders{
            background:#fff; 
            border-radius:12px; 
            padding:20px; 
            text-align:center;
        }
        @media (max-width: 680px){
        .wrap{flex-direction:column;
        }
        .sidebar{width:100%;
        }
        }
        .order-details-row{
            background:#fbfdff;
        }
        .details-wrap{
            display:flex; 
            flex-direction:column; 
            gap:6px;
        }
        .btn-link{
            border:none; 
            background:transparent; 
            color:#315f99; 
            text-decoration:underline; 
            cursor:pointer; 
            font-weight:600;
            padding:0;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="sidebar">
        <h2>Order Tracking</h2>
        <a href="Thrift.php">Home</a>
        <a href="viewCart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="main">
        <div class="header">
            <h2>Hi, <?php echo e($user_name); ?> 👋</h2>
            <div class="muted">Track your orders and view status.</div>
        </div>

        <?php if (count($orders) === 0): ?>
            <div class="no-orders">
                <h3 style="margin:0 0 6px;">No orders yet</h3>
                <div class="muted">When you place an order, it will show up here.</div>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Delivery Address</th>
                        <th>Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr class="order-details-row">
                            <td>
                                <button class="btn-link" type="button" 
                                        onclick="toggleOrderDetails('details-<?php echo e($o['order_id']); ?>')">
                                    <?php echo e($o['order_id']); ?>
                                </button>
                            </td>
                            <td class="muted"><?php echo e(date('M j, Y H:i', strtotime($o['created_at']))); ?></td>
                            <td>
                                <span class="status-pill status-<?php echo e($o['order_status']); ?>"><?php echo e($o['order_status']); ?></span>
                            </td>
                            <td>R<?php echo e($o['total_price']); ?></td>
                            <td><?php echo e($o['address']); ?></td>
                            <td><?php echo e($o['items']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleOrderDetails(id){
        // Minimal UI: table already shows items/address/status.
        // This function is kept for future expansion without breaking markup.
        const el = document.getElementById(id);
        if(!el) return;
        el.style.display = (el.style.display === 'none' ? 'block' : 'none');
    }
</script>
</body>
</html>

