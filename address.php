<?php
session_start();
ob_start();

function sendJson(array $data) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data);
    exit;
}

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "klosetdigsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    sendJson(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
}

// Create delivery_addresses table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS delivery_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    shipping_method VARCHAR(50),
    courier_option VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($createTableQuery)) {
    sendJson(['success' => false, 'message' => 'Table creation failed: ' . $conn->error]);
}
// Create orders table if it doesn't exist
$createOrdersTable = "CREATE TABLE IF NOT EXISTS orders (
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
)";

if (!$conn->query($createOrdersTable)) {
    sendJson(['success' => false, 'message' => 'Orders table creation failed: ' . $conn->error]);
}

// Create order_items table if it doesn't exist
$createOrderItemsTable = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id_fk VARCHAR(20) NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id_fk) REFERENCES orders(order_id) ON DELETE CASCADE
)";

if (!$conn->query($createOrderItemsTable)) {
    sendJson(['success' => false, 'message' => 'Order items table creation failed: ' . $conn->error]);
}

// Create seller_notifications table
// Create seller notifications table if it doesn't exist
$createSellerNotificationsTable = "CREATE TABLE IF NOT EXISTS seller_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    order_id VARCHAR(20) NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createSellerNotificationsTable)) {
    sendJson(['success' => false, 'message' => 'Seller notifications table creation failed: ' . $conn->error]);
}

// Ensure existing products table has an `is_sold` column to avoid runtime errors
// If the table doesn't exist we skip; if column missing, attempt to add it.
$tableRes = $conn->query("SHOW TABLES LIKE 'products'");
if ($tableRes && $tableRes->num_rows > 0) {
    $colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'is_sold'");
    if ($colRes && $colRes->num_rows === 0) {
        if (!$conn->query("ALTER TABLE products ADD COLUMN is_sold TINYINT(1) DEFAULT 0")) {
            // Non-fatal but report JSON error to client when attempting to create orders
            sendJson(['success' => false, 'message' => 'Failed to add is_sold column to products table: ' . $conn->error]);
        }
    }
}

