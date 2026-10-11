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

$conn->query("CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    fullname VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    password VARCHAR(255) NOT NULL,
    temporary_password TINYINT(1) NOT NULL DEFAULT 1,
    role VARCHAR(50) NULL DEFAULT 'Super Admin',
    access_level VARCHAR(50) NULL DEFAULT 'Full Access',
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

ensureAdminColumn($conn, 'role', "role VARCHAR(50) NULL DEFAULT 'Super Admin'");
ensureAdminColumn($conn, 'access_level', "access_level VARCHAR(50) NULL DEFAULT 'Full Access'");
ensureAdminColumn($conn, 'is_active', "is_active TINYINT(1) NOT NULL DEFAULT 1");
ensureAdminColumn($conn, 'permissions', "permissions TEXT NULL");

$admin_count = 0;
$result = $conn->query("SELECT COUNT(*) AS count FROM admin_users");
if ($result) {
    $row = $result->fetch_assoc();
    $admin_count = intval($row['count']);
}

$login_error = "";
$create_message = "";
$created_temp_password = "";
$show_first_admin_form = $admin_count === 0;

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
    if (isset($_POST['create_first_admin'])) {
        $employee_id = trim($_POST['employee_id'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($employee_id === '' || $fullname === '') {
            $login_error = "Please provide an employee ID and name to create the first admin.";
        } else {
            $temp_pass = generateTempPassword();
            $hashed_password = password_hash($temp_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (employee_id, fullname, email, password, temporary_password, role, access_level, is_active) VALUES (?, ?, ?, ?, 1, 'Super Admin', 'Full Access', 1)");
            if ($stmt) {
                $stmt->bind_param("ssss", $employee_id, $fullname, $email, $hashed_password);
                if ($stmt->execute()) {
                    $created_temp_password = $temp_pass;
                    $create_message = "First admin created. Save the temporary password below and log in to set a permanent password.";
                    $show_first_admin_form = false;
                } else {
                    $login_error = "Unable to create the initial admin. Please try a different employee ID.";
                }
                $stmt->close();
            } else {
                $login_error = "Unable to prepare the statement. Please try again later.";
            }
        }
    } else {
        $employee_id = trim($_POST['employee_id'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($employee_id === '' || $password === '') {
            $login_error = "Please enter both your employee ID and password.";
        } else {
            $stmt = $conn->prepare("SELECT id, fullname, password, temporary_password FROM admin_users WHERE employee_id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $employee_id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $hashed_password = '';
                    $stmt->bind_result($admin_id, $fullname, $hashed_password, $temporary_password);
                    $stmt->fetch();

                    if (password_verify($password, $hashed_password)) {
                        $_SESSION['admin_id'] = $admin_id;
                        $_SESSION['admin_name'] = $fullname;
                        $_SESSION['admin_employee_id'] = $employee_id;
                        $_SESSION['admin_temp_password'] = intval($temporary_password);

// Log the login attempt (create table if missing)
                        $conn->query("CREATE TABLE IF NOT EXISTS admin_login_logs (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            admin_id INT NOT NULL,
                            employee_id VARCHAR(50) NOT NULL,
                            fullname VARCHAR(255) NULL,
                            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        $log_stmt = $conn->prepare("INSERT INTO admin_login_logs (admin_id, employee_id, fullname, login_time) VALUES (?, ?, ?, NOW())");
                        if ($log_stmt) {
                            $log_stmt->bind_param("iss", $admin_id, $employee_id, $fullname);
                            $log_stmt->execute();
                            $log_stmt->close();
                        }

                        if ($temporary_password) {
                            header("Location: Settings.php?force_change=1");
                            exit();
                        }

                        header("Location: admin.php");
                        exit();
                    }
                }

                $login_error = "Invalid employee ID or password.";
                $stmt->close();
            } else {
                $login_error = "Unable to prepare login. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>

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
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            overflow: hidden;
            display: flex;
            max-width: 900px;
            width: 100%;
        }
        .login-left, .login-right {
            padding: 40px;
        }
        .login-left {
            background: linear-gradient(160deg, #7fa2f2, #8ccbd2);
            color: white;
            width: 45%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-left .logo {
            width: 140px;
            height: 140px;
            background: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #7fa2f2;
            font-size: 40px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .login-left h2 {
            margin-bottom: 10px;
        }
        .login-left p {
            opacity: 0.9;
        }
        .login-right {
            width: 55%;
        }
        .login-right h1 {
            color: #7fa2f2;
            margin-bottom: 20px;
        }
        .login-right label {
            font-size: 14px; 
            color: #555;
        }
        .login-right input {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            border-radius: 10px;
            border: 1px solid #ccc;
            background: #f2f6fb;
        }
        .login-right button {
            width: 100%; padding:
            12px; border: none;
            border-radius: 12px;
            background: #7fa2f2;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .login-right button:hover {
            opacity: 0.95;
        }
        .notice {
            background: #eef5ff;
            border-left: 4px solid #7fa2f2;
            padding: 12px;
            margin-bottom: 20px;}
        .error {
            color: #c43d3d;
            margin-bottom: 15px;
        }
        .success {
            color: #1b6c32;
            margin-bottom: 15px;
        }
        .temp-pass {
            font-weight: bold;
            color: #233d8f;
        }
        .redirect {
            font-size: 14px;
            margin-top: 12px;
        }
        @media(max-width: 900px) {
            .login-container {
                flex-direction: column;
            } 
            .login-left, 
            .login-right {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-left">
        <div class="logo">AD</div>
        <h2>Thrift Admin</h2>
        <p>Log in with your employee ID and password. New admins receive a temporary password and must update it in Settings.</p>
    </div>
    <div class="login-right">
        <h1>Admin Login</h1>
        <?php if ($login_error): ?>
            <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <form method="POST">

            <label>Employee ID:</label>
            <input type="text" name="employee_id" placeholder="Enter employee ID" required>

            <label>Password:</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <button type="submit">Login</button>
        </form>

        <?php if ($create_message): ?>
            <div class="success"><?php echo htmlspecialchars($create_message); ?></div>
            <div class="notice">Temporary password: 
            <span class="temp-pass"><?php echo htmlspecialchars($created_temp_password); ?></span></div>
            <div class="redirect">Use this password now and go to Settings to set a permanent password.</div>
        <?php endif; ?>

        <?php if ($show_first_admin_form): ?>
            <div class="notice">No admin accounts found. Create the first admin below.</div>
            <form method="POST">
                <input type="hidden" name="create_first_admin" value="1">
                <label>Employee ID:</label>
                <input type="text" name="employee_id" placeholder="Admin ID" required>
                <label>Full Name:</label>
                <input type="text" name="fullname" placeholder="Admin name" required>
                <label>Email (optional):</label>
                <input type="email" name="email" placeholder="admin@example.com">
                <button type="submit">Create Admin</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>