<?php
session_start();
// Security check: Only admins can access this page
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "parking_lot");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle Delete request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = $_GET['id'];
    
    // Prevent admin from deleting their own account from this page
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $message = "❌ Error: You cannot delete your own account from this list.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        if ($stmt->execute()) {
            $message = "✅ User deleted successfully.";
        } else {
            $message = "❌ Error deleting user.";
        }
        $stmt->close();
    }
}

// Fetch all users to display
$result = $conn->query("SELECT * FROM users ORDER BY id");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Users</title>
<style>
/* Using styles from your other pages */
body {font-family: Arial, sans-serif; background: #f0f2f5; margin:0;}
nav {background: #2a5298; padding: 15px; text-align: center;}
nav a {color: white; margin: 0 20px; text-decoration: none; font-weight: bold; font-size: 18px;}
nav a:hover {color: #ffd700;}
.container {max-width: 1200px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);}
h1 {color: #2a5298; text-align: center;}
.message {text-align: center; font-weight: bold; margin-bottom: 20px;}
.message.error {color: red;}
.message.success {color: green;}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}
th {
    background-color: #f2f2f2;
    color: #333;
}
tr:nth-child(even) {background-color: #f9f9f9;}
tr:hover {background-color: #f1f1f1;}
.action-links a {
    text-decoration: none;
    padding: 5px 10px;
    border-radius: 5px;
    color: white;
    margin-right: 5px;
}
.action-links .edit { background-color: #2a5298; }
.action-links .delete { background-color: #dc3545; }
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
    <h1>Manage Users</h1>

    <?php if ($message): ?>
        <p class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Car Number</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['car_no'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                    echo "<td class='action-links'>
                            <a href='edit_user.php?id=" . $row['id'] . "' class='edit'>Edit</a>
                            <a href='manage_users.php?action=delete&id=" . $row['id'] . "' class='delete' onclick=\"return confirm('Are you sure you want to delete this user?');\">Delete</a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No users found.</td></tr>";
            }
            $conn->close();
            ?>
        </tbody>
    </table>
</div>
</body>
</html>