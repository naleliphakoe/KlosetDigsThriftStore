<?php
session_start();

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "klosetdigsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Some DB installs may not have all product columns yet; select only columns that are expected.
// (If others are missing, this prevents fatal “Unknown column ...” errors.)
$query = "SELECT 
            p.id,
            p.name,
            p.price,
            p.brand,
            p.location,
            p.created_at,
            u.fullname AS seller_name,
            u.email AS seller_email
          FROM products p
          LEFT JOIN logindb.users u ON p.user_id = u.id
          ORDER BY p.created_at DESC";

$result = $conn->query($query);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
<style>
/* copied styles from Products.html */
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
.status { 
    padding: 5px 10px; 
    border-radius: 5px; 
    font-size: 12px; 
}
.instock { 
    background: #2ecc71; 
    color: white; 
}
.sold { 
    background: #e74c3c; 
    color: white; 
}
.actions button { 
    border: none; 
    padding: 6px 10px;
    margin-right: 5px; 
    cursor: pointer; 
    border-radius: 4px; 
}
.edit { 
    background: #a8c3ee; 
}
.delete {
    background: #aeaddc;
}
.small { 
    font-size:13px; 
    color:#475569;
     }
</style>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Products</h2>
         <a href="admin.php">Home</a>
        <a href="Orders.html">Orders</a>
        <a href="Customers.php">Customers</a>
        <a href="products.php">Products</a>
        <a href="Reports.php">Reports</a>
        <a href="Settings.php">Settings</a>
        <a href="admin_logout.php">Logout</a>
    </div>

<!-- Main Content -->
    <div class="main">

        <!-- Header -->
        <div class="header">
            <input type="text" placeholder="Search...">
            <select>
                <option>clothes</option>
                <option>jewelry</option>
                <option>bags</option>
                <option>kitchen</option>
                <option>furniture</option>
                <option>pet supplies</option>
                <option>office supplies</option>
                <option>toys</option>
                <option>books</option>
            </select>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Brand</th>
                <th>Location</th>
                <th>Seller</th>
                <th>Created</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['name']) ?></strong>
<div class="small"><?= htmlspecialchars(substr(($row['description'] ?? ''), 0, 140)) ?></div>
                    </td>

                        <td>R<?= number_format((float)$row['price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['brand']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td><?= htmlspecialchars($row['seller_name'] ?: $row['seller_email'] ?: 'Unknown') ?></td>
                        <td class="small"><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No products found.</td></tr>
            <?php endif; ?>
        </table>
    </div>

</body>
</html>
