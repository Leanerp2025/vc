<?php
session_start();

$config = require 'config.php';

$registration_success_message = '';
if (isset($_SESSION['registration_success'])) {
    $registration_success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = require 'db.php';

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['email'] = $email;
        $_SESSION['id'] = $id;
        header('Location: dashboard.php');
    } else {
        $error = "Invalid email or password!";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Video Capture</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 80px);">
        <div class="card" style="max-width: 400px; width: 100%; padding: 30px; box-sizing: border-box;">
            <h2 style="text-align: center; margin-bottom: 20px; color: #2c3e50;">Welcome Back</h2>
            <p style="text-align: center; margin-bottom: 30px; color: #555;">Login to access your dashboard.</p>
            <form action="index.php" method="post">
                <?php if (!empty($registration_success_message)) { echo "<p class='success-msg' style='text-align: center; color: #27ae60; margin-bottom: 15px;'>" . $registration_success_message . "</p>"; } ?>
                <?php if (isset($error)) { echo "<p class='error-msg' style='text-align: center; color: #e74c3c; margin-bottom: 15px;'>" . $error . "</p>"; } ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Login</button>
            </form>
            <div class="links" style="text-align: center; margin-top: 20px;">
                <a href="register.php" style="color: #3498db; text-decoration: none;">Don't have an account? <b>Register here</b></a>
            </div>
        </div>
    </main>
    <script src="auth.js"></script>
</body>
</html>