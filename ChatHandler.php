<?php
// chat_handler.php - Backend for chat functionality

// Start session to access user info
session_start();

// Database connection
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'KlosetDigsdb';

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Set charset to UTF-8
$conn->set_charset('utf8mb4');

// Create chat_messages table if it doesn't exist
$createTableQuery = "
    CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        sender ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$conn->query($createTableQuery);

$action = $_REQUEST['action'] ?? '';

// ---- Conversation key security ----
// Client must NOT control the conversation key.
// We derive it from the logged-in session + request parameters that identify the *other party*.

$isAdmin = isset($_SESSION['admin_id']);
$userIdSession = isset($_SESSION['user_id']) ? (string)$_SESSION['user_id'] : '';

function computeBuyerToBuyerKey(string $a, string $b): string {
    // order-independent
    $x = intval($a);
    $y = intval($b);
    if ($x <= $y) {
        return "user:$x:$y";
    }
    return "user:$y:$x";
}

function computeSupportKey(): string {
    // single shared support thread per logged-in user
    // (admin vs user still isolated by prefix)
    global $userIdSession;
    return "support:$userIdSession";
}

function requireLoggedInOrFail(bool $isAdmin): void {
    global $userIdSession;
    if ($isAdmin) return;
    if ($userIdSession === '' || !isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
}

// Handle getting messages
if ($action === 'getMessages') {
    requireLoggedInOrFail($isAdmin);

    // For security we accept only partner identifiers, not a raw conversation userId.
    // Chats.html (support) does: action=getMessages&userId=<local convo key>
    // We ignore that and compute from session.

    $requestedPartnerUserId = isset($_GET['partnerUserId']) ? (string)$_GET['partnerUserId'] : '';

    if ($isAdmin) {
        // Admin can view: support threads or buyer-buyer threads.
        // If partnerUserId is present -> treat as buyer-to-buyer view.
        if ($requestedPartnerUserId !== '') {
            $viewer = $userIdSession; // admin doesn't have user_id; but still fine: compute key from two buyers.
            // For buyer-to-buyer, both sides must be provided.
            $buyerA = isset($_GET['buyerA']) ? (string)$_GET['buyerA'] : $userIdSession;
            $buyerB = $requestedPartnerUserId;
            if ($buyerA === '' || $buyerB === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing buyer identifiers']);
                exit;
            }
            $conversationKey = computeBuyerToBuyerKey($buyerA, $buyerB);
        } else {
            // default admin support view uses support key derived from admin? not possible without user.
            // require supportThreadKey from client but validated as support:{current user session id} is not accessible for admin.
            // Admin should call with supportThreadUserId.
            $supportThreadUserId = isset($_GET['supportThreadUserId']) ? (string)$_GET['supportThreadUserId'] : '';
            if ($supportThreadUserId === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'supportThreadUserId required for admin']);
                exit;
            }
            $conversationKey = "support:" . intval($supportThreadUserId);
        }
    } else {
        // User view: if partnerUserId present -> buyer-to-buyer; else -> support
        if ($requestedPartnerUserId !== '') {
            $conversationKey = computeBuyerToBuyerKey($userIdSession, $requestedPartnerUserId);
        } else {
            $conversationKey = computeSupportKey();
        }
    }

    $query = "SELECT message, sender, created_at FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC LIMIT 100";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('s', $conversationKey);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    $stmt->close();
    exit;
}

// Handle getting all conversations (for admin)
if ($action === 'getAllConversations') {

    // Fetch all unique user_ids and their last message
    $query = "
        SELECT user_id, message as last_message, created_at
        FROM chat_messages
        WHERE user_id != ''
        GROUP BY user_id
        ORDER BY MAX(created_at) DESC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'conversations' => $conversations]);
    exit;
}

