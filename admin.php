<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: adminlogin.php');
    exit();
}

$db_server = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'KlosetDigsdb';

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$today = date('Y-m-d');

// Daily Sales (sum of orders created today)
$daily_result = $conn->query("SELECT COALESCE(SUM(total_price), 0) AS daily_sales FROM orders WHERE DATE(created_at) = '$today'");
$daily_row = $daily_result->fetch_assoc();
$daily_sales = (float)$daily_row['daily_sales'];

// Total Sales (sum of all orders)
$total_result = $conn->query("SELECT COALESCE(SUM(total_price), 0) AS total_sales FROM orders");
$total_row = $total_result->fetch_assoc();
$total_sales = (float)$total_row['total_sales'];

// Total Orders
$orders_result = $conn->query("SELECT COUNT(*) AS total_orders FROM orders");
$orders_row = $orders_result->fetch_assoc();
$total_orders = (int)$orders_row['total_orders'];

// Total Customers (distinct user_ids from orders or count from users)
$customers_result = $conn->query("SELECT COUNT(DISTINCT user_id) AS total_customers FROM orders WHERE user_id IS NOT NULL");
$customers_row = $customers_result->fetch_assoc();
$total_customers = (int)$customers_row['total_customers'];

// Total Products
$products_result = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$products_row = $products_result->fetch_assoc();
$total_products = (int)$products_row['total_products'];

// Recent Orders (latest 5)
$recent_orders = [];
$recent_result = $conn->query("SELECT order_id, customer_name, total_price, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
if ($recent_result) {
    while ($row = $recent_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #bdc9e0; 
            display: flex;
        }

        
        .sidebar {
            width: 220px;
            height: 100vh;
            background-color: #9eb3ec;
            color: white;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
        }

        .sidebar a:hover {
            background-color: #3e99e9;
        }

        /* Main */
        .main {
            flex: 1;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h2 {
            color: #688bec;
        }

        .header input {
            padding: 10px;
            width: 900px;
            border-radius: 70px;
            margin-right: 100px;
            border: none;
        }

        /* Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .card h3 {
            color: #688bec;
            margin-bottom: 10px;
        }

        .sales {
            font-size: 18px;
            font-weight: bold;
            color: #555;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #e2e8f0;
            text-align: left;
        }

        /* Buttons */
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color: rgb(255, 255, 255);
        }

        .btn-edit {
            background: #a8c3ee;
        }

        .btn-delete {
            background: #aeaddc;
        }
        .btn-add {
            background: #9cb2e6;
            color: white;
            margin-top: 10px;
        }

        .btn:hover {
            opacity: 0.8;
        }

        /* Waiting List */
        .item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>

<body>

<script>
// Enable searching across recent orders table.
(function(){
  const search = document.getElementById('adminSearch');
  if (!search) return;

  const rows = () => Array.from(document.querySelectorAll('table tr'));

  function getSearchableText(tr){
    const cells = tr.querySelectorAll('td');
    return Array.from(cells).map(td => td.textContent || '').join(' ').toLowerCase();
  }

  search.addEventListener('input', () => {
    const q = (search.value || '').toLowerCase().trim();
    rows().forEach(tr => {
      // skip header rows without td
      if (tr.querySelectorAll('td').length === 0) return;
      const match = !q || getSearchableText(tr).includes(q);
      tr.style.display = match ? '' : 'none';
    });
  });
})();
</script>


    <!-- Sidebar -->
    <div class="sidebar">
        <h2><i>KlosetDigs</i> Admin</h2>
<a href="admin.php">Home</a>
<a href="Orders.html">Orders</a>
        <a href="Customers.php">Customers</a>
        <a href="adminproducts.php">Products</a>
        <a href="Reports.php">Reports</a>
        <a href="Settings.php">Settings</a>
        <a href="admin_logout.php">Logout</a>
        <a href="adminlogin.php" style="display:none;">Login</a>
    </div>

    <!-- Main Content -->
    <div class="main">

        <!-- Header -->
        <div class="header">
            <h2>Dashboard</h2>
            <input type="text" id="adminSearch" placeholder="Search orders..." aria-label="Search orders">
        </div>

        <!-- Stats Cards -->
        <div class="cards">
            <div class="card">
                <h3>Daily Sales</h3>
                <p class="sales">R<?= number_format($daily_sales, 2) ?></p>
            </div>

            <div class="card">
                <h3>Total Sales</h3>
                <p class="sales">R<?= number_format($total_sales, 2) ?></p>
            </div>

            <div class="card">
                <h3>Orders</h3>
                <p><?= $total_orders ?></p>
            </div>

            <div class="card">
                <h3>Customers</h3>
                <p><?= $total_customers ?></p>
            </div>

            <div class="card">
                <h3>Products</h3>
                <p><?= $total_products ?></p>
            </div>
        </div>

        <!-- Recent Orders -->
        <h3>Recent Orders</h3>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php if (!empty($recent_orders)): ?>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td>R<?= number_format((float)$order['total_price'], 2) ?></td>
                    <td><?= htmlspecialchars($order['order_status']) ?></td>
                    <td><?= date('M j, Y H:i', strtotime($order['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #999;">No orders found</td>
                </tr>
            <?php endif; ?>
        </table>

    </div>

</body>
</html>
