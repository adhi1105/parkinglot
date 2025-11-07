<?php
session_start();
if (!isset($_SESSION['user_id'])) { // Check for user_id
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "parking_lot");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$details_error = "";
$details_success = "";
$pass_error = "";
$pass_success = "";

// --- FORM 1: Handle Profile Detail Update ---
if (isset($_POST['update_details'])) {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_car_no = $_POST['car_no'] ?? null; // Handle if admin
    
    // Check for duplicate username or email (but not for ourself)
    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $details_error = "❌ Username or email already taken by another user.";
    } else {
        // Update user
        $stmt_update = $conn->prepare("UPDATE users SET username = ?, email = ?, car_no = ? WHERE id = ?");
        $stmt_update->bind_param("sssi", $new_username, $new_email, $new_car_no, $user_id);
        if ($stmt_update->execute()) {
            $_SESSION['username'] = $new_username; // Update session
            $details_success = "✅ Details updated successfully!";
        } else {
            $details_error = "❌ Error updating details.";
        }
        $stmt_update->close();
    }
    $stmt->close();
}

// --- FORM 2: Handle Password Update ---
if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Get current hashed password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $hashed_password_from_db = $row['password'];

    // Verify current password
    if (password_verify($current_pass, $hashed_password_from_db)) {
        if ($new_pass === $confirm_pass) {
            // Hash new password
            $new_hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
            
            // Update password in DB
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_hashed_password, $user_id);
            if ($stmt_update->execute()) {
                $pass_success = "✅ Password changed successfully!";
            } else {
                $pass_error = "❌ Error changing password.";
            }
            $stmt_update->close();
        } else {
            $pass_error = "❌ New passwords do not match.";
        }
    } else {
        $pass_error = "❌ Incorrect current password.";
    }
    $stmt->close();
}

// Fetch current user data to show in forms
$stmt = $conn->prepare("SELECT username, email, car_no FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Settings</title>
<style>
/* Using styles from your other pages */
body {font-family: Arial, sans-serif; background: #f0f2f5; margin:0;}
nav {background: #2a5298; padding: 15px; text-align: center;}
nav a {color: white; margin: 0 20px; text-decoration: none; font-weight: bold; font-size: 18px;}
nav a:hover {color: #ffd700;}
.container {max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);}
h1, h2 {color: #2a5298; text-align: center;}
.form-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}
input[type="text"], input[type="email"], input[type="password"] {
    width: 95%; 
    padding: 12px; 
    margin: 10px 0;
    border: 1px solid #ccc; 
    border-radius: 8px;
    font-size: 16px;
}
button {
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, #2a5298, #1e3c72);
    color: white; border: none;
    border-radius: 8px; font-size: 18px;
    cursor: pointer; font-weight: bold;
    margin-top: 10px;
}
button:hover { background: linear-gradient(135deg, #1e3c72, #162950); }
.error {color: red; font-weight: bold; text-align: center;}
.success {color: green; font-weight: bold; text-align: center;}
</style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="slot_management.php">📊 Slot Management</a>
        <a href="manage_users.php">👤 Manage Users</a>
    <?php else: ?>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="book.php">🅿️ Book Slot</a>
    <?php endif; ?>
    <a href="account.php">⚙️ Account</a>
    <a href="logout.php">🚪 Logout</a>
</nav>
<div class="container">
    <h1>Account Settings</h1>

    <div class="form-section">
        <h2>Profile Details</h2>
        <?php if ($details_error) echo "<p class='error'>$details_error</p>"; ?>
        <?php if ($details_success) echo "<p class='success'>$details_success</p>"; ?>
        <form method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
            
            <label for="email">Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
            
            <?php if ($_SESSION['role'] == 'user'): // Only show car_no for users ?>
                <label for="car_no">Car Number</label>
                <input type="text" name="car_no" value="<?php echo htmlspecialchars($user_data['car_no'] ?? ''); ?>">
            <?php endif; ?>
            
            <button type="submit" name="update_details">Save Details</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Change Password</h2>
        <?php if ($pass_error) echo "<p class='error'>$pass_error</p>"; ?>
        <?php if ($pass_success) echo "<p class='success'>$pass_success</p>"; ?>
        <form method="POST">
            <label for="current_password">Current Password</label>
            <input type="password" name="current_password" placeholder="Enter your current password" required>
            
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" placeholder="Enter a new password" required>
            
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm the new password" required>
            
            <button type="submit" name="update_password">Change Password</button>
        </form>
    </div>
</div>
</body>
</html>