// Handle POST requests for saving address and creating order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $fullName = isset($_POST['fullName']) ? $conn->real_escape_string($_POST['fullName']) : '';
    $streetAddress = isset($_POST['streetAddress']) ? $conn->real_escape_string($_POST['streetAddress']) : '';
    $city = isset($_POST['city']) ? $conn->real_escape_string($_POST['city']) : '';
    $postalCode = isset($_POST['postalCode']) ? $conn->real_escape_string($_POST['postalCode']) : '';
    $phoneNumber = isset($_POST['phoneNumber']) ? $conn->real_escape_string($_POST['phoneNumber']) : '';
    $shippingMethod = isset($_POST['shippingMethod']) ? $conn->real_escape_string($_POST['shippingMethod']) : '';
    $courierOption = isset($_POST['courierOption']) ? $conn->real_escape_string($_POST['courierOption']) : '';
    $cartItems = isset($_POST['cartItems']) ? json_decode($_POST['cartItems'], true) : [];
    $totalPrice = isset($_POST['totalPrice']) ? floatval($_POST['totalPrice']) : 0.00;

    // Validate required fields
        if (empty($fullName) || empty($streetAddress) || empty($city) || empty($postalCode) || empty($phoneNumber)) {
        sendJson(['success' => false, 'message' => 'All fields are required']);
    }

        if (empty($cartItems) || !is_array($cartItems)) {
            sendJson(['success' => false, 'message' => 'Cart is empty. Please add items before placing an order.']);
        }

    // Generate unique order ID (e.g., ORD-20260531-001234)
    $date = date('Ymd');
    $random = rand(100000, 999999);
    $order_id = 'ORD-' . $date . '-' . substr($random, 0, 6);

    // Check if order ID already exists (rare but possible)
    $check_stmt = $conn->prepare("SELECT id FROM orders WHERE order_id = ?");
    if (!$check_stmt) {
        sendJson(['success' => false, 'message' => 'Prepare failed for order id check: ' . $conn->error]);
    }
    $check_stmt->bind_param("s", $order_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $random = rand(1000000, 9999999);
        $order_id = 'ORD-' . $date . '-' . substr($random, 0, 6);
    }
    $check_stmt->close();

    // Get user info from session if available
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : NULL;
    $customer_email = isset($_SESSION['user_email']) ? $conn->real_escape_string($_SESSION['user_email']) : NULL;

    // Insert order into database
    $insertOrderQuery = "INSERT INTO orders (order_id, user_id, customer_name, customer_email, customer_phone, street_address, city, postal_code, shipping_method, courier_option, total_price, order_status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($insertOrderQuery);
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    }
    $stmt->bind_param("sissssssssd", $order_id, $user_id, $fullName, $customer_email, $phoneNumber, $streetAddress, $city, $postalCode, $shippingMethod, $courierOption, $totalPrice);
    if ($stmt->execute()) {
        // Insert order items
        if (!empty($cartItems) && is_array($cartItems)) {
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id_fk, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
            if (!$itemStmt) {
                sendJson(['success' => false, 'message' => 'Prepare failed for order items: ' . $conn->error]);
            }

            // Ensure is_sold column exists before attempting updates
            $colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'is_sold'");
            $hasIsSold = ($colRes && $colRes->num_rows > 0);
            if (!$hasIsSold) {
                $conn->query("ALTER TABLE products ADD COLUMN is_sold TINYINT(1) DEFAULT 0");
                $colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'is_sold'");
                $hasIsSold = ($colRes && $colRes->num_rows > 0);
            }

            $sellerQuery = $conn->prepare("SELECT user_id FROM products WHERE id = ? LIMIT 1");
            if (!$sellerQuery) {
                sendJson(['success' => false, 'message' => 'Prepare failed for seller lookup: ' . $conn->error]);
            }

            $notifyStmt = $conn->prepare("INSERT INTO seller_notifications (seller_id, order_id, product_id, message) VALUES (?, ?, ?, ?)");
            if (!$notifyStmt) {
                sendJson(['success' => false, 'message' => 'Prepare failed for seller notification: ' . $conn->error]);
            }

            foreach ($cartItems as $item) {
                $prod_id = isset($item['id']) ? intval($item['id']) : 0;
                $prod_name = isset($item['name']) ? $conn->real_escape_string($item['name']) : '';
                $price = isset($item['price']) ? floatval($item['price']) : 0.00;
                $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
                $itemStmt->bind_param("sisdi", $order_id, $prod_id, $prod_name, $price, $qty);
                $itemStmt->execute();

                if ($prod_id > 0 && $hasIsSold) {
                    // Use a direct query to avoid prepare-time exceptions if schema changes during runtime
                    $conn->query(sprintf("UPDATE products SET is_sold = 1 WHERE id = %d", $prod_id));
                }

                if ($prod_id > 0) {
                    $sellerQuery->bind_param("i", $prod_id);
                    $sellerQuery->execute();
                    $sellerQuery->store_result();
                    $sellerQuery->bind_result($seller_id);
                    if ($sellerQuery->num_rows > 0 && $sellerQuery->fetch()) {
                        $notificationMessage = "Your product '{$prod_name}' has been purchased. Order ID: {$order_id}.";
                        $notifyStmt->bind_param("isis", $seller_id, $order_id, $prod_id, $notificationMessage);
                        $notifyStmt->execute();
                    }
                    $sellerQuery->free_result();
                }
            }
            $sellerQuery->close();
            $notifyStmt->close();
            $itemStmt->close();
        }
        $stmt->close();
        // store last order id in session so confirmation page can read it
        $_SESSION['last_order_id'] = $order_id;
        sendJson(['success' => true, 'message' => 'Order created successfully', 'orderId' => $order_id]);
    } else {
        sendJson(['success' => false, 'message' => 'Error saving order: ' . $conn->error]);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Method</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 24px; 
            background-color: #d0e2ef;
        }
        fieldset { 
            border: 1px solid #ccc; 
            padding: 16px; 
            margin-bottom: 16px; 
        }
        legend { 
            font-weight: bold;
         }
        .hidden { 
            display: none; 
        }
        label { 
            display: block; 
            margin-bottom: 8px;
         }
        .option-group { 
            margin-left: 20px; 
            margin-top: 12px; 
        }
        input[type="text"], input[type="tel"] { 
            width: 100%; 
            padding: 8px; 
            margin-top: 4px; 
            box-sizing: border-box; 
        }
        .h1{
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .backBtn{
            padding: 8px 16px; 
            font-size: 14px; 
            cursor: pointer; 
            display: none;
        }

    </style>
</head>
<body>
<div>
    <button id="backBtn" onclick="window.location.href='viewCart.php'" type="button">Back</button>
        <h1>Select Shipping Method</h1>   
    </div>
    <form id="shippingForm">
        <fieldset>
            <legend>Main Shipping Method</legend>
            <label><input type="radio" name="shippingMethod" value="Collection"> Collection</label>
            <label><input type="radio" name="shippingMethod" value="courier"> Courier</label>
        </fieldset>

        <fieldset id="paxiSection" class="hidden">
            <legend>Paxi Options</legend>
            <div>
                <label><input type="radio" name="paxiCategory" value="speed"> Speed (3-5 days)</label>
                <label><input type="radio" name="paxiCategory" value="economy"> Economy (7-9 days)</label>
            </div>
            <div id="paxiDeliveryOptions" class="option-group hidden">
                <strong>Delivery Type</strong>
                <label><input type="radio" name="paxiDeliveryType" value="store-to-store"> Store-to-store</label>
                <label><input type="radio" name="paxiDeliveryType" value="store-to-door"> Store-to-door</label>
            </div>
        </fieldset>

        <fieldset id="courierSection" class="hidden">
            <legend>Courier Options</legend>
            <label><input type="radio" name="courierOption" value="sameDayExpress" data-fee="250"> Same-day Express (R250)</label>
            <label><input type="radio" name="courierOption" value="overnightCourier" data-fee="400"> Overnight Courier (R400)</label>
            <label><input type="radio" name="courierOption" value="roadFreightEconomy" data-fee="150"> Road Freight / Economy (R150)</label>
        </fieldset>

        <fieldset id="addressSection" class="hidden">
            <legend>Delivery Address</legend>
            <label>Full Name: <input type="text" name="fullName" required></label>
            <label>Street Address: <input type="text" name="streetAddress" required></label>
            <label>City: <input type="text" name="city" required></label>
            <label>Postal Code: <input type="text" name="postalCode" required></label>
            <label>Phone Number: <input type="tel" name="phoneNumber" required></label>
        </fieldset>

        <button class="icon-btn" type="submit">Continue</button>
</div>
    </form>

    <script>
        const shippingRadios = document.querySelectorAll('input[name="shippingMethod"]');
        const paxiSection = document.getElementById('paxiSection');
        const courierSection = document.getElementById('courierSection');
        const addressSection = document.getElementById('addressSection');
        const paxiCategoryRadios = document.querySelectorAll('input[name="paxiCategory"]');
        const paxiDeliveryOptions = document.getElementById('paxiDeliveryOptions');
        const courierOptionRadios = document.querySelectorAll('input[name="courierOption"]');
        const backBtn = document.getElementById('backBtn');

        function updateSections() {
            const selected = document.querySelector('input[name="shippingMethod"]:checked');
            addressSection.classList.add('hidden');
            backBtn.style.display = 'none';
            if (!selected) {
                paxiSection.classList.add('hidden');
                courierSection.classList.add('hidden');
                return;
            }
            if (selected.value === 'paxi') {
                paxiSection.classList.remove('hidden');
                courierSection.classList.add('hidden');
                updatePaxiDeliveryOptions();
            } else {
                paxiSection.classList.add('hidden');
                courierSection.classList.remove('hidden');
            }
        }

        function updatePaxiDeliveryOptions() {
            const selectedCategory = document.querySelector('input[name="paxiCategory"]:checked');
            if (selectedCategory) {
                paxiDeliveryOptions.classList.remove('hidden');
            } else {
                paxiDeliveryOptions.classList.add('hidden');
            }
        }

        function updateAddressForm() {
            // Show address for Courier AND Collection.
            const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked');
            const courierOption = document.querySelector('input[name="courierOption"]:checked');

            if (!shippingMethod) {
                addressSection.classList.add('hidden');
                backBtn.style.display = 'none';
                return;
            }

            if (shippingMethod.value === 'Collection') {
                addressSection.classList.remove('hidden');
                backBtn.style.display = 'block';
                return;
            }

            // For courier, only show address when a courier option is selected.
            if (courierOption) {
                addressSection.classList.remove('hidden');
                backBtn.style.display = 'block';
            } else {
                addressSection.classList.add('hidden');
                backBtn.style.display = 'none';
            }
        }


        function handleBack() {
            document.getElementById('shippingForm').reset();
            paxiSection.classList.add('hidden');
            courierSection.classList.add('hidden');
            addressSection.classList.add('hidden');
            backBtn.style.display = 'none';
        }

        shippingRadios.forEach(radio => radio.addEventListener('change', () => {
            updateSections();
            updateAddressForm();
        }));
        paxiCategoryRadios.forEach(radio => radio.addEventListener('change', updatePaxiDeliveryOptions));
        courierOptionRadios.forEach(radio => radio.addEventListener('change', updateAddressForm));
        backBtn.addEventListener('click', handleBack);


        document.getElementById('shippingForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked');
            if (!shippingMethod) {
                alert('Please select a shipping method.');
                return;
            }
            let selectedShippingOption = '';
            if (shippingMethod.value === 'paxi') {
                const category = document.querySelector('input[name="paxiCategory"]:checked');
                const deliveryType = document.querySelector('input[name="paxiDeliveryType"]:checked');
                if (!category || !deliveryType) {
                    alert('Please select a Paxi category and delivery type.');
                    return;
                }
                selectedShippingOption = category.value + ' - ' + deliveryType.value;
            } else {
                const courierOption = document.querySelector('input[name="courierOption"]:checked');
                if (!courierOption) {
                    alert('Please select a courier option.');
                    return;
                }
                selectedShippingOption = courierOption.value;
            }
            const fullName = document.querySelector('input[name="fullName"]').value;
            const streetAddress = document.querySelector('input[name="streetAddress"]').value;
            const city = document.querySelector('input[name="city"]').value;
            const postalCode = document.querySelector('input[name="postalCode"]').value;
            const phoneNumber = document.querySelector('input[name="phoneNumber"]').value;
            
            if (!fullName || !streetAddress || !city || !postalCode || !phoneNumber) {
                alert('Please fill in all address fields.');
                return;
            }
            
            // Store shipping and order summary in localStorage and go to checkout
            const cart = JSON.parse(localStorage.getItem('thrift-cart') || '[]');
            let cartTotal = 0;
            cart.forEach(item => { cartTotal += item.price * item.quantity; });

            // determine shipping fee from selected option element
            let shippingFee = 0;
            const courierEl = document.querySelector('input[name="courierOption"]:checked');
            if (courierEl && courierEl.dataset && courierEl.dataset.fee) {
                shippingFee = parseFloat(courierEl.dataset.fee) || 0;
            }

            const pendingOrder = {
                fullName, streetAddress, city, postalCode, phoneNumber,
                shippingMethod: shippingMethod.value,
                courierOption: selectedShippingOption,
                shippingFee,
                cartItems: cart,
                subtotal: cartTotal,
                totalPrice: cartTotal + shippingFee
            };
            localStorage.setItem('thrift-pending-order', JSON.stringify(pendingOrder));
            // Navigate to checkout to complete payment
            window.location.href = 'checkout.html';
        });
    </script>
</body>
</html>