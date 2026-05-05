<?php
session_start();
require 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = md5($_POST['password']); 

    $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['role'] = $row['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Student Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Center the login box and give it a dark background */
        body {
            justify-content: center;
            align-items: center;
            background-color: #2c3e50;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-card h2 { margin-bottom: 20px; color: #2c3e50; }
        .login-card button { width: 100%; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>🎓 SMS Login</h2>
        <?php if($error) echo "<p style='color:red; margin-bottom:15px;'>$error</p>"; ?>
        
        <form method="POST" action="" style="box-shadow: none; padding: 0;">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>