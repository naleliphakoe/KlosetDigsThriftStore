<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "klosetdigsdb";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    fullname VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    password VARCHAR(255) NOT NULL,
    temporary_password TINYINT(1) NOT NULL DEFAULT 1,
    role VARCHAR(50) NULL DEFAULT 'Staff',
    access_level VARCHAR(50) NULL DEFAULT 'Limited Access',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    permissions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

function ensureAdminColumn(mysqli $conn, string $columnName, string $columnDefinition): void {
    $check = $conn->query("SHOW COLUMNS FROM admin_users LIKE '" . $conn->real_escape_string($columnName) . "'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE admin_users ADD COLUMN " . $columnDefinition);
    }
}

ensureAdminColumn($conn, 'role', "role VARCHAR(50) NULL DEFAULT 'Staff'");
ensureAdminColumn($conn, 'access_level', "access_level VARCHAR(50) NULL DEFAULT 'Limited Access'");
ensureAdminColumn($conn, 'is_active', "is_active TINYINT(1) NOT NULL DEFAULT 1");
ensureAdminColumn($conn, 'permissions', "permissions TEXT NULL");

$roles = ['Super Admin', 'Manager', 'Staff'];
$accessLevels = ['Full Access', 'Manager Access', 'Limited Access'];
$rolePermissions = [
    'Super Admin' => 'Full dashboard access, user management, settings, orders, products, and reports.',
    'Manager' => 'Manage orders, customers, products, and reports. Limited settings access.',
    'Staff' => 'View orders and customers only. No user management or settings access.',
];

$admin_id = intval($_SESSION['admin_id']);
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
$admin_temp_password = intval($_SESSION['admin_temp_password'] ?? 0);
$set_message = "";
$create_message = "";
$error_message = "";
$created_temp_password = "";

