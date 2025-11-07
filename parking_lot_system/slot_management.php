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

$user = $_SESSION['username'];
$role = $_SESSION['role'];

// --- All Admin Logic is Mover Here ---

// ✅ Admin adds slot
if (isset($_POST['add_slot']) && $role == 'admin') {
    $conn->query("INSERT INTO parking_slots (status) VALUES ('Available')");
}

// ✅ Admin removes slot
if (isset($_POST['remove_slot']) && $role == 'admin') {
    $slot_id = $_POST['slot_id'];
    $stmt_del = $conn->prepare("DELETE FROM parking_slots WHERE id = ?");
    $stmt_del->bind_param("i", $slot_id);
    $stmt_del->execute();
    $stmt_del->close();
}

// ✅ Admin frees any slot
if (isset($_POST['admin_free_slot']) && $role == 'admin') {
    $slot_id = $_POST['slot_id'];
    $stmt_admin_free = $conn->prepare("UPDATE parking_slots SET status='Available', booked_by=NULL, car_no=NULL WHERE id = ?");
    $stmt_admin_free->bind_param("i", $slot_id);
    $stmt_admin_free->execute();
    $stmt_admin_free->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Slot Management</title>
<style>
/* (Your existing styles) */
body {font-family: Arial, sans-serif; background: #f0f2f5; margin:0;}
nav {background: #2a5298; padding: 15px; text-align: center;}
nav a {color: white; margin: 0 20px; text-decoration: none; font-weight: bold; font-size: 18px;}
nav a:hover {color: #ffd700;}
.container {max-width: 1100px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); text-align: center;}
h1 {color: #2a5298;}
.slots {display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-top: 20px;}
.slot {padding: 20px; border-radius: 10px; color: white; font-weight: bold; position: relative; font-size: 0.9em;}
.slot.available {background: green;}
.slot.booked {background: red;}
.slot button {margin-top: 10px; padding: 5px 10px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;}
.slot button.admin-btn {background: white; color: orange;}
.slot button.remove {background: white; color: red;}
.add {background: green; color: white; margin-bottom: 20px; padding: 10px 15px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;}
/* --- NEW: Style for DB ID --- */
.slot-id-display {
    font-size: 0.8em;
    font-weight: normal;
    color: #eeeeee;
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
<h1>Welcome, <?php echo htmlspecialchars($user); ?> (Admin)</h1>
<h4>Role: <?php echo ucfirst($role); ?></h4>

<form method="POST">
    <button type="submit" name="add_slot" class="add">➕ Add Slot</button>
</form>

<div class="slots">
<?php
// --- START OF CHANGE ---
$result = $conn->query("SELECT * FROM parking_slots ORDER BY id ASC");
if ($result && $result->num_rows > 0) {
    $slot_display_number = 1; // 1. Initialize counter
    while($row = $result->fetch_assoc()) {
// --- END OF CHANGE ---
        
        if ($row['status'] == 'Available') {
            $statusClass = 'available';
        } else {
            $statusClass = 'booked';
        }

        // Admin buttons - they use the REAL row['id']
        $buttonHTML = "<form method='POST' style='display:inline;'>
                            <input type='hidden' name='slot_id' value='".$row['id']."'>
                            <button type='submit' name='remove_slot' class='remove'>❌ Remove</button>
                       </form>
                       <form method='POST' style='display:inline;'>
                            <input type='hidden' name='slot_id' value='".$row['id']."'>
                            <button type='submit' name='admin_free_slot' class='admin-btn'>🔓 Free</button>
                       </form>";

        // --- START OF CHANGE ---
        echo "<div class='slot $statusClass'>
                <div>Slot #".$slot_display_number." <span class='slot-id-display'>(DB ID: ".$row['id'].")</span></div>"; // 2. Display counter and real ID
        // --- END OF CHANGE ---
        
        echo "<div>Status: ".htmlspecialchars($row['status'])."</div>
              <div>Booked by: ".htmlspecialchars($row['booked_by'] ?? '-')."</div>
              <div>Car: ".htmlspecialchars($row['car_no'] ?? '-')."</div>
              $buttonHTML
            </div>";
            
        // --- START OF CHANGE ---
        $slot_display_number++; // 3. Increment counter
        // --- END OF CHANGE ---
    }
} else {
    echo "<p>No slots found</p>";
}
$conn->close();
?>
</div>
</div>
</body>
</html>