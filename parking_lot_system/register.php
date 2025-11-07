<?php
$error = ""; // Variable to store error messages
$success = ""; // Variable to store success message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "parking_lot");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = $_POST['username'];
    $email = $_POST['email']; 
    $password = $_POST['password'];

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        // --- START OF CHANGE ---
        // Set a success message in the session and redirect to login
        session_start();
        $_SESSION['registration_success'] = "Registration successful! Please log in.";
        header("Location: login.php");
        exit(); 
        // --- END OF CHANGE ---
    } else {
        // --- START OF ERROR HANDLING ---
        if ($conn->errno == 1062) {
            // 1062 is the MySQL error code for "Duplicate entry"
            if (strpos($conn->error, 'username')) {
                $error = "❌ This username is already taken. Please choose another.";
            } elseif (strpos($conn->error, 'email')) {
                $error = "❌ This email address is already registered.";
            } else {
                $error = "❌ Duplicate entry. Please check your details.";
            }
        } else {
            // Other database error
            $error = "Error: " . $stmt->error;
        }
        // --- END OF ERROR HANDLING ---
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Parking Lot Management</title>
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
        input[type="text"], input[type="email"], input[type="password"] {
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
    <a href="login.php">🔑 Login</a>
    <a href="register.php">📝 Register</a>
    <a href="about.php">ℹ️ About</a>
</nav>

<div class="container">
    <h2>📝 Register</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
    <a href="login.php" class="back">⬅ Back to Login</a>
</div>
</body>
</html>