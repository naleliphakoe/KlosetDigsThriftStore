<?php
// Buyer ↔ Seller direct chat backend (separate from existing admin/support chat)
// Endpoints:
//   - action=send_message (POST)
//   - action=get_messages (GET)
//
// Required table schema (recommended):
//   messages(id, product_id, sender_id, receiver_id, message, created_at)
//
// SECURITY:
//   - Prepared statements
//   - Conversation filtering: only allows access if the authenticated user is either
//     the buyer or the seller for the given product.

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$authUserId = (int)$_SESSION['user_id'];

// Helper: JSON in fetch sometimes sends action as string; accept only expected actions.
$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : '';

// Use the same DB you already use for messages if it exists; otherwise default to productsdb.
// NOTE: Adjust DB name if your messages table lives elsewhere.
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'KlosetDigsdb';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');

function respond(string $status, array $payload = []): void {
    echo json_encode(array_merge(['status' => $status], $payload));
    exit;
}

function require_param(string $name, array $source): void {
    if (!isset($source[$name]) || $source[$name] === '') {
        respond('error', ['message' => "Missing parameter: {$name}"]);
    }
}

function get_int(mixed $v): int {
    return (int)filter_var($v, FILTER_VALIDATE_INT);
}


if ($action === 'send_message') {
    // POST: product_id, buyer_id, seller_id, message
    require_param('product_id', $_POST);
    require_param('buyer_id', $_POST);
    require_param('seller_id', $_POST);
    require_param('message', $_POST);

    $productId = get_int($_POST['product_id']);
    $buyerId   = get_int($_POST['buyer_id']);
    $sellerId  = get_int($_POST['seller_id']);
    $message   = trim((string)$_POST['message']);

    if ($productId <= 0 || $buyerId <= 0 || $sellerId <= 0) {
        respond('error', ['message' => 'Invalid identifiers']);
    }
    if ($message === '' || mb_strlen($message) > 2000) {
        respond('error', ['message' => 'Invalid message']);
    }

    // Auth user must be either buyer or seller.
    if (!in_array($authUserId, [$buyerId, $sellerId], true)) {
        respond('error', ['message' => 'Access denied']);
    }

    // Ensure product ownership matches seller_id.
    // products.user_id is seller_id.
    $check = $mysqli->prepare('SELECT id, user_id FROM products WHERE id = ? LIMIT 1');
    $check->bind_param('i', $productId);
    $check->execute();
    $res = $check->get_result();
    if (!$res || $res->num_rows !== 1) {
        respond('error', ['message' => 'Product not found']);
    }
    $row = $res->fetch_assoc();
    $productSellerId = (int)$row['user_id'];
    $check->close();

    if ($productSellerId !== $sellerId) {
        respond('error', ['message' => 'Seller does not own this product']);
    }

    // Sender/receiver from auth + provided buyer/seller
    $senderId = $authUserId;
    $receiverId = ($authUserId === $buyerId) ? $sellerId : $buyerId;

    // Insert message
    $stmt = $mysqli->prepare(
        'INSERT INTO messages (product_id, sender_id, receiver_id, message, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->bind_param('iiis', $productId, $senderId, $receiverId, $message);

    if (!$stmt->execute()) {
        respond('error', ['message' => 'Failed to send message']);
    }

    $stmt->close();

    respond('ok', ['message' => 'sent']);
}

if ($action === 'get_messages') {
    // GET: product_id, buyer_id, seller_id
    require_param('product_id', $_GET);
    require_param('buyer_id', $_GET);
    require_param('seller_id', $_GET);

    $productId = get_int($_GET['product_id']);
    $buyerId   = get_int($_GET['buyer_id']);
    $sellerId  = get_int($_GET['seller_id']);

    if ($productId <= 0 || $buyerId <= 0 || $sellerId <= 0) {
        respond('error', ['message' => 'Invalid identifiers']);
    }

    // Auth user must be buyer or seller.
    if (!in_array($authUserId, [$buyerId, $sellerId], true)) {
        respond('error', ['message' => 'Access denied']);
    }

    // Validate seller owns product.
    $check = $mysqli->prepare('SELECT user_id FROM products WHERE id = ? LIMIT 1');
    $check->bind_param('i', $productId);
    $check->execute();
    $res = $check->get_result();
    if (!$res || $res->num_rows !== 1) {
        respond('error', ['message' => 'Product not found']);
    }
    $productSellerId = (int)$res->fetch_assoc()['user_id'];
    $check->close();

    if ($productSellerId !== $sellerId) {
        respond('error', ['message' => 'Seller does not own this product']);
    }

    // Fetch messages between the two participants for that product.
    // Only return rows that match receiver/sender pair for the given buyer+seller.
    $stmt = $mysqli->prepare(
        'SELECT id, product_id, sender_id, receiver_id, message, created_at
         FROM messages
         WHERE product_id = ?
           AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
         ORDER BY created_at ASC
         LIMIT 2000'
    );
    // order of params: productId, buyer->seller, buyer->seller receiver, seller->buyer sender, seller->buyer receiver
    $stmt->bind_param('iiiii', $productId, $buyerId, $sellerId, $sellerId, $buyerId);
    $stmt->execute();

    $result = $stmt->get_result();
    $messages = [];
    while ($m = $result->fetch_assoc()) {
        $messages[] = [
            'id' => (int)$m['id'],
            'sender_id' => (int)$m['sender_id'],
            'receiver_id' => (int)$m['receiver_id'],
            'message' => $m['message'],
            'created_at' => $m['created_at'],
        ];
    }

    $stmt->close();

    respond('ok', ['messages' => $messages]);
}

respond('error', ['message' => 'Unknown action']);


