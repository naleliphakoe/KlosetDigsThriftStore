<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $next = urlencode('Selling.php');
    header("Location: login.php?next=$next");
    exit();
}

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "KlosetDigsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Ensure tables exist with sold status
$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    brand VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'Other',
    gender VARCHAR(20) NOT NULL DEFAULT 'Both',
    size VARCHAR(20) NOT NULL DEFAULT 'One Size',
    shoe_size VARCHAR(20) NOT NULL DEFAULT 'N/A',
    buyer_notes TEXT NULL,
    seller_id_number VARCHAR(100) NULL,
    seller_phone VARCHAR(50) NULL,
    seller_address VARCHAR(255) NULL,
    bank_name VARCHAR(255) NULL,
    bank_account VARCHAR(255) NULL,
    is_sold TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) NOT NULL DEFAULT 'Other'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS gender VARCHAR(20) NOT NULL DEFAULT 'Both'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS size VARCHAR(20) NOT NULL DEFAULT 'One Size'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS shoe_size VARCHAR(20) NOT NULL DEFAULT 'N/A'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS seller_id_number VARCHAR(100) NULL");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS seller_phone VARCHAR(50) NULL");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS seller_address VARCHAR(255) NULL");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS bank_name VARCHAR(255) NULL");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS bank_account VARCHAR(255) NULL");

$conn->query("CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS seller_profiles (
    user_id INT PRIMARY KEY,
    id_number VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    address VARCHAR(255) NULL,
    bank_name VARCHAR(255) NULL,
    bank_account VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Guest User');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? '');
$user_id = intval($_SESSION['user_id']);

$success = '';
$errors = [];
$profile_success = '';
$product_success = '';
$profile_errors = [];
$product_errors = [];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

// Handle Delete
if ($action === 'delete' && $product_id > 0) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    if ($stmt->execute()) {
        $success = 'Product deleted successfully.';
    } else {
        $errors[] = 'Unable to delete product.';
    }
    $stmt->close();
}

// Handle Mark as Sold
if ($action === 'mark_sold' && $product_id > 0) {
    $stmt = $conn->prepare("UPDATE products SET is_sold = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    if ($stmt->execute()) {
        $success = 'Product marked as sold.';
    } else {
        $errors[] = 'Unable to update product.';
    }
    $stmt->close();
}

// Handle Mark as Available
if ($action === 'mark_available' && $product_id > 0) {
    $stmt = $conn->prepare("UPDATE products SET is_sold = 0 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    if ($stmt->execute()) {
        $success = 'Product marked as available.';
    } else {
        $errors[] = 'Unable to update product.';
    }
    $stmt->close();
}

