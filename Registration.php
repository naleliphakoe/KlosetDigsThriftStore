<?php
session_start();
$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "klosetdigsdb";
$register_error = "";

$conn = new mysqli($db_server, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    age INT NULL,
    dob DATE NULL,
    gender VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if (isset($_SESSION['user_id'])) {
    header("Location: Thrift.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $age = trim($_POST['age'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    if ($fullname === '' || $email === '' || $password === '' || $confirm_password === '') {
        $register_error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match. Please try again.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $register_error = "An account already exists with that email.";
                $stmt->close();
            } else {
                $stmt->close();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, age, dob, gender) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssiss", $fullname, $email, $hashed_password, $age, $dob, $gender);
                    if ($stmt->execute()) {
                        $_SESSION['user_id'] = $stmt->insert_id;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_name'] = $fullname;
                        header("Location: Thrift.php");
                        exit();
                    }
                    $register_error = "Unable to create account. Please try again.";
                    $stmt->close();
                } else {
                    $register_error = "Unable to create account. Please try again.";
                }
            }
        } else {
            $register_error = "Unable to process registration. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration page</title>
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
        background-color: #c4d2de;
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
    <form method="POST">
        <h2>Create Account</h2>

        <?php if ($register_error): ?>
            <p class="error" style="color: red; margin: 10px 0;"><?= htmlspecialchars($register_error) ?></p>
        <?php endif; ?>

        <label>Full Name:</label>
        <input type="text" name="fullname" placeholder="Enter full name" required><br><br>

        <label>Email:</label>
        <input type="email" name="email" placeholder="example@gmail.com" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" required><br><br>

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required><br><br>

        <label>Age:</label>
        <input type="number" name="age"><br><br>

        <label>Date of Birth:</label>
        <input type="date" name="dob"><br><br>

        <label>Gender:</label><br>
        <input type="radio" name="gender" value="Male" class="inline"> Male
        <input type="radio" name="gender" value="Female" class="inline"> Female
        <input type="radio" name="gender" value="Other" class="inline"> Other<br><br>

        <button type="submit">Register</button>

        <p class="switch">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>
    </form>
</body>
</html>       