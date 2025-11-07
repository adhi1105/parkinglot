<?php 
session_start(); 
$logged_in = isset($_SESSION['username']);
$available_slots = 0;
$role = $_SESSION['role'] ?? 'guest'; // Get role

// If user is logged in, connect to DB to get slot count
if ($logged_in) {
    $conn = new mysqli("localhost", "root", "", "parking_lot");
    if (!$conn->connect_error) {
        $result = $conn->query("SELECT COUNT(*) as count FROM parking_slots WHERE status='Available'");
        if ($result) {
            $available_slots = $result->fetch_assoc()['count'];
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parking Lot Management System</title>
    <style>
        /* All your existing CSS is fine */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('parking.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
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
            position: sticky;
            top: 0;
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
        nav a:hover {
            color: #ffd700;
        }
        .container {
            max-width: 650px;
            background: rgba(255,255,255,0.9);
            padding: 40px;
            margin: 120px auto;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }
        .container h1 {
            color: #2a5298;
            margin-bottom: 20px;
            font-size: 36px;
        }
        .container h3 {
            color: green;
            font-size: 24px;
            margin-bottom: 30px;
        }
        .container p {
            font-size: 18px;
            color: #444;
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            margin: 10px;
            padding: 12px 25px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            background: linear-gradient(135deg, #2a5298, #1e3c72);
            color: white;
            transition: background 0.3s, transform 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .button.book {
             background: linear-gradient(135deg, #28a745, #218838);
        }
        .button.book:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: scale(1.05);
        }
        .button:hover {
            background: linear-gradient(135deg, #1e3c72, #162955);
            transform: scale(1.05);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <?php if ($logged_in): ?>
        <?php if ($role == 'admin'): ?>
            <a href="slot_management.php">📊 Slot Management</a>
            <a href="manage_users.php">👤 Manage Users</a>
        <?php else: ?>
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="book.php">🅿️ Book Slot</a>
        <?php endif; ?>
        <a href="account.php">⚙️ Account</a>
        <a href="logout.php">🚪 Logout</a>
    <?php else: ?>
        <a href="login.php">🔑 Login</a>
        <a href="register.php">📝 Register</a>
    <?php endif; ?>
    <a href="about.php">ℹ️ About</a>
</nav>
<div class="container">
    <h1>🚗 Parking Lot Management</h1>

    <?php if ($logged_in): ?>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        
        <?php if ($role == 'admin'): ?>
            <p>You are logged in as an administrator.</p>
            <a href="slot_management.php" class="button">Go to Slot Management</a>
        <?php else: ?>
            <h3>Available Slots: <?php echo $available_slots; ?></h3>
            <a href="book.php" class="button book">🅿️ Book a Slot</a>
            <a href="dashboard.php" class="button">View Dashboard</a>
        <?php endif; ?>
        
    <?php else: ?>
        <p>Welcome! Manage parking slots easily and securely.</p>
        <a href="login.php" class="button">Login</a>
        <a href="register.php" class="button">Register</a>
    <?php endif; ?>

</div>
</body>
</html>