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

$conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME NULL,
    UNIQUE(email, token_hash)
) ENGINE=InnoDB");

$token = (string) trim($_GET['token'] ?? '');
$email = (string) trim($_GET['email'] ?? '');

$error = '';
$success = '';

$userId = null;


if ($token === '' || $email === '') {
    $error = 'Invalid reset link.';
} else {
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare("SELECT user_id, expires_at, used_at FROM password_reset_tokens WHERE email = ? AND token_hash = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $tokenHash);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($dbUserId, $expiresAt, $usedAt);
        $stmt->fetch();
        $userId = $dbUserId;

        if (!empty($usedAt)) {
            $error = 'This reset link has already been used.';
        } else {
            $expiresAtTs = !empty($expiresAt) ? (int)strtotime((string)$expiresAt) : null;
            if ($expiresAtTs !== null) {
                $now = time();
                if ($expiresAtTs < $now) {
                    $error = 'This reset link has expired. Please request a new one.';
                }
            }

        }

    } else {
        $error = 'Invalid reset link.';
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error) && $userId) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '' || $confirmPassword === '') {
        $error = 'Please fill in both password fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ? LIMIT 1");
        $upd->bind_param("si", $hashed, $userId);

        if ($upd->execute()) {
// Mark token as used
            $tokenHash = hash('sha256', (string) $token);

            $mark = $conn->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE email = ? AND token_hash = ? AND used_at IS NULL");
            $mark->bind_param("ss", $email, $tokenHash);
            $mark->execute();
            $mark->close();

            $success = 'Password updated successfully. You can now log in.';
        } else {
            $error = 'Unable to update password. Please try again.';
        }
        $upd->close();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #bdc9e0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .wrap {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            padding: 28px;
            width: 380px;
        }
        h1 { 
            color: #8ccbd2;
            margin: 0 0 10px; 
            font-size: 22px; 
            text-align: center; 
        }
        .error { 
            color: #c43d3d; 
            margin: 10px 0; 
        }
        .success { 
            color: #1b6c32; 
            margin: 10px 0; 
        }
        label { 
            font-size: 14px; 
            color: #555; 
            display:block; 
            margin-top: 14px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 10px;
            border: 1px solid #ccc;
            background: #f2f6fb;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 18px;
            border: none;
            border-radius: 12px;
            background: #7fa2f2;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover { 
            opacity: 0.95; 
        }
        a { 
            color: #417cd0; 
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Reset Password</h1>

        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if (empty($error) && empty($success)): ?>
            <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>&email=<?= urlencode($email) ?>">
                <label>New password</label>
                <input type="password" name="new_password" required>

                <label>Confirm password</label>
                <input type="password" name="confirm_password" required>

                <button type="submit">Update Password</button>
            </form>
        <?php else: ?>
            <p style="margin-top: 16px;">Back to <a href="login.php">login</a></p>
        <?php endif; ?>
    </div>
</body>
</html>

