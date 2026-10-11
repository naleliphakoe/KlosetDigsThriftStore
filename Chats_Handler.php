<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'You must be logged in to send messages.']);
    exit;
}

$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'klosetdigsdb';

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// Ensure the messages table exists. Uses users.id (the real primary key in
// this project's users table) rather than a non-existent "user_id" column.
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    body TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_type VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
)");

$userId = (int)$_SESSION['user_id'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'send_message') {
    $recipientId = (int)($_POST['recipient_id'] ?? 0);
    $body = trim((string)($_POST['body'] ?? ''));
    $attachmentPath = '';
    $attachmentType = '';

    if ($recipientId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Invalid recipient.']);
        exit;
    }

    if ($recipientId === $userId) {
        echo json_encode(['ok' => false, 'error' => 'You cannot message yourself.']);
        exit;
    }

    // users table primary key is "id", not "user_id"
    $check = $conn->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
    $check->bind_param("i", $recipientId);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        echo json_encode(['ok' => false, 'error' => 'Recipient not found.']);
        exit;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed, true)) {
            echo json_encode(['ok' => false, 'error' => 'Unsupported image type.']);
            exit;
        }

        $mime = mime_content_type($_FILES['image']['tmp_name']) ?: '';
        if (strpos($mime, 'image/') !== 0) {
            echo json_encode(['ok' => false, 'error' => 'Only image uploads are allowed.']);
            exit;
        }

        $dir = __DIR__ . '/uploads/messages';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $filename = 'msg_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $dir . '/' . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            echo json_encode(['ok' => false, 'error' => 'Failed to upload image.']);
            exit;
        }

        $attachmentPath = 'uploads/messages/' . $filename;
        $attachmentType = $mime;
    }

    if ($body === '' && $attachmentPath === '') {
        echo json_encode(['ok' => false, 'error' => 'Message cannot be empty.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, body, attachment_path, attachment_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $userId, $recipientId, $body, $attachmentPath, $attachmentType);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Failed to send message: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Invalid action.']);
$conn->close();