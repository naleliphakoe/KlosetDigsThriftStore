<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $next = urlencode('SellingEdit.php');
    header("Location: login.php?next=$next");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'KlosetDigsdb');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id <= 0) {
    header('Location: Selling.php');
    exit();
}

// Ensure product belongs to logged-in seller
$stmt = $conn->prepare('SELECT * FROM products WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $product_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$product) {
    header('Location: Selling.php');
    exit();
}

// Load images
$images = [];
$imgRes = $conn->query('SELECT image_path FROM product_images WHERE product_id=' . $product_id);
if ($imgRes) {
    while ($r = $imgRes->fetch_assoc()) {
        $images[] = $r['image_path'];
    }
}

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_product') {
        $name = trim($_POST['product_name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $category = trim($_POST['category'] ?? 'Other');
        $gender = trim($_POST['gender'] ?? 'Both');
        $size = trim($_POST['size'] ?? '');
        $shoe_size = trim($_POST['shoe_size'] ?? '');
        $buyer_notes = trim($_POST['buyer_notes'] ?? '');

        if ($name === '') $errors[] = 'Product name is required.';
        if ($location === '') $errors[] = 'Location is required.';
        if ($price === '' || !is_numeric($price) || floatval($price) < 0) $errors[] = 'Please enter a valid price.';
        if ($brand === '') $errors[] = 'Brand is required.';
        if ($category === '') $errors[] = 'Category is required.';
        if ($gender === '') $errors[] = 'Gender is required.';

        if (empty($errors)) {
            $price_value = number_format((float)$price, 2, '.', '');

            $stmt = $conn->prepare('UPDATE products SET name=?, location=?, price=?, brand=?, category=?, gender=?, size=?, shoe_size=?, buyer_notes=? WHERE id=? AND user_id=?');
            $stmt->bind_param('ssdssssssi', $name, $location, $price_value, $brand, $category, $gender, $size, $shoe_size, $buyer_notes, $product_id, $user_id);
            if ($stmt->execute()) {
                // Optional image additions (not deleting old ones)
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $total_files = count($_FILES['images']['name']);
                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmp = $_FILES['images']['tmp_name'][$i];
                            $namef = time() . '_' . rand(1000, 9999) . '_' . basename($_FILES['images']['name'][$i]);
                            $path = $upload_dir . $namef;
                            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                            if (in_array($ext, $allowed)) {
                                if (move_uploaded_file($tmp, $path)) {
                                    $img_stmt = $conn->prepare('INSERT INTO product_images (product_id, image_path) VALUES (?, ?)');
                                    $img_stmt->bind_param('is', $product_id, $path);
                                    $img_stmt->execute();
                                    $img_stmt->close();
                                }
                            }
                        }
                    }
                }

                $success = 'Product updated successfully.';
                // Refresh product data
                $stmt->close();
                $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->bind_param('ii', $product_id, $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $product = $res ? $res->fetch_assoc() : $product;
                $stmt->close();

                $images = [];
                $imgRes = $conn->query('SELECT image_path FROM product_images WHERE product_id=' . $product_id);
                if ($imgRes) {
                    while ($r = $imgRes->fetch_assoc()) {
                        $images[] = $r['image_path'];
                    }
                }
            } else {
                $errors[] = 'Unable to update the product.';
            }
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
        }
    }
}

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Guest User');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Product | Kloset Digs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #eef2ff; color: #1f2937; }
        .page-wrapper { max-width: 1200px; margin: 0 auto; padding: 24px; position: relative; }
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
        .page-wrapper > * { position: relative; z-index: 1; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        header h1 { font-size: 32px; }
        header p { margin-top: 8px; color: #4b5563; }
        a.back-button { display: inline-block; margin-bottom: 20px; padding: 10px 16px; background: #6b7280; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
        a.back-button:hover { background: #4b5563; }

        .form-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 16px 45px rgba(15, 23, 42, 0.08);
            padding: 28px;
            margin-bottom: 28px;
        }
        .form-section h2 { font-size: 22px; margin-bottom: 18px; }
        .message.success { background: #e0f2fe; color: #0c4a6e; border: 1px solid #7dd3fc; padding: 12px 14px; border-radius: 14px; margin-bottom: 16px; }
        .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 14px; margin-bottom: 16px; }

        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        input, textarea, select { display: block; width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #d1d5db; font-size: 14px; margin-bottom: 18px; background: #f8fafc; }
        textarea { min-height: 120px; resize: vertical; }

        .product-images { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .product-images img { width: 110px; height: 110px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; }

        .button-row { display: flex; gap: 14px; flex-wrap: wrap; }
        button { border: none; border-radius: 14px; padding: 14px 24px; color: white; background: #4338ca; cursor: pointer; font-size: 15px; font-weight: 600; }
        button:hover { background: #3730a3; }

        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
    </style>
</head>
<body>
<div class="page-wrapper">
    <a href="Selling.php" class="back-button">← Back</a>

    <header>
        <div>
            <h1>Edit Product</h1>
            <p>Update product details for buyers.</p>
        </div>
        <div>
            <p>You are signed in as <strong><?= $user_name ?></strong></p>
        </div>
    </header>

    <div class="form-section">
        <h2>Current images</h2>
        <?php if (!empty($images)): ?>
            <div class="product-images">
                <?php foreach ($images as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="small" style="color:#6b7280;">No images yet.</p>
        <?php endif; ?>
    </div>

    <div class="form-section">
        <h2>Edit details</h2>

        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <ul style="margin:0; padding-left:18px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_product">
            <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">

            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>

            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= htmlspecialchars($product['location'] ?? '') ?>" required>

            <label for="price">Price (ZAR)</label>
            <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($product['price'] ?? '0') ?>" required>

            <label for="brand">Brand</label>
            <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>" required>

            <label for="category">Category</label>
            <select id="category" name="category" required>
                <?php
                $cats = ['Clothes','Jewelry','Bags','Shoes','Kitchen','Furniture','Books','Toys','Other'];
                foreach ($cats as $c) {
                    $sel = (isset($product['category']) && $product['category'] === $c) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($c) . '" ' . $sel . '>' . htmlspecialchars($c) . '</option>';
                }
                ?>
            </select>

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <?php
                $genders = ['Both','Men','Women','Kids'];
                foreach ($genders as $g) {
                    $sel = (isset($product['gender']) && $product['gender'] === $g) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($g) . '" ' . $sel . '>' . htmlspecialchars($g) . '</option>';
                }
                ?>
            </select>

            <label for="size">Clothing Size</label>
            <select id="size" name="size">
                <?php
                $sizes = ['', 'XXS','XS','S','M','L','XL','XXL','Other'];
                foreach ($sizes as $s) {
                    $label = $s === '' ? 'Choose clothing size' : $s;
                    $value = $s;
                    $current = $product['size'] ?? '';
                    $sel = ($value !== '' && $current === $value) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($value) . '" ' . ($value === '' ? 'selected' : $sel) . '>' . htmlspecialchars($label) . '</option>';
                }
                ?>
            </select>

            <label for="shoe_size">Shoe Size</label>
            <select id="shoe_size" name="shoe_size">
                <?php
                $shoeSizes = ['', '3','4','5','6','7','8','9','10','11','12','13','14'];
                $currentShoe = $product['shoe_size'] ?? '';
                foreach ($shoeSizes as $ss) {
                    $label = $ss === '' ? 'Choose shoe size' : $ss;
                    $sel = ($ss !== '' && (string)$currentShoe === (string)$ss) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($ss) . '" ' . ($ss === '' ? 'selected' : $sel) . '>' . htmlspecialchars($label) . '</option>';
                }
                ?>
            </select>

            <label for="buyer_notes">What buyers should know</label>
            <textarea id="buyer_notes" name="buyer_notes" placeholder="Condition, size, any defects, etc."><?= htmlspecialchars($product['buyer_notes'] ?? '') ?></textarea>

            <label for="images">Add more images (optional)</label>
            <input type="file" id="images" name="images[]" multiple accept="image/*">

            <div class="button-row">
                <button type="submit">Save Changes</button>
                <a class="btn-danger" style="display:inline-flex; align-items:center; justify-content:center; padding:14px 24px; border-radius:14px; color:white; text-decoration:none;" href="Selling.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>


