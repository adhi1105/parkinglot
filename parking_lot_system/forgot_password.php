<?php
session_start();
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "parking_lot");
    if ($conn->connect_error) {
        die("Connection failed: ". $conn->connect_error);
    }

    $email = $_POST['email'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // --- Generate Token ---
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", time() + 3600); // Token valid for 1 hour

        // --- Store Token in DB ---
        $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $token, $expiry, $email);
        $update_stmt->execute();

        // --- Send Email ---
        $reset_link = "http://localhost/parking_lot_system/reset_password.php?token=" . $token;
        $subject = "Password Reset Request - Parking Lot Management";
        $body = "Click the link to reset your password: " . $reset_link . "\n\nThis link is valid for 1 hour.";
        $headers = "From: no-reply@parking-lot.com";

        if (mail($email, $subject, $body, $headers)) {
            $message = "✅ A password reset link has been sent to your email.";
        } else {
            $error = "❌ Failed to send reset email. Please try again later.";
        }
    } else {
        $error = "❌ No account found with that email address.";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        body {font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: url('parking.jpg') no-repeat center center fixed; background-size: cover;}
        body::before {content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: -1;}
        nav {background: rgba(0, 0, 0, 0.8); padding: 15px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);}
        nav a {color: white; margin: 0 20px; text-decoration: none; font-weight: bold; font-size: 18px; transition: color 0.3s;}
        nav a:hover { color: #ffd700; }
        .container {max-width: 400px; background: rgba(255,255,255,0.95); padding: 30px; margin: 80px auto; border-radius: 15px; box-shadow: 0 6px 20px rgba(0,0,0,0.4); text-align: center;}
        .container h2 {color: #2a5298; margin-bottom: 20px;}
        input[type="email"] {width: 100%; padding: 12px; margin: 12px 0; border: 1px solid #ccc; border-radius: 8px; font-size: 16px;}
        button {width: 100%; padding: 14px; background: linear-gradient(135deg, #2a5298, #1e3c72); color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; font-weight: bold; transition: background 0.3s, transform 0.2s;}
        button:hover { background: linear-gradient(135deg, #1e3c72, #162950); transform: scale(1.05); }
        .error {color: red; margin-bottom: 15px; font-weight: bold;}
        .success {color: green; background: #e0ffe0; border: 1px solid green; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: bold;}
        .back {display: block; margin-top: 15px; color: #2a5298; text-decoration: none; font-weight: bold;}
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <a href="login.php">🔑 Login</a>
    <a href="register.php">📝 Register</a>
</nav>
<div class="container">
    <h2>🔒 Reset Password</h2>
    <p style="color: #333;">Enter your email, and we will send you a link to reset your password.</p>
    
    <?php if (!empty($message)) echo "<p class='success'>$message</p>"; ?>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your registered email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <a href="login.php" class="back">⬅ Back to Login</a>
</div>
</body>
</html>