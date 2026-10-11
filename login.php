<?php
session_start();
$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "KlosetDigsdb";
$login_error = "";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function clean_redirect(mixed $path): string
{
    $path = trim((string)$path);
    $path = basename(parse_url($path, PHP_URL_PATH) ?: '');
    $allowed = ['Thrift.php', 'Selling.php', 'Registration.php'];
    return in_array($path, $allowed, true) ? $path : 'Thrift.php';
}


$redirect_to = clean_redirect($_GET['next'] ?? 'Thrift.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect_to = clean_redirect($_POST['next'] ?? $redirect_to);

    if ($email === '' || $password === '') {
        $login_error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 1) {

            $hashed_password = '';

            $stmt->bind_result($user_id, $fullname, $hashed_password);
            $stmt->fetch();
            if (!empty($hashed_password) && password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $fullname ?: $email;
                $_SESSION['user_email'] = $email;
                header("Location: $redirect_to");
                exit();
                }
        }
            $login_error = 'Invalid email or password.';
            $stmt->close();
        } else {
            $login_error = 'Unable to process login. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
        font-family: Arial, sans-serif;
        background-color: #bdc9e0; 
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    form {
        background-color: #ffffff;
        padding: 30px;
        border-radius: 15px;
        width: 320px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 1px solid #9cd3dc;
    }
    h1 {
        text-align: center;
        color: #8ccbd2; 
        margin-bottom: 20px;
    }
    h2 {
        text-align: center;
        color: #8ccbd2; 
        margin-bottom: 20px;
    }
    label {
        font-size: 14px;
        color: #555;
    }
    input {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border-radius: 8px;
        border: 1px solid #ccc;
        background-color: #d2dae1;
    }
    input:focus {
        outline: none;
        border-color: #a5a58d;
    }
    .inline {
        width: auto;
        margin-right: 5px;
    }
     button {
        width: 100%;
        padding: 10px;
        background-color: #7fa2f2; 
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
    }
    button:hover {
        background-color: #7d9499;
    }
    </style>

    </head>
    <body>

    <form method="POST" action="login.php">
        <h2>Welcome back</h2>
        <h6>Login to your account</h6>

        <?php if ($login_error): ?>
            <p style="color: #b91c1c; margin: 12px 0;"><?= htmlspecialchars($login_error) ?></p>
        <?php endif; ?>

        <label>Email:</label>
        <input type="email" name="email" placeholder="Enter your email" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" placeholder="Enter your password" required><br><br>

        <input type="hidden" name="next" value="<?= htmlspecialchars($redirect_to) ?>">

        <button type="submit">Login</button>

        <p class="switch">
            Don't have an account?
            <a href="Registration.php">Sign up</a>
        </p>

        <p class="switch" style="margin-top:12px; text-align:center;">
            <a href="forgot_password.php">Forgot password?</a>
        </p>
    </form>
</body>
</html>
