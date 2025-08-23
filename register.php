<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Video Capture</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="dashboard_styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 80px);">
        <div class="card" style="max-width: 400px; width: 100%; padding: 30px; box-sizing: border-box;">
            <h2 style="text-align: center; margin-bottom: 20px; color: #2c3e50;">Create an Account</h2>
            <p style="text-align: center; margin-bottom: 30px; color: #555;">Join us to start managing your videos.</p>
            <form action="register.php" method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Register</button>
            </form>
            <div class="links" style="text-align: center; margin-top: 20px;">
                <a href="index.php" style="color: #3498db; text-decoration: none;">Already have an account? <b>Login here</b></a>
            </div>
        </div>
    </main>

    <?php
    $conn = require 'db.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $password);

        if ($stmt->execute()) {
            session_start();
            $_SESSION['registration_success'] = "Registration successful! You can now login.";
            header('Location: index.php');
            exit;
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();
    ?>
    <script src="auth.js"></script>
</body>
</html>