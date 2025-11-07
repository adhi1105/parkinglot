<?php
session_start();

// If user hasn't started the login process, redirect them
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = ""; // Define error variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = $_POST['otp'];

    // Check if OTP has expired
    if (time() > $_SESSION['otp_expiry']) {
        $error = "❌ OTP has expired. Please log in again.";
        // Clear all temporary session data
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user_id'], $_SESSION['temp_username'], $_SESSION['temp_role']);
    } 
    // Check if OTP is correct
    elseif ($user_otp == $_SESSION['otp']) {
        
        // ✅ VULNERABILITY FIX 3: Regenerate session ID
        session_regenerate_id(true);

        // SUCCESS: Log the user in
        $_SESSION['user_id'] = $_SESSION['temp_user_id']; // This is the new line we needed
        $_SESSION['username'] = $_SESSION['temp_username'];
        $_SESSION['role'] = $_SESSION['temp_role'];

        // Clear all temporary session data
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user_id'], $_SESSION['temp_username'], $_SESSION['temp_role']);

        // Redirect to the main dashboard (or admin page)
        if ($_SESSION['role'] == 'admin') {
            header("Location: slot_management.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $error = "❌ Invalid OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP - Parking Lot Management</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0; padding: 0;
            background: url('parking.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: -1;
        }
        nav {
            background: rgba(0, 0, 0, 0.8);
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        nav a {
            color: white;
            margin: 0 20px;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
            transition: color 0.3s;
        }
        nav a:hover { color: #ffd700; }
        .container {
            max-width: 400px;
            background: rgba(255,255,255,0.95);
            padding: 30px;
            margin: 80px auto;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
            text-align: center;
        }
        .container h2 {
            color: #2a5298;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"] {
            width: 80%; padding: 8px; margin: 12px 0;
            border: 1px solid #ccc; border-radius: 8px;
            font-size: 16px;
        }
        button {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #2a5298, #1e3c72);
            color: white; border: none;
            border-radius: 8px; font-size: 18px;
            cursor: pointer; font-weight: bold;
            transition: background 0.3s, transform 0.2s;
        }
        button:hover { background: linear-gradient(135deg, #1e3c72, #162950); transform: scale(1.05); }
        .error {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .info {
            color: #333;
            margin-bottom: 15px;
        }
        .back {
            display: block; margin-top: 15px;
            color: #2a5298; text-decoration: none; font-weight: bold;
        }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <a href="register.php">📝 Register</a>
    <a href="about.php">ℹ️ About</a>
</nav>

<div class="container">
    <h2>🔒 OTP Verification</h2>
    <p class="info">A 6-digit OTP has been sent to your registered email.</p>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="otp" placeholder="Enter 6-Digit OTP" required>
        <button type="submit">Verify</button>
    </form>
    <a href="login.php" class="back">⬅ Back to Login</a>
</div>
</body>
</html>