<?php
session_start();

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "KlosetDigsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$query = "SELECT p.id, p.name, p.price, p.brand, p.location, p.description, p.buyer_notes, p.created_at, u.fullname AS seller_name, u.email AS seller_email
          FROM products p
          LEFT JOIN logindb.users u ON p.user_id = u.id
          ORDER BY p.created_at DESC";
$result = $conn->query($query);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin — Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:#f3f6fb; 
            margin:0;
         }
        .container {
            max-width:1100px; 
            margin:24px auto; 
            padding:18px; 
        }
        table { 
            
            width:100%; 
            border-collapse: collapse; 
            background:white; 
            border-radius:8px; 
            overflow:hidden; 
        }
        th, td { 
            padding:10px 12px; 
            border-bottom:1px solid #eee; 
            text-align:left; 
        }
        th { 
            background:#eef2ff;
        }
        .small { 
            font-size:13px; 
            color:#475569 
        }
        .actions button { 
            margin-right:6px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Products</h2>
        <p class="small"></p>

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
                            <div class="small"><?= htmlspecialchars(substr($row['description'] ?? '', 0, 140)) ?></div>
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
