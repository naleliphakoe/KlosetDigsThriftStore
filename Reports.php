<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: adminlogin.php');
    exit();
}

// Connect to productsdb for sales data
$db_server = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'klosetdigsdb';

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Create necessary tables
$conn->query("CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NULL,
    seller_name VARCHAR(255) NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

// Create customer_feedback table
$conn->query("CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    customer_name VARCHAR(255) NULL,
    rating INT NULL,
    complaints TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Connect to admindb for employee login data
$admin_conn = new mysqli($db_server, 'root', '', 'admindb');
if ($admin_conn->connect_error) {
    die('Admin database connection failed: ' . $admin_conn->connect_error);
}

// Create admin_login_logs table
$admin_conn->query("CREATE TABLE IF NOT EXISTS admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    employee_id VARCHAR(50) NOT NULL,
    fullname VARCHAR(255) NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    INDEX (admin_id),
    INDEX (login_time)
)");

// Ensure orders and minimal order schema exist (prevents fatal error on fresh DB)
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NULL,
    customer_name VARCHAR(255) NOT NULL,
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

// Query data from the orders table for real sales data
$result = $conn->query("SELECT COALESCE(SUM(total_price), 0) AS total_sales, COUNT(*) AS total_orders, COUNT(DISTINCT user_id) AS total_sellers FROM orders");

if ($result) {
    $summary = $result->fetch_assoc();
} else {
    $summary = ['total_sales' => 0, 'total_orders' => 0, 'total_sellers' => 0];
}

$total_sales = (float)$summary['total_sales'];
$total_orders = (int)$summary['total_orders'];
$total_sellers = (int)$summary['total_sellers'];
$commission_rate = 0.10;
$commission_amount = round($total_sales * $commission_rate, 2);
$net_earnings = round($total_sales - $commission_amount, 2);

$history = [];
$history_result = $conn->query("SELECT order_id, customer_name, total_price, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 10");
if ($history_result) {
    while ($row = $history_result->fetch_assoc()) {
        $history[] = $row;
    }
}

// Get employee performance data
$employee_performance = [];
$emp_result = $admin_conn->query("SELECT a.id, a.employee_id, a.fullname, a.created_at, a.is_active, 
                                           MAX(l.login_time) as last_login, COUNT(l.id) as login_count
                                    FROM admin_users a 
                                    LEFT JOIN admin_login_logs l ON a.id = l.admin_id
                                    GROUP BY a.id 
                                    ORDER BY last_login DESC");
if ($emp_result) {
    while ($row = $emp_result->fetch_assoc()) {
        $employee_performance[] = $row;
    }
}

// Get customer feedback data
$customer_feedback = [];
$feedback_result = $conn->query("SELECT id, order_id, customer_name, rating, complaints, created_at FROM customer_feedback ORDER BY created_at DESC LIMIT 20");
if ($feedback_result) {
    while ($row = $feedback_result->fetch_assoc()) {
        $customer_feedback[] = $row;
    }
}

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #bdc9e0;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .sidebar {
            width: 220px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
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
            transition: 0.3s;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #3e99e9;
        }
        .main-content {
            margin-left: 260px;
            padding: 24px;
            flex: 1;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }
        .topbar h1 {
            margin: 0;
            font-size: 28px;
        }
        .topbar input {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #d5d9f0;
            flex: 1;
            max-width: 320px;
        }
        .report-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        .tab {
            padding: 10px 18px;
            border: none;
            border-radius: 999px;
            background: #e7ecfd;
            color: #1a3cd3;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tab.active {
            background: #2f54eb;
            color: white;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(76, 87, 234, 0.12);
        }
        .summary-card h3 {
            margin: 0 0 12px;
            font-size: 16px;
            color: #475569;
        }
        .summary-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .summary-meta {
            color: #6b7280;
            font-size: 14px;
        }
        .report-content,
        .history-card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(76, 87, 234, 0.08);
            margin-bottom: 24px;
        }
        .report-content h3,
        .history-card h3 {
            margin-top: 0;
        }
        .report-content p {
            color: #475569;
            line-height: 1.7;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .history-table th,
        .history-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .history-table th {
            color: #6b7280;
            font-weight: 600;
        }
        .history-status {
            padding: 6px 10px;
            border-radius: 999px;
            background: #e7f5e6;
            color: #166534;
            font-size: 13px;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .rating-stars {
            color: #f59e0b;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background: #fee2e2;
            color: #7f1d1d;
        }
        .no-data {
            color: #9ca3af;
            text-align: center;
            padding: 20px;
        }
        @media (max-width: 900px) {
            body {
                display: block;
            }
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            .main-content {
                margin-left: 0;
            }
            .topbar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Thrift Admin</h2>
        <a href="admin.php">Home</a>
        <a href="Orders.html">Orders</a>
        <a href="Customers.php">Customers</a>
        <a href="products.php">Products</a>
        <a href="Reports.php" class="active">Reports</a>
        <a href="Settings.php">Settings</a>
        <a href="admin_logout.php">Logout</a>
    </div>
    <div class="main-content">
        <div class="topbar">
            <div>
                <h1>Reports</h1>
                
            </div>
            <input type="text" placeholder="Search reports...">
        </div>

        <div class="report-tabs">
            <button class="tab active" onclick="switchTab(event, 'sales')">Sales & Inventory</button>
            <button class="tab" onclick="switchTab(event, 'employee')">Employee Performance</button>
            <button class="tab" onclick="switchTab(event, 'customer')">Customer Reports</button>
        </div>

        <!-- Sales & Inventory Tab -->
        <div id="sales" class="tab-content active">
            <div class="summary-grid">
                <div class="summary-card">
                    <h3>Total Sales (Gross)</h3>
                    <div class="summary-value">R<?= number_format($total_sales, 2) ?></div>
                    <div class="summary-meta">Sum of all recorded sales</div>
                </div>
                <div class="summary-card">
                    <h3>Platform Commission (10%)</h3>
                    <div class="summary-value">R<?= number_format($commission_amount, 2) ?></div>
                    <div class="summary-meta">Commission retained by the platform</div>
                </div>
                <div class="summary-card">
                    <h3>Net Earnings</h3>
                    <div class="summary-value">R<?= number_format($net_earnings, 2) ?></div>
                    <div class="summary-meta">Amount after 10% commission</div>
                </div>
                <div class="summary-card">
                    <h3>Total Orders</h3>
                    <div class="summary-value"><?= $total_orders ?></div>
                    <div class="summary-meta">Recorded sales transactions</div>
                </div>
            </div>

            <div class="report-content">
                <h3>Commission Summary</h3>
                <p>All sales data is drawn from the orders table. The platform automatically takes a 10% commission fee from each order total. These reports reflect real-time transaction data.</p>
            </div>

            <div class="history-card">
                <h3>Recent Sales Orders</h3>
                <?php if (count($history) === 0): ?>
                    <p class="no-data">No sales transactions have been recorded yet.</p>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $sale): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sale['order_id']) ?></td>
                                    <td><?= htmlspecialchars($sale['customer_name'] ?: 'Unknown') ?></td>
                                    <td>R<?= number_format((float)$sale['total_price'], 2) ?></td>
                                    <td><?= htmlspecialchars($sale['order_status'] ?: 'Pending') ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($sale['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Employee Performance Tab -->
        <div id="employee" class="tab-content">
            <div class="history-card">
                <h3>Employee Performance & Login History</h3>
                <?php if (count($employee_performance) === 0): ?>
                    <p class="no-data">No employee data available.</p>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Last Login</th>
                                <th>Total Logins</th>
                                <th>Status</th>
                                <th>Account Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employee_performance as $emp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['employee_id']) ?></td>
                                    <td><?= htmlspecialchars($emp['fullname'] ?: 'N/A') ?></td>
                                    <td>
                                        <?php 
                                            if ($emp['last_login']) {
                                                echo date('M j, Y H:i', strtotime($emp['last_login']));
                                            } else {
                                                echo '<span style="color: #9ca3af;">Never</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><?= (int)$emp['login_count'] ?> logins</td>
                                    <td>
                                        <span class="status-badge <?= $emp['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $emp['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($emp['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Reports Tab -->
        <div id="customer" class="tab-content">
            <div class="history-card">
                <h3>Customer Ratings & Complaints</h3>
                <?php if (count($customer_feedback) === 0): ?>
                    <p class="no-data">No customer feedback has been recorded yet. Customer ratings and complaints will appear here.</p>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Rating</th>
                                <th>Complaints</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_feedback as $feedback): ?>
                                <tr>
                                    <td><?= htmlspecialchars($feedback['order_id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($feedback['customer_name'] ?: 'Anonymous') ?></td>
                                    <td>
                                        <span class="rating-stars">
                                            <?php 
                                                $rating = (int)($feedback['rating'] ?? 0);
                                                if ($rating > 0) {
                                                    echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . " ($rating/5)";
                                                } else {
                                                    echo 'No rating';
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($feedback['complaints'] ?: 'None') ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($feedback['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function switchTab(event, tabName) {
            event.preventDefault();
            
            // Hide all tab content
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show the selected tab content
            const tabContent = document.getElementById(tabName);
            if (tabContent) {
                tabContent.classList.add('active');
            }
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>