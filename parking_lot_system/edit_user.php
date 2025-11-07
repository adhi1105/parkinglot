<?php
session_start();
// Security check: Only admins can access this page
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin' || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "parking_lot");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id_to_edit = $_GET['id'];
$error = "";
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_car_no = $_POST['car_no'] ?? null;
    $new_role = $_POST['role'];

    // Check for duplicate username or email (but not for this user)
    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $new_username, $new_email, $user_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $error = "❌ Username or email already taken by another user.";
    } else {
        // Update user
        $stmt_update = $conn->prepare("UPDATE users SET username = ?, email = ?, car_no = ?, role = ? WHERE id = ?");
        $stmt_update->bind_param("ssssi", $new_username, $new_email, $new_car_no, $new_role, $user_id_to_edit);
        if ($stmt_update->execute()) {
            $success = "✅ User details updated successfully!";
            // Special check: if admin just edited their OWN details, update session
            if ($user_id_to_edit == $_SESSION['user_id']) {
                $_SESSION['username'] = $new_username;
                $_SESSION['role'] = $new_role;
            }
        } else {
            $error = "❌ Error updating details.";
        }
        $stmt_update->close();
    }
}

// Fetch current user data to show in form
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    die("User not found.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>
<style>
/* Using styles from your account.php */
body {font-family: Arial, sans-serif; background: #f0f2f5; margin:0;}
nav {background: #2a5298; padding: 15px; text-align: center;}
nav a {color: white; margin: 0 20px; text-decoration: none; font-weight: bold; font-size: 18px;}
nav a:hover {color: #ffd700;}
.container {max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);}
h1 {color: #2a5298; text-align: center;}
input[type="text"], input[type="email"], select {
    width: 95%; 
    padding: 12px; 
    margin: 10px 0;
    border: 1px solid #ccc; 
    border-radius: 8px;
    font-size: 16px;
    background-color: white; /* Ensure select is white */
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
.error {color: red; font-weight: bold; text-align: center; margin-bottom: 15px;}
.success {color: green; font-weight: bold; text-align: center; margin-bottom: 15px;}
.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #2a5298;
    text-decoration: none;
    font-weight: bold;
}
</style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <a href="slot_management.php">📊 Slot Management</a>
    <a href="manage_users.php">👤 Manage Users</a>
    <a href="account.php">⚙️ Account</a>
    <a href="logout.php">🚪 Logout</a>
</nav>

<div class="container">
    <h1>Edit User: <?php echo htmlspecialchars($user_data['username']); ?></h1>

    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>

    <form method="POST">
        <label for="username">Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
        
        <label for="email">Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        
        <label for="car_no">Car Number (if user)</label>
        <input type="text" name="car_no" value="<?php echo htmlspecialchars($user_data['car_no'] ?? ''); ?>">
        
        <label for="role">Role</label>
        <select name="role" id="role">
            <option value="user" <?php if($user_data['role'] == 'user') echo 'selected'; ?>>User</option>
            <option value="admin" <?php if($user_data['role'] == 'admin') echo 'selected'; ?>>Admin</option>
        </select>
        
        <button type="submit" name="update_details">Save Changes</button>
    </form>
    
    <a href="manage_users.php" class="back-link">⬅ Back to User List</a>
</div>
</body>
</html>