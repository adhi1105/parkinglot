<?php
session_start();
// Security check: Only users can access this page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Redirect admins to their own page
if ($_SESSION['role'] == 'admin') {
    header("Location: slot_management.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "parking_lot");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['username'];
$role = $_SESSION['role'];
$error = "";

// --- Handle car number update ---
if (isset($_POST['update_car_no']) && $role == 'user') {
    $new_car_no = $_POST['car_no'];
    $stmt = $conn->prepare("UPDATE users SET car_no = ? WHERE username = ?");
    $stmt->bind_param("ss", $new_car_no, $user);
    $stmt->execute();
    $stmt->close();
}

// Fetch car number for logged-in user
$car_no = "";
$stmt = $conn->prepare("SELECT car_no FROM users WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $car_no = $result->fetch_assoc()['car_no'];
}
$stmt->close();


// ✅ User booking with 1-slot-per-user restriction
if (isset($_POST['book_slot']) && $role == 'user') {
    $stmt_check = $conn->prepare("SELECT * FROM parking_slots WHERE booked_by = ?");
    $stmt_check->bind_param("s", $user);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "❌ You can only book 1 slot at a time!";
    } else {
        $slot_id = $_POST['slot_id'];
        $stmt_update = $conn->prepare("UPDATE parking_slots SET status='Booked', booked_by=?, car_no=? WHERE id=? AND status='Available'");
        $stmt_update->bind_param("ssi", $user, $car_no, $slot_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt_check->close();
}

// ✅ User freeing their own slot
if (isset($_POST['free_slot']) && $role == 'user') {
    $slot_id = $_POST['slot_id'];
    $stmt_free = $conn->prepare("UPDATE parking_slots SET status='Available', booked_by=NULL, car_no=NULL WHERE id=? AND booked_by=?");
    $stmt_free->bind_param("is", $slot_id, $user);
    $stmt_free->execute();
    $stmt_free->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Parking Lot Management</title>
<style>
/* (Your existing styles) */
body {font-family: Arial, sans-serif; background: #f0f2f5; margin:0;}
nav {background: #2a5298; padding: 15px; text-align: center;}
nav a {color: white; margin: 0 20px; text-decoration: none; font-weight: bold; font-size: 18px;}
nav a:hover {color: #ffd700;}
.container {max-width: 1100px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); text-align: center;}
h1 {color: #2a5298;}
.error {margin-bottom: 15px; font-weight: bold; color: red;}
.slots {display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-top: 20px;}
.slot {padding: 20px; border-radius: 10px; color: white; font-weight: bold; position: relative;}
.slot.available {background: green;}
.slot.booked {background: red;}
.slot.own {background: blue;}
.slot button {margin-top: 10px; padding: 5px 10px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;}
.slot button.book {background: white; color: green;}
.slot button.free {background: white; color: red;}

.car-form {
    margin: 20px auto; 
    padding: 15px; 
    background: #f9f9f9; 
    border-radius: 8px; 
    border: 1px solid #ddd;
    max-width: 400px;
}
.car-form input[type="text"] {
    padding: 8px; 
    border-radius: 6px; 
    border: 1px solid #ccc;
    margin-left: 10px;
}
.car-form button {
    padding: 8px 12px; 
    border: none; 
    border-radius: 6px; 
    background: #2a5298; 
    color: white; 
    cursor: pointer;
    margin-left: 5px;
}
#save-status {
    color: green;
    font-weight: bold;
    font-size: 0.9em;
    display: inline-block;
    margin-left: 10px;
}
</style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="book.php">🅿️ Book Slot</a> 
    <a href="account.php">👤 Account</a>
    <a href="logout.php">🚪 Logout</a>
</nav>

<div class="container">
<h1>Welcome, <?php echo htmlspecialchars($user); ?> 👋</h1>

<div class="car-form" id="car-form-container">
    <form method="POST" id="car-form">
        <label for="car_no"><strong>Your Car Number:</strong></label>
        <input type="text" name="car_no" id="car_no_input" value="<?php echo htmlspecialchars($car_no); ?>" placeholder="Enter Car No.">
        <button type="submit" name="update_car_no">Save</button>
        <span id="save-status"></span>
    </form>
</div>

<h4>Role: <?php echo ucfirst($role); ?></h4>

<?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

<div class="slots">
<?php
// --- START OF CHANGE ---
$result = $conn->query("SELECT * FROM parking_slots ORDER BY id ASC");
if ($result && $result->num_rows > 0) {
    $slot_display_number = 1; // 1. Initialize counter
    while($row = $result->fetch_assoc()) {
// --- END OF CHANGE ---
        $statusClass = 'available';
        $buttonHTML = '';
        
        // Determine slot color and buttons
        if ($row['status'] == 'Available') {
            $statusClass = 'available';
            // Use the REAL row['id'] for the form
            $buttonHTML = "<form method='POST'><input type='hidden' name='slot_id' value='".$row['id']."'><button type='submit' name='book_slot' class='book'>Book</button></form>";
        
        } elseif ($row['booked_by'] == $user) {
            $statusClass = 'own';
            // Use the REAL row['id'] for the form
            $buttonHTML = "<form method='POST'><input type='hidden' name='slot_id' value='".$row['id']."'><button type='submit' name='free_slot' class='free'>Free</button></form>";
        
        } else {
            $statusClass = 'booked';
        }

        // --- START OF CHANGE ---
        echo "<div class='slot $statusClass'>
                <div>Slot #".$slot_display_number."</div>"; // 2. Display the counter
        // --- END OF CHANGE ---
        
        echo "<div>Status: ".htmlspecialchars($row['status'])."</div>
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

<script>
// (Your existing JavaScript for live car update)
const carForm = document.getElementById('car-form');
if (carForm) {
    carForm.addEventListener('submit', function(event) {
        event.preventDefault(); 
        const carInput = document.getElementById('car_no_input');
        const saveStatus = document.getElementById('save-status');
        const newCarValue = carInput.value;
        saveStatus.style.color = 'orange';
        saveStatus.textContent = 'Saving...';
        
        fetch('update_car_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ car_no: newCarValue })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                saveStatus.style.color = 'green';
                saveStatus.textContent = 'Saved!';
                setTimeout(() => {
                    saveStatus.textContent = '';
                }, 2000);
            } else {
                saveStatus.style.color = 'red';
                saveStatus.textContent = 'Error!';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            saveStatus.style.color = 'red';
            saveStatus.textContent = 'Error!';
        });
    });
}
</script>

</body>
</html>