function generateTempPassword() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
    $length = 10;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_admin') {
        $employee_id = trim($_POST['new_employee_id'] ?? '');
        $fullname = trim($_POST['new_fullname'] ?? '');
        $email = trim($_POST['new_email'] ?? '');
        $role = trim($_POST['new_role'] ?? 'Staff');
        $access_level = trim($_POST['new_access_level'] ?? 'Limited Access');
        $is_active = isset($_POST['new_is_active']) && $_POST['new_is_active'] === '1' ? 1 : 0;

        if ($employee_id === '' || $fullname === '') {
            $error_message = "Employee ID and full name are required to create a new admin.";
        } else {
            $temp_pass = generateTempPassword();
            $hashed_password = password_hash($temp_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (employee_id, fullname, email, password, temporary_password, role, access_level, is_active) VALUES (?, ?, ?, ?, 1, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssssssi", $employee_id, $fullname, $email, $hashed_password, $role, $access_level, $is_active);
                if ($stmt->execute()) {
                    $created_temp_password = $temp_pass;
                    $create_message = "New admin created with a temporary password. Share it securely with the new admin.";
                } else {
                    $error_message = "Unable to create the new admin. Employee ID may already exist.";
                }
                $stmt->close();
            } else {
                $error_message = "Unable to prepare the admin creation request.";
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $edit_id = intval($_POST['edit_user_id'] ?? 0);
        $fullname = trim($_POST['edit_fullname'] ?? '');
        $email = trim($_POST['edit_email'] ?? '');
        $role = trim($_POST['edit_role'] ?? 'Staff');
        $access_level = trim($_POST['edit_access_level'] ?? 'Limited Access');
        $is_active = isset($_POST['edit_is_active']) && $_POST['edit_is_active'] === '1' ? 1 : 0;

        if ($edit_id === 0 || $fullname === '') {
            $error_message = "Please select a user and fill in the required fields.";
        } else {
            $stmt = $conn->prepare("UPDATE admin_users SET fullname = ?, email = ?, role = ?, access_level = ?, is_active = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssii", $fullname, $email, $role, $access_level, $is_active, $edit_id);
                if ($stmt->execute()) {
                    $set_message = "User profile updated successfully.";
                } else {
                    $error_message = "Unable to update the user profile.";
                }
                $stmt->close();
            } else {
                $error_message = "Unable to prepare the update request.";
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'toggle_user') {
        $toggle_id = intval($_POST['toggle_user_id'] ?? 0);
        $toggle_state = isset($_POST['toggle_state']) && $_POST['toggle_state'] === '1' ? 1 : 0;

        if ($toggle_id > 0) {
            $stmt = $conn->prepare("UPDATE admin_users SET is_active = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $toggle_state, $toggle_id);
                $stmt->execute();
                $set_message = $toggle_state ? "User account enabled." : "User account disabled.";
                $stmt->close();
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $delete_id = intval($_POST['delete_user_id'] ?? 0);
        if ($delete_id > 0 && $delete_id !== $admin_id) {
            $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                $set_message = "User account deleted.";
                $stmt->close();
            }
        } else {
            $error_message = "You cannot delete your own account from this screen.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'set_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password === '' || $confirm_password === '') {
            $error_message = "Please enter and confirm your new password.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "The new passwords do not match.";
        } else {
            if ($admin_temp_password) {
                $stmt = $conn->prepare("UPDATE admin_users SET password = ?, temporary_password = 0 WHERE id = ?");
                if ($stmt) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt->bind_param("si", $hashed_password, $admin_id);
                    if ($stmt->execute()) {
                        $set_message = "Permanent password set successfully. You can now log in with your new password.";
                        $_SESSION['admin_temp_password'] = 0;
                        $admin_temp_password = 0;
                    } else {
                        $error_message = "Unable to update your password. Please try again.";
                    }
                    $stmt->close();
                }
            } else {
                if ($current_password === '') {
                    $error_message = "Please enter your current password.";
                } else {
                    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ? LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param("i", $admin_id);
                        $stmt->execute();
                        $stmt->bind_result($stored_password);
                        if ($stmt->fetch() && password_verify($current_password, $stored_password)) {
                            $stmt->close();
                            $stmt = $conn->prepare("UPDATE admin_users SET password = ?, temporary_password = 0 WHERE id = ?");
                            if ($stmt) {
                                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                $stmt->bind_param("si", $hashed_password, $admin_id);
                                if ($stmt->execute()) {
                                    $set_message = "Your password has been updated successfully.";
                                } else {
                                    $error_message = "Unable to update your password. Please try again.";
                                }
                                $stmt->close();
                            }
                        } else {
                            $error_message = "Current password is incorrect.";
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Settings</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #bdc9e0;
            display: flex;
            overflow-x: hidden;
        }
        .sidebar {
            width: 220px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
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
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #3e99e9;
        }
        .main {
            margin-left: 260px;
            padding: 20px;
            flex: 1;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header input {
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ccc;
            width: 250px;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 20px;
        }
        .card {
            background:white;
            padding:25px;
            border-radius:15px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
            transition:0.3s;
            overflow: hidden;
        }
        .card h3 {
            margin-bottom:20px;
        }
        .user-card {
            background: #f7fbff;
            border: 1px solid #dbe8f6;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .user-form {
            display: grid;
            gap: 12px;
        }
        .user-card .setting {
            margin-bottom: 12px;
        }
        .user-card form {
            margin-bottom: 12px;
        }
        .main {
            margin-left: 260px;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .setting {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:18px;
        }
        .setting span {
            font-weight:bold;
        }
        select, 
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            padding:10px;
            border-radius:8px;
            border:1px solid #ccc;
            width:100%;
            margin-top:8px;
        }
        .save-button,
        .create-button {
            margin-top:20px;
            padding:14px 28px;
            background:#6d8edb;
            color:white;
            border:none;
            border-radius:10px;
            cursor:pointer;
            font-size:16px;
        }
        .save-button:hover,
        .create-button:hover {
            opacity:0.9;
        }
        .notice {
            background:#eef4ff;
            border-left:4px solid #6d8edb; 
            padding:14px;
            margin-bottom:20px;
        }
        .error {
            background:#ffe8e8;
            border-left:4px solid #d34545; 
            padding:14px;
            margin-bottom:20px;
            color:#8a2d2d;
        }
        .success {
            background:#e7f6e7;
            border-left:4px solid #2a8530;
            padding:14px;
            margin-bottom:20px;
            color:#1f5c1f;
        }
        .logout-link {
            color:#fff;
            text-decoration:none;
            display:inline-block;
            margin-top:10px;
            background:#365f8c;
            padding:10px 16px;
            border-radius:10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Settings</h2>
        <a href="admin.php">Home</a>
        <a href="Orders.html">Orders</a>
        <a href="Customers.php">Customers</a>
        <a href="products.php">Products</a>
        <a href="Reports.php">Reports</a>
        <a href="Settings.php">Settings</a>
        <a href="admin_logout.php">Logout</a>
    </div>
    <div class="main">
        <div class="header">
            <div>
                <h1>Welcome, <?php echo $admin_name; ?></h1>
                <?php if ($admin_temp_password): ?>
                    <p style="color:#8a2d2d;">You are using a temporary password. Please set a permanent password now.</p>
                <?php endif; ?>
            </div>
            <input type="text" placeholder="Search...">
        </div>
        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($set_message): ?>
            <div class="success"><?php echo htmlspecialchars($set_message); ?></div>
        <?php endif; ?>
        <?php if ($create_message): ?>
            <div class="success"><?php echo htmlspecialchars($create_message); ?></div>
            <div class="notice">Temporary password for new admin: <strong><?php echo htmlspecialchars($created_temp_password); ?></strong></div>
        <?php endif; ?>
        <div class="settings-grid">
            <div class="card">
                <h3><?php echo $admin_temp_password ? 'Set Permanent Password' : 'Change Password'; ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="set_password">
                    <?php if (!$admin_temp_password): ?>
                        <div class="setting">
                            <span>Current Password</span>
                        </div>
                        <input type="password" name="current_password" placeholder="Current password">
                    <?php endif; ?>
                    <div class="setting">
                        <span>New Password</span>
                    </div>
                    <input type="password" name="new_password" placeholder="New password" required>
                    <div class="setting">
                        <span>Confirm Password</span>
                    </div>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                    <button type="submit" class="save-button"><?php echo $admin_temp_password ? 'Set Permanent Password' : 'Save Password'; ?></button>
                </form>
            </div>
            <div class="card">
                <h3>Create New Admin</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_admin">
                    <label>Employee ID</label>
                    <input type="text" name="new_employee_id" placeholder="New admin ID" required>
                    <label>Full Name</label>
                    <input type="text" name="new_fullname" placeholder="New admin name" required>
                    <label>Email</label>
                    <input type="email" name="new_email" placeholder="New admin email">
                    <label>Role</label>
                    <select name="new_role">
                        <?php foreach ($roles as $roleOption): ?>
                            <option value="<?= htmlspecialchars($roleOption) ?>"><?= htmlspecialchars($roleOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Access Level</label>
                    <select name="new_access_level">
                        <?php foreach ($accessLevels as $levelOption): ?>
                            <option value="<?= htmlspecialchars($levelOption) ?>"><?= htmlspecialchars($levelOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Status</label>
                    <select name="new_is_active">
                        <option value="1" selected>Active</option>
                        <option value="0">Disabled</option>
                    </select>
                    <button type="submit" class="create-button">Create Admin</button>
                </form>
                <p style="margin-top:12px; font-size:14px; color:#555;">Each new admin receives a temporary password and must update it here on first login.</p>
            </div>
        </div>

        <div class="card">
            <h3>User Accounts</h3>
            <?php
            $accounts = [];
            $result = $conn->query("SELECT id, employee_id, fullname, email, role, access_level, is_active FROM admin_users ORDER BY id ASC");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $accounts[] = $row;
                }
            }
            ?>
            <?php if (count($accounts) === 0): ?>
                <p>No admin user accounts found.</p>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                    <div class="user-card">
                        <form method="POST" class="user-form">
                            <input type="hidden" name="action" value="edit_user">
                            <input type="hidden" name="edit_user_id" value="<?= intval($account['id']) ?>">
                            <div class="setting"><span>Employee ID</span><span><?= htmlspecialchars($account['employee_id']) ?></span></div>
                            <label>Full Name</label>
                            <input type="text" name="edit_fullname" value="<?= htmlspecialchars($account['fullname']) ?>" required>
                            <label>Email</label>
                            <input type="email" name="edit_email" value="<?= htmlspecialchars($account['email']) ?>">
                            <label>Role</label>
                            <select name="edit_role">
                                <?php foreach ($roles as $roleOption): ?>
                                    <option value="<?= htmlspecialchars($roleOption) ?>" <?= $roleOption === $account['role'] ? 'selected' : '' ?>><?= htmlspecialchars($roleOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label>Access Level</label>
                            <select name="edit_access_level">
                                <?php foreach ($accessLevels as $levelOption): ?>
                                    <option value="<?= htmlspecialchars($levelOption) ?>" <?= $levelOption === $account['access_level'] ? 'selected' : '' ?>><?= htmlspecialchars($levelOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label>Status</label>
                            <select name="edit_is_active">
                                <option value="1" <?= intval($account['is_active']) === 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= intval($account['is_active']) === 0 ? 'selected' : '' ?>>Disabled</option>
                            </select>
                            <button type="submit" class="save-button">Save</button>
                        </form>
                        <form method="POST" style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                            <input type="hidden" name="action" value="toggle_user">
                            <input type="hidden" name="toggle_user_id" value="<?= intval($account['id']) ?>">
                            <input type="hidden" name="toggle_state" value="<?= intval($account['is_active']) === 1 ? 0 : 1 ?>">
                            <button type="submit" class="create-button" style="background:#f09f1a;"><?= intval($account['is_active']) === 1 ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="POST" style="margin-top:10px;">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="delete_user_id" value="<?= intval($account['id']) ?>">
                            <button type="submit" class="create-button" style="background:#d9534f;">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div style="margin-top:20px;">
                <h4>Role-Based Access Control (RBAC)</h4>
                <?php foreach ($rolePermissions as $roleName => $permissionText): ?>
                    <div style="margin-bottom:10px;">
                        <strong><?= htmlspecialchars($roleName) ?></strong>: <?= htmlspecialchars($permissionText) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>