// Handle save of personal seller information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_profile') {
    $seller_id_number = trim($_POST['seller_id_number'] ?? '');
    $seller_phone = trim($_POST['seller_phone'] ?? '');
    $seller_address = trim($_POST['seller_address'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');

    if ($seller_id_number === '') $profile_errors[] = 'ID number is required.';
    if ($seller_phone === '') $profile_errors[] = 'Phone number is required.';
    if ($seller_address === '') $profile_errors[] = 'Home address is required.';
    if ($bank_name === '') $profile_errors[] = 'Bank name is required.';
    if ($bank_account === '') $profile_errors[] = 'Bank account number is required.';

    if (empty($profile_errors)) {
        $stmt = $conn->prepare("INSERT INTO seller_profiles (user_id, id_number, phone, address, bank_name, bank_account) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id_number = VALUES(id_number), phone = VALUES(phone), address = VALUES(address), bank_name = VALUES(bank_name), bank_account = VALUES(bank_account)");
        if ($stmt) {
            $stmt->bind_param("isssss", $user_id, $seller_id_number, $seller_phone, $seller_address, $bank_name, $bank_account);
            if ($stmt->execute()) {
                $profile_success = 'Personal information saved successfully.';
            } else {
                $profile_errors[] = 'Unable to save personal information. Please try again.';
            }
            $stmt->close();
        } else {
            $profile_errors[] = 'Unable to prepare the profile save request.';
        }
    }
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_product') {
    $product_name = trim($_POST['product_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $shoe_size = trim($_POST['shoe_size'] ?? '');
    $buyer_notes = trim($_POST['buyer_notes'] ?? '');

    if ($product_name === '') $product_errors[] = 'Product name is required.';
    if ($location === '') $product_errors[] = 'Location is required.';
    if ($price === '' || !is_numeric($price) || floatval($price) < 0) $product_errors[] = 'Please enter a valid price.';
    if ($brand === '') $product_errors[] = 'Brand is required.';
    if ($category === '') $product_errors[] = 'Category is required.';
    if ($gender === '') $product_errors[] = 'Gender is required.';

    if (empty($product_errors)) {
        $price_value = number_format((float)$price, 2, '.', '');
        $stmt = $conn->prepare("INSERT INTO products (user_id, name, location, price, brand, category, gender, size, shoe_size, buyer_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issdssssss", $user_id, $product_name, $location, $price_value, $brand, $category, $gender, $size, $shoe_size, $buyer_notes);
            if ($stmt->execute()) {
                $product_id = $stmt->insert_id;

                // Handle file uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $total_files = count($_FILES['images']['name']);
                    if ($total_files > 10) {
                        $product_errors[] = "Maximum 10 images allowed.";
                    } else {
                        $upload_dir = "uploads/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        for ($i = 0; $i < $total_files; $i++) {
                            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                                $tmp = $_FILES['images']['tmp_name'][$i];
                                $name = time() . "_" . rand(1000, 9999) . "_" . basename($_FILES['images']['name'][$i]);
                                $path = $upload_dir . $name;
                                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                                if (in_array($ext, $allowed)) {
                                    if (move_uploaded_file($tmp, $path)) {
                                        $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                                        $img_stmt->bind_param("is", $product_id, $path);
                                        $img_stmt->execute();
                                        $img_stmt->close();
                                    }
                                } else {
                                    $product_errors[] = "Invalid file type: $name. Only jpg, jpeg, png, gif, bmp, webp allowed.";
                                }
                            }
                        }
                    }
                }
                if (empty($product_errors)) {
                    $product_success = 'Product listed successfully.';
                    $_POST = [];
                }
            } else {
                $product_errors[] = 'Unable to save the product. Please try again.';
            }
            $stmt->close();
        } else {
            $product_errors[] = 'Unable to prepare the save request.';
        }
    }
}

// Load seller profile
$profile = [
    'id_number' => '',
    'phone' => '',
    'address' => '',
    'bank_name' => '',
    'bank_account' => ''
];
$stmt = $conn->prepare("SELECT id_number, phone, address, bank_name, bank_account FROM seller_profiles WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($profile_id_number, $profile_phone, $profile_address, $profile_bank_name, $profile_bank_account);
    if ($stmt->fetch()) {
        $profile = [
            'id_number' => $profile_id_number,
            'phone' => $profile_phone,
            'address' => $profile_address,
            'bank_name' => $profile_bank_name,
            'bank_account' => $profile_bank_account,
        ];
    }
    $stmt->close();
}

$product_name = $_POST['product_name'] ?? '';
$location = $_POST['location'] ?? '';
$price = $_POST['price'] ?? '';
$brand = $_POST['brand'] ?? '';
$category = $_POST['category'] ?? '';
$gender = $_POST['gender'] ?? '';
$size = $_POST['size'] ?? '';
$shoe_size = $_POST['shoe_size'] ?? '';
$buyer_notes = $_POST['buyer_notes'] ?? '';