// Handle sending messages
if ($action === 'sendMessage') {
    requireLoggedInOrFail($isAdmin);

    $message = $_POST['message'] ?? '';
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'message required']);
        exit;
    }

    // conversation selection from request; do not trust client userId.
    $requestedPartnerUserId = isset($_POST['partnerUserId']) ? (string)$_POST['partnerUserId'] : '';

    if ($isAdmin) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Admins must use sendAdminReply']);
        exit;
    }

    $conversationKey = ($requestedPartnerUserId !== '')
        ? computeBuyerToBuyerKey($userIdSession, $requestedPartnerUserId)
        : computeSupportKey();

    // Limit message length
    $message = substr($message, 0, 5000);

    // Insert user message
    $query = "INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'user')";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('ss', $conversationKey, $message);

    if ($stmt->execute()) {
        // Auto-response from admin (simple rule-based)
        $autoReply = generateAutoReply($message);

        if ($autoReply) {
            $adminQuery = "INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'admin')";
            $adminStmt = $conn->prepare($adminQuery);
            $adminStmt->bind_param('ss', $conversationKey, $autoReply);
            $adminStmt->execute();
            $adminStmt->close();
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Message sent']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
    }

    $stmt->close();
    exit;
}


// Handle admin sending a reply (requires admin authentication)
if ($action === 'sendAdminReply') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $message = $_POST['message'] ?? '';
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'message required']);
        exit;
    }

    // conversation key derived from identifiers
    $type = $_POST['type'] ?? 'support'; // support | buyer

    if ($type === 'buyer') {
        $buyerA = $_POST['buyerA'] ?? '';
        $buyerB = $_POST['buyerB'] ?? '';
        if ($buyerA === '' || $buyerB === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'buyerA and buyerB required']);
            exit;
        }
        $conversationKey = computeBuyerToBuyerKey((string)$buyerA, (string)$buyerB);
    } else {
        $supportThreadUserId = $_POST['supportThreadUserId'] ?? '';
        if ($supportThreadUserId === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'supportThreadUserId required']);
            exit;
        }
        $conversationKey = "support:" . intval($supportThreadUserId);
    }

    $message = substr($message, 0, 5000);

    $query = "INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'admin')";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('ss', $conversationKey, $message);

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Admin reply sent']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to send reply']);
    }

    $stmt->close();
    exit;
}


// Default error response
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
$conn->close();
exit;

// Auto-reply generator based on keywords
function generateAutoReply(string $userMessage): string {

    $lowerMessage = strtolower($userMessage);
    
    // Keywords and corresponding auto-replies
    $replies = [
        ['keywords' => ['hours', 'response', 'support', 'available'], 'reply' => 'Hi! Our support team typically responds within business hours (9 AM - 5 PM EST, Monday-Friday). We appreciate your patience! 😊'],
        ['keywords' => ['shipping', 'delivery', 'track'], 'reply' => 'For shipping and delivery questions, please check your order details for tracking information. Most items arrive within 5-7 business days.'],
        ['keywords' => ['refund', 'return', 'exchange'], 'reply' => 'We accept returns within 14 days of purchase. Please contact our support team with your order number and photos for faster processing.'],
        ['keywords' => ['payment', 'checkout', 'card'], 'reply' => 'We accept credit/debit cards, PayPal, and bank transfers. All payments are encrypted and secure. If you\'re having trouble, please try again or contact support.'],
        ['keywords' => ['list', 'selling', 'create', 'item'], 'reply' => 'To create a listing, go to "Create a Listing," add photos, description, price, and submit. Your item goes live immediately! Need help? Check our Seller Guide.'],
        ['keywords' => ['price', 'fee', 'commission', 'cost'], 'reply' => 'Our platform fee is 10% of the sale price. For example, a R100 sale nets you R90. Sellers with 50+ listings get 8% commission. See our Pricing Guide for more details.'],
    ];
    
    // Check for keyword matches
    foreach ($replies as $rule) {
        foreach ($rule['keywords'] as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return $rule['reply'];
            }
        }
    }
    
    // Default reply if no keywords match
    return "Thanks for reaching out! Our team will review your message and get back to you shortly. In the meantime, check our Support Center for quick answers.";
}
?>
