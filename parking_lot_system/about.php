<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About - Parking Lot Management</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('parkinglot.jpeg') no-repeat center center fixed;
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
        nav a:hover { color: #ffd700; }
        .container {
            max-width: 700px;
            background: rgba(255,255,255,0.9);
            padding: 40px;
            margin: 100px auto;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
            animation: fadeIn 1s ease-in-out;
        }
        .container h1 {
            color: #2a5298;
            margin-bottom: 20px;
            font-size: 32px;
            text-align: center;
        }
        .container p {
            font-size: 18px;
            color: #444;
            line-height: 1.6;
            margin-bottom: 20px;
            text-align: justify;
        }
        .highlight {
            color: #2a5298;
            font-weight: bold;
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
    
    <?php if (isset($_SESSION['user_id'])): // Check if logged in ?>
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="slot_management.php">📊 Slot Management</a>
        <?php else: ?>
            <a href="dashboard.php">📊 Dashboard</a>
        <?php endif; ?>
        <a href="account.php">👤 Account</a>
        <a href="logout.php">🚪 Logout</a>
    <?php else: // Not logged in ?>
        <a href="login.php">🔑 Login</a>
        <a href="register.php">📝 Register</a>
    <?php endif; ?>
    
    <a href="about.php">ℹ️ About</a>
</nav>

<div class="container">
    <h1>ℹ️ About Our Parking Lot Management System</h1>
    <p>
        Our <span class="highlight">Parking Lot Management System</span> is designed to provide 
        a modern, reliable, and user-friendly solution for managing parking spaces. Whether you 
        are a facility owner, manager, or driver, our system ensures parking is handled 
        <span class="highlight">efficiently, securely, and hassle-free</span>.
    </p>
    <p>
        Drivers can easily register, log in, and reserve parking slots in advance, while 
        administrators can track available spaces and monitor vehicle entries. This helps 
        reduce congestion, improve customer experience, and optimize parking operations.
    </p>
    <p>
        By using this system, parking facilities can offer a more organized and 
        <span class="highlight">technology-driven service</span> that saves time, improves safety, 
        and enhances convenience for everyone.
    </p>
</div>
</body>
</html>
