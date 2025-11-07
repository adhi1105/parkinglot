<?php
session_start();
$error = "";
$token_valid = false;

if (!isset($_GET['token'])) {
    $error = "❌ No token provided. Invalid link.";
} else {
    $token = $_GET['token'];
    
    $conn = new mysqli("localhost", "root", "", "parking_lot");
    if ($conn->connect_error) {
        die("Connection failed: ". $conn->connect_error);
    }

    // Check if token is valid and not expired
    $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token_valid = true;
    } else {
        $error = "❌ Invalid or expired token. Please request a new link.";
    }
    $stmt->close();

    // Handle the new password submission
    if ($token_valid && $_SERVER["REQUEST_METHOD"] == "POST") {
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if ($password !== $password_confirm) {
            $error = "❌ Passwords do not match.";
        } else {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Update the password and clear the token
            $update_sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $hashed_password, $token);
            $update_stmt->execute();
            
            $_SESSION['reset_success'] = "✅ Your password has been reset! You can now log in.";
            header("Location: login.php");
            exit();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <style>
        body {font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: url('parking.jpg') no-repeat center center fixed; background-size: cover;}
        body::before {content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: -1;}
        nav {background: rgba(0, 0, 0, 0.8); padding: 15px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);}
        nav a {color: white; margin: 0 20px; text-decoration: none; font-weight: bold; font-size: 18px; transition: color 0.3s;}
        nav a:hover { color: #ffd700; }
        .container {max-width: 400px; background: rgba(255,255,255,0.95); padding: 30px; margin: 80px auto; border-radius: 15px; box-shadow: 0 6px 20px rgba(0,0,0,0.4); text-align: center;}
        .container h2 {color: #2a5298; margin-bottom: 20px;}
        input[type="password"] {width: 100%; padding: 12px; margin: 12px 0; border: 1px solid #ccc; border-radius: 8px; font-size: 16px;}
        button {width: 100%; padding: 14px; background: linear-gradient(135deg, #2a5298, #1e3c72); color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; font-weight: bold; transition: background 0.3s, transform 0.2s;}
        button:hover { background: linear-gradient(135deg, #1e3c72, #162950); transform: scale(1.05); }
        .error {color: red; margin-bottom: 15px; font-weight: bold;}
    </style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <a href="login.php">🔑 Login</a>
</nav>
<div class="container">
    <h2>🔒 Set New Password</h2>
    
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <?php if ($token_valid): ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter new password" required>
            <input type="password" name="password_confirm" placeholder="Confirm new password" required>
            <button type="submit">Update Password</button>
        </form>
    <?php else: ?>
        <a href="forgot_password.php" style="color: #2a5298;">Request a new link</a>
    <?php endif; ?>
    
</div>
</body>
</html>