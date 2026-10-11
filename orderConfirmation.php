<?php
session_start();
// orderConfirmation.php
// Shows order details after successful order creation. Expects ?orderId=ORD-...

$orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';
// fallback to last created order stored in session (set by address.php)
if (empty($orderId) && isset($_SESSION['last_order_id'])) {
    $orderId = $_SESSION['last_order_id'];
}
if (empty($orderId)) {
    echo "<p>Missing order ID.</p>";
    exit;
}

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "klosetdigsdb";
$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    echo "<p>Database connection failed.</p>";
    exit;
}

// Fetch order
$stmt = $conn->prepare("SELECT order_id, user_id, customer_name, customer_email, customer_phone, street_address, city, postal_code, shipping_method, courier_option, order_status, total_price, created_at FROM orders WHERE order_id = ? LIMIT 1");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
if (!$order) {
    echo "<p>Order not found.</p>";
    exit;
}
$stmt->close();

// Fetch items
$itemStmt = $conn->prepare("SELECT product_id, product_name, price, quantity FROM order_items WHERE order_id_fk = ?");
$itemStmt->bind_param("s", $orderId);
$itemStmt->execute();
$itemsRes = $itemStmt->get_result();
$items = [];
while ($r = $itemsRes->fetch_assoc()) {
    $items[] = $r;
}
$itemStmt->close();
$conn->close();

function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
// clear last order id from session so the page won't reuse it accidentally
if (isset($_SESSION['last_order_id'])) {
    unset($_SESSION['last_order_id']);
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order Confirmation</title>
    <style>
        body{
            font-family:Arial,Helvetica,sans-serif;
            background:#f3f6fb;
            padding:24px;
        }
        .card{
            max-width:800px;
            margin:40px auto;
            background:#fff;
            padding:20px;
            border-radius:8px;
            box-shadow:0 6px 18px rgba(0,0,0,0.08);
        }
        h1{
            color:#2b6cb0;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:12px;
        }
        th,td{
            padding:8px;
            border-bottom:1px solid #eee;
            text-align:left;
        }
        .tr,td{
            text-align:right;
        }
        .muted{
            color:#6b7280;
            font-size:14px;
        }
        .actions{
            margin-top:16px;
        }
        .btn{
            display:inline-block;
            padding:10px 14px;
            border-radius:6px;
            text-decoration:none;
            color:#fff;
            background:#2b6cb0;
        }
        
    </style>
</head>
<body>
    <div class="card">
        <h1>Order Confirmed</h1>
        <p class="muted">Thank you, your order has been placed successfully. A copy of this confirmation has been sent to the admin panel.</p>
        <h2>Order: <?php echo esc($order['order_id']); ?></h2>
        <p><strong>Status:</strong> <?php echo esc($order['order_status']); ?> &nbsp; <strong>Date:</strong> <?php echo esc(date('M j, Y H:i', strtotime($order['created_at']))); ?></p>

        <h3>Customer</h3>
        <p><?php echo esc($order['customer_name']); ?><?php if(!empty($order['customer_email'])) echo ' &middot; '.esc($order['customer_email']); ?></p>
        <p class="muted">Phone: <?php echo esc($order['customer_phone']); ?></p>

        <h3>Delivery Address</h3>
        <p><?php echo esc($order['street_address']) . ', ' . esc($order['city']) . ' ' . esc($order['postal_code']); ?></p>
        <p class="muted">Shipping: <?php echo esc($order['shipping_method']); ?> <?php if(!empty($order['courier_option'])) echo ' - '.esc($order['courier_option']); ?></p>

        <h3>Items</h3>
        <table>
            <thead>
                <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach($items as $it): ?>
                <tr>
                    <td><?php echo esc($it['product_name']); ?></td>
                    <td>R<?php echo number_format($it['price'],2); ?></td>
                    <td><?php echo intval($it['quantity']); ?></td>
                    <td>R<?php echo number_format($it['price'] * $it['quantity'],2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="3" ><strong>Total</strong></td><td><strong>R<?php echo number_format((float)$order['total_price'],2); ?></strong></td></tr>
            </tfoot>
        </table>

        <div class="actions">
            <a class="btn" href="viewCart.php">Continue shopping</a>
            <a class="btnPrint" href="print.php?orderId=<?php echo urlencode($orderId); ?>" style="background:#6b7280;margin-left:8px">Print</a>
        </div>
    </div>
</body>
</html>