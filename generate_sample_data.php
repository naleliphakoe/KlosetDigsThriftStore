<?php
// This script generates sample data for testing Reports
// Run this once to populate the database with test data

$db_server = "localhost";
$db_user = "root";
$db_password = "";

// Connect to admindb
$admin_conn = new mysqli($db_server, $db_user, $db_password, "KlosetDigsdb");
if ($admin_conn->connect_error) {
    die("Admin DB connection failed: " . $admin_conn->connect_error);
}

// Connect to productsdb
$prod_conn = new mysqli($db_server, $db_user, $db_password, "productsdb");
if ($prod_conn->connect_error) {
    die("Products DB connection failed: " . $prod_conn->connect_error);
}

echo "<h2>Generating Sample Data for Reports</h2>";

// Create admin_login_logs table if needed
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

// Create customer_feedback table if needed
$prod_conn->query("CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    customer_name VARCHAR(255) NULL,
    rating INT NULL,
    complaints TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (order_id),
    INDEX (created_at)
)");

// Get existing admins
$admins = [];
$result = $admin_conn->query("SELECT id, employee_id, fullname FROM admin_users LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Add sample login logs for existing admins
if (!empty($admins)) {
    echo "<p>Adding sample login logs...</p>";
    
    foreach ($admins as $admin) {
        // Add 3-5 random login entries for each admin
        $login_count = rand(3, 5);
        
        for ($i = 0; $i < $login_count; $i++) {
            $days_ago = rand(1, 30);
            $hours_ago = rand(0, 23);
            $minutes_ago = rand(0, 59);
            
            $login_time = date('Y-m-d H:i:s', strtotime("-$days_ago days -$hours_ago hours -$minutes_ago minutes"));
            
            $stmt = $admin_conn->prepare("INSERT INTO admin_login_logs (admin_id, employee_id, fullname, login_time) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $admin['id'], $admin['employee_id'], $admin['fullname']);
                $stmt->bind_param("i", $admin['id']);
                $stmt->bind_param("s", $admin['employee_id']);
                $stmt->bind_param("s", $admin['fullname']);
                $stmt->bind_param("s", $login_time);
                
                // Use a simpler approach
                $query = "INSERT IGNORE INTO admin_login_logs (admin_id, employee_id, fullname, login_time) 
                         VALUES ({$admin['id']}, '{$admin_conn->real_escape_string($admin['employee_id'])}', 
                                 '{$admin_conn->real_escape_string($admin['fullname'])}', '$login_time')";
                $admin_conn->query($query);
            }
        }
    }
    echo "<p>✓ Added " . count($admins) . " employees with login history</p>";
}

// Add sample customer feedback
echo "<p>Adding sample customer feedback...</p>";

$sample_feedback = [
    ['order_id' => 1, 'customer_name' => 'John Smith', 'rating' => 5, 'complaints' => 'Excellent service and fast delivery!'],
    ['order_id' => 2, 'customer_name' => 'Maria Garcia', 'rating' => 4, 'complaints' => 'Good quality, but packaging could be better'],
    ['order_id' => 3, 'customer_name' => 'Ahmed Hassan', 'rating' => 3, 'complaints' => 'Item arrived with minor damage'],
    ['order_id' => 4, 'customer_name' => 'Sarah Johnson', 'rating' => 5, 'complaints' => 'Very satisfied with purchase'],
    ['order_id' => 5, 'customer_name' => 'Michael Chen', 'rating' => 2, 'complaints' => 'Product not as described, different color than shown'],
    ['order_id' => 6, 'customer_name' => 'Anna Kowalski', 'rating' => 4, 'complaints' => 'Good item, delivery was slightly delayed'],
    ['order_id' => 7, 'customer_name' => 'David Lopez', 'rating' => 5, 'complaints' => 'Perfect! Exactly what I wanted'],
    ['order_id' => 8, 'customer_name' => 'Emma Brown', 'rating' => 1, 'complaints' => 'Poor quality, fell apart after 1 week of use'],
    ['order_id' => 9, 'customer_name' => 'James Wilson', 'rating' => 4, 'complaints' => 'Nice product, good price'],
    ['order_id' => 10, 'customer_name' => 'Lisa Anderson', 'rating' => 5, 'complaints' => 'Fantastic! Highly recommend'],
];

foreach ($sample_feedback as $feedback) {
    $days_ago = rand(1, 60);
    $created_at = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
    
    $query = "INSERT IGNORE INTO customer_feedback (order_id, customer_name, rating, complaints, created_at) 
             VALUES ({$feedback['order_id']}, '{$prod_conn->real_escape_string($feedback['customer_name'])}', 
                     {$feedback['rating']}, '{$prod_conn->real_escape_string($feedback['complaints'])}', '$created_at')";
    $prod_conn->query($query);
}

echo "<p>✓ Added " . count($sample_feedback) . " customer feedback entries</p>";

// Close connections
$admin_conn->close();
$prod_conn->close();

echo "<p style='color: green; font-weight: bold;'>✓ Sample data generation complete!</p>";
echo "<p><a href='Reports.php'>View Reports</a></p>";
?>
