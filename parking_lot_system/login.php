<?php
session_start();

// --- START OF SUCCESS MESSAGE LOGIC ---
// Check if a registration success message is in the session
$success_msg = "";
if (isset($_SESSION['registration_success'])) {
    $success_msg = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']); // Clear it so it doesn't show again
}
// --- ADDED THIS FOR RESET SUCCESS ---
if (isset($_SESSION['reset_success'])) {
    $success_msg = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']); 
}
// --- END OF CHANGE ---

$error = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "parking_lot");

    if ($conn->connect_error) {
        die("Connection failed: ". $conn->connect_error);
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    // ... (rest of your PHP login logic) ...
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            
            // --- START OTP LOGIC ---
            $otp = rand(100000, 999999);
            $user_email = $row['email'];

            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = time() + 300; 
            $_SESSION['temp_user_id'] = $row['id'];
            $_SESSION['temp_username'] = $row['username'];
            $_SESSION['temp_role'] = $row['role'];

            $subject = "Your Login OTP - Parking Lot Management";
            $message = "Your 6-digit verification code is: $otp";
            $headers = "From: no-reply@parking-lot.com"; 

            if (mail($user_email, $subject, $message, $headers)) {
                header("Location: verify_otp.php");
                exit();
            } else {
                $error = "❌ Failed to send OTP email. Contact admin.";
            }
            // --- END OTP LOGIC ---

        } else {
            $error = "❌ Invalid username or password!";
        }
    } else {
        $error = "❌ Invalid username or password!";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Parking Lot Management</title>
    <style>
        /* ... (all your existing CSS) ... */
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
        .success {
            color: green;
            background: #e0ffe0;
            border: 1px solid green;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
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
    <h2>🚗 Login</h2>

    <?php if (!empty($success_msg)) echo "<p class='success'>$success_msg</p>"; ?>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    
    <form method="POST">
        <input type="text" name="username" placeholder="Enter Username" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit">Login</button>
    </form>
    
    <a href="forgot_password.php" class="back" style="margin-top: 20px;">Forgot Password?</a>
    <a href="index.php" class="back">⬅ Back to Home</a>
    </div>
</body>
</html>