// Load user's products
$user_products = [];
$result = $conn->query("SELECT * FROM products WHERE user_id = $user_id ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['images'] = [];
        $img_result = $conn->query("SELECT image_path FROM product_images WHERE product_id = " . $row['id']);
        if ($img_result) {
            while ($img_row = $img_result->fetch_assoc()) {
                $row['images'][] = $img_row['image_path'];
            }
        }
        $user_products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sell Your Product</title>
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, sans-serif; 
        background: #eef2ff; 
        color: #1f2937; 
    }
    .page-wrapper {
        max-width: 1200px; 
        margin: 0 auto; 
        padding: 24px; 
    }
    .page-wrapper::before {
        content: '';
        position: absolute;
        width: 1300px;
        height: 1300px;
        background: url('Logo.png') center center no-repeat;
        background-size: contain;
        opacity: 0.08;
        pointer-events: none;
        z-index: 0;
    }
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 24px; 
    }
        header h1 {
        margin: 0;
        font-size: 32px;
    }
        header p {
        margin: 8px 0 0;
        color: #4b5563;
        max-width: 680px;
    }
        .form-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 16px 45px rgba(15, 23, 42, 0.08);
        padding: 28px;
        margin-bottom: 28px;
    }
        .form-section h2 {
        margin-top: 0;
        font-size: 22px;
        margin-bottom: 18px;
    }
        label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
    }
        input,
        textarea,
        select {
        display: block;
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        font-size: 14px;
        margin-bottom: 18px;
        background: #f8fafc;
    }
        textarea {
        min-height: 120px;
        resize: vertical;
    }
        .file-input-wrapper {
        position: relative;
        margin-bottom: 18px;
    }
        .file-input-label {
        display: block;
        padding: 12px 14px;
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        background: #f8fafc;
        transition: 0.2s;
    }
        .file-input-label:hover {
        border-color: #4338ca;
        background: #f0ecff;
    }
        .file-input-wrapper input[type="file"] { 
        display: none; 
    }
        .file-names {
        font-size: 12px;
        color: #475569;
        margin-top: 8px;
    }
        .button-row {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }
        button { 
        border: none;
        border-radius: 14px;
        padding: 14px 24px;
        color: white;
        background: #4338ca;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
    }
        button:hover {
        background: #3730a3; 
    }
        .btn-secondary {
        background: #6b7280;
    }
        .btn-secondary:hover {
        background: #4b5563;
    }
        .btn-danger {
        background: #ef4444;
    }
        .btn-danger:hover { 
        background: #dc2626; 
    }
        .btn-success { 
        background: #10b981;
    }
        .btn-success:hover { 
        background: #059669;
    }
        .message {
        padding: 14px 18px;
        border-radius: 14px;
        margin-bottom: 18px;
    }
        .message.success {
        background: #e0f2fe;
        color: #0c4a6e;
        border: 1px solid #7dd3fc;
    }
        .message.error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
        .products-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 16px 45px rgba(15, 23, 42, 0.08);
        padding: 28px;
    }
        .products-section h2 {
        margin-top: 0;
        font-size: 22px;
        margin-bottom: 24px;
    }
        .product-card {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        position: relative;
    }
        .product-card.sold {
        opacity: 0.7;
        background: #f9fafb;
    }
        .sold-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: #ef4444;
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
    }
        .product-images {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
        .product-images img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
        .product-card h3 {
        margin: 0 0 12px;
        font-size: 20px;
    }
        .product-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 14px;
        color: #4b5563;
        font-size: 14px;
    }
        .product-desc {
        color: #4b5563;
        line-height: 1.6;
        margin-bottom: 12px;
    }
        .product-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 16px;
    }
        .product-actions form {
        display: inline;
    }
        .product-actions button {
        padding: 10px 16px;
        font-size: 14px;
        margin: 0;
    }
        .empty-state {
        text-align: center;
        color: #9ca3af;
        padding: 40px 20px;
    }
        .empty-state p {
        margin: 0;
    }
        .back-button {
        display: inline-block;
        margin-bottom: 20px;
        padding: 10px 16px;
        background: #6b7280;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: 0.2s;
    }
        .back-button:hover {
        background: #4b5563;
    }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <a href="Thrift.php" class="back-button">←</a>
        <header>
            <div>
                <h1>Sell Your Product</h1>
                <p>Create a listing with the product name, price, brand, location, and sizes details buyers can trust.</p>
            </div>
            <div>
                <p>You are signed in as <strong><?= $user_name ?></strong></p>
            </div>
        </header>

        <div class="form-section">
            <h2>Personal Information</h2>
            <?php if ($profile_success): ?>
                <div class="message success"><?= htmlspecialchars($profile_success) ?></div>
            <?php endif; ?>
            <?php if (!empty($profile_errors)): ?>
                <div class="message error">
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($profile_errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="save_profile">

                <label for="seller_id_number">ID Number</label>
                <input type="text" id="seller_id_number" name="seller_id_number" placeholder="e.g., 8001011234087" value="<?= htmlspecialchars($_POST['seller_id_number'] ?? $profile['id_number']) ?>" required>

                <label for="seller_phone">Phone Number</label>
                <input type="tel" id="seller_phone" name="seller_phone" placeholder="e.g., 0821234567" value="<?= htmlspecialchars($_POST['seller_phone'] ?? $profile['phone']) ?>" required>

                <label for="seller_address">Home Address</label>
                <input type="text" id="seller_address" name="seller_address" placeholder="e.g., 123 Main Street, Johannesburg" value="<?= htmlspecialchars($_POST['seller_address'] ?? $profile['address']) ?>" required>

                <label for="bank_name">Bank</label>
                <input type="text" id="bank_name" name="bank_name" placeholder="e.g., FNB, Standard Bank" value="<?= htmlspecialchars($_POST['bank_name'] ?? $profile['bank_name']) ?>" required>

                <label for="bank_account">Bank Account</label>
                <input type="text" id="bank_account" name="bank_account" placeholder="e.g., 12345678901" value="<?= htmlspecialchars($_POST['bank_account'] ?? $profile['bank_account']) ?>" required>

                <div class="button-row">
                    <button type="submit">Save Personal Info</button>
                </div>
            </form>
        </div>

        <div class="form-section">
            <h2>Product Listing Form</h2>
            <?php if ($success): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($product_success): ?>
                <div class="message success"><?= htmlspecialchars($product_success) ?></div>
            <?php endif; ?>
            <?php if (!empty($product_errors)): ?>
                <div class="message error">
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($product_errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_product">

                <label for="product_name">Product Name</label>
                <input type="text" id="product_name" name="product_name" placeholder="e.g., Vintage Leather Jacket" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required>

                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Gauteng, Midrand" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>

                <label for="price">Price (ZAR)</label>
                <input type="number" step="0.01" id="price" name="price" placeholder="e.g., 450.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>

                <label for="brand">Brand</label>
                <input type="text" id="brand" name="brand" placeholder="e.g., Nike, Adidas, Other" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" required>

                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Choose a category</option>
                    <option value="Clothes" <?= isset($category) && $category === 'Clothes' ? 'selected' : '' ?>>Clothes</option>
                    <option value="Jewelry" <?= isset($category) && $category === 'Jewelry' ? 'selected' : '' ?>>Jewelry</option>
                    <option value="Bags" <?= isset($category) && $category === 'Bags' ? 'selected' : '' ?>>Bags</option>
                    <option value="Shoes" <?= isset($category) && $category === 'Shoes' ? 'selected' : '' ?>>Shoes</option>
                    <option value="Kitchen" <?= isset($category) && $category === 'Kitchen' ? 'selected' : '' ?>>Kitchen</option>
                    <option value="Furniture" <?= isset($category) && $category === 'Furniture' ? 'selected' : '' ?>>Furniture</option>
                    <option value="Books" <?= isset($category) && $category === 'Books' ? 'selected' : '' ?>>Books</option>
                    <option value="Toys" <?= isset($category) && $category === 'Toys' ? 'selected' : '' ?>>Toys</option>
                    <option value="Other" <?= isset($category) && $category === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>

                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Choose gender</option>
                    <option value="Both" <?= isset($gender) && $gender === 'Both' ? 'selected' : '' ?>>Both</option>
                    <option value="Men" <?= isset($gender) && $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Women" <?= isset($gender) && $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Kids" <?= isset($gender) && $gender === 'Kids' ? 'selected' : '' ?>>Kids</option>
                </select>

                <label for="size">Clothing Size</label>
                <select id="size" name="size">
                    <option value="">Choose clothing size</option>
                    <option value="XXS" <?= isset($size) && $size === 'XXS' ? 'selected' : '' ?>>XXS</option>
                    <option value="XS" <?= isset($size) && $size === 'XS' ? 'selected' : '' ?>>XS</option>
                    <option value="S" <?= isset($size) && $size === 'S' ? 'selected' : '' ?>>S</option>
                    <option value="M" <?= isset($size) && $size === 'M' ? 'selected' : '' ?>>M</option>
                    <option value="L" <?= isset($size) && $size === 'L' ? 'selected' : '' ?>>L</option>
                    <option value="XL" <?= isset($size) && $size === 'XL' ? 'selected' : '' ?>>XL</option>
                    <option value="XXL" <?= isset($size) && $size === 'XXL' ? 'selected' : '' ?>>XXL</option>
                    <option value="Other" <?= isset($size) && $size === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>

                <label for="shoe_size">Shoe Size</label>
                <select id="shoe_size" name="shoe_size">
                    <option value="">Choose shoe size</option>
                    <option value="3" <?= isset($shoe_size) && $shoe_size === '3' ? 'selected' : '' ?>>3</option>
                    <option value="4" <?= isset($shoe_size) && $shoe_size === '4' ? 'selected' : '' ?>>4</option>
                    <option value="5" <?= isset($shoe_size) && $shoe_size === '5' ? 'selected' : '' ?>>5</option>
                    <option value="6" <?= isset($shoe_size) && $shoe_size === '6' ? 'selected' : '' ?>>6</option>
                    <option value="7" <?= isset($shoe_size) && $shoe_size === '7' ? 'selected' : '' ?>>7</option>
                    <option value="8" <?= isset($shoe_size) && $shoe_size === '8' ? 'selected' : '' ?>>8</option>
                    <option value="9" <?= isset($shoe_size) && $shoe_size === '9' ? 'selected' : '' ?>>9</option>
                    <option value="10" <?= isset($shoe_size) && $shoe_size === '10' ? 'selected' : '' ?>>10</option>
                    <option value="11" <?= isset($shoe_size) && $shoe_size === '11' ? 'selected' : '' ?>>11</option>
                    <option value="12" <?= isset($shoe_size) && $shoe_size === '12' ? 'selected' : '' ?>>12</option>
                    <option value="13" <?= isset($shoe_size) && $shoe_size === '13' ? 'selected' : '' ?>>13</option>
                    <option value="14" <?= isset($shoe_size) && $shoe_size === '14' ? 'selected' : '' ?>>14</option>
                </select>

                <label for="buyer_notes">What buyers should know</label>
                <textarea id="buyer_notes" name="buyer_notes" placeholder="Condition, size, any defects, etc. (optional)"></textarea>

                <label for="images">Product Images (Max 10)</label>
                <div class="file-input-wrapper">
                    <label for="images" class="file-input-label">
                        📷 Click to select images or drag & drop
                    </label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*">
                    <div class="file-names" id="file-count"></div>
                </div>

                <div class="button-row">
                    <button type="submit">Add Product</button>
                </div>
            </form>
        </div>

        <div class="products-section">
            <h2>Your Listed Products</h2>
            <?php if (empty($user_products)): ?>
                <div class="empty-state">
                    <p>You haven't listed any products yet. Create your first listing above!</p>
                </div>
            <?php else: ?>
                <?php foreach ($user_products as $product): ?>
                    <?php $productIsSold = !empty($product['is_sold']) ? 1 : 0; ?>
                    <div class="product-card <?= $productIsSold ? 'sold' : '' ?>">
                        <?php if ($productIsSold): ?>
                            <div class="sold-badge">SOLD</div>
                        <?php endif; ?>

                        <?php if (!empty($product['images'])): ?>
                            <div class="product-images">
                                <?php foreach ($product['images'] as $image): ?>
                                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="product-meta">
                            <div><strong>Price:</strong> R<?= number_format((float)$product['price'], 2) ?></div>
                            <div><strong>Brand:</strong> <?= htmlspecialchars($product['brand']) ?></div>
                            <div><strong>Location:</strong> <?= htmlspecialchars($product['location']) ?></div>
                            <div><strong>Size:</strong> <?= htmlspecialchars($product['size']) ?></div>
                            <div><strong>Shoe Size:</strong> <?= htmlspecialchars($product['shoe_size'] ?? '') ?></div>
                            <div><strong>Posted:</strong> <?= date('M j, Y', strtotime($product['created_at'])) ?></div>
                        </div>
                        <?php if (!empty($product['buyer_notes'])): ?>
                            <p class="product-desc"><strong>Buyer Notes:</strong> <?= nl2br(htmlspecialchars($product['buyer_notes'])) ?></p>
                        <?php endif; ?>

                        <div class="product-actions">
                            <a href="SellingEdit.php?product_id=<?= (int)$product['id'] ?>" class="btn-secondary" style="text-decoration:none; padding:10px 16px; font-size:14px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center;">
                                ✏️ Edit
                            </a>

                            <?php if (!$productIsSold): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="mark_sold">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn-success">Mark as Sold</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="mark_available">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn-secondary">Mark as Available</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('images');
        const fileCount = document.getElementById('file-count');
        const fileLabel = document.querySelector('.file-input-label');

        fileInput.addEventListener('change', () => {
            const count = fileInput.files.length;
            if (count > 10) {
                alert('Maximum 10 images allowed. Only first 10 will be uploaded.');
            }
            if (count > 0) {
                const names = Array.from(fileInput.files).map(f => f.name).join(', ');
                fileCount.textContent = `${count} file(s) selected: ${names}`;
            } else {
                fileCount.textContent = '';
            }
        });

        // Drag and drop
        fileLabel.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileLabel.style.borderColor = '#4338ca';
            fileLabel.style.background = '#f0ecff';
        });

        fileLabel.addEventListener('dragleave', () => {
            fileLabel.style.borderColor = '#d1d5db';
            fileLabel.style.background = '#f8fafc';
        });

        fileLabel.addEventListener('drop', (e) => {
            e.preventDefault();
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            fileLabel.style.borderColor = '#d1d5db';
            fileLabel.style.background = '#f8fafc';
        });
    </script>
</body>
</html>