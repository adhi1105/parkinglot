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

// ... (rest of your existing book.php code is correct) ...
$user = $_SESSION['username'];
$role = $_SESSION['role'];
$error = "";

if (isset($_POST['book_slot'])) {
    $car_no_from_form = $_POST['car_no'];
    $slot_id = $_POST['book_slot']; 

    $stmt_check = $conn->prepare("SELECT * FROM parking_slots WHERE booked_by = ?");
    $stmt_check->bind_param("s", $user);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        $error = "❌ You can only book 1 slot at a time! Go to the dashboard to free your slot.";
    } elseif (empty($car_no_from_form)) {
        $error = "❌ Please enter your car number to book a slot.";
    } else {
        $stmt_update = $conn->prepare("UPDATE parking_slots SET status='Booked', booked_by=?, car_no=? WHERE id=? AND status='Available'");
        $stmt_update->bind_param("ssi", $user, $car_no_from_form, $slot_id);

        if ($stmt_update->execute()) {
            $stmt_save_car = $conn->prepare("UPDATE users SET car_no = ? WHERE username = ?");
            $stmt_save_car->bind_param("ss", $car_no_from_form, $user);
            $stmt_save_car->execute();
            $stmt_save_car->close();
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Error booking slot. It might have just been taken.";
        }
        $stmt_update->close();
    }
    $stmt_check->close();
}

$car_no = "";
$stmt = $conn->prepare("SELECT car_no FROM users WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $car_no = $result->fetch_assoc()['car_no'];
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book a Slot - Parking Lot Management</title>
<style>
/* All your existing CSS is fine */
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
.slot button {margin-top: 10px; padding: 5px 10px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;}
.slot button.book {background: white; color: green;}
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
</style>
</head>
<body>
<nav>
    <a href="index.php">🏠 Home</a>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="book.php">🅿️ Book Slot</a>
    <a href="logout.php">🚪 Logout</a>
</nav>

<div class="container">
<h1>Available Parking Slots</h1>
<p>Enter your car number and select an available slot to book.</p>

<?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

<form method="POST">
    <div class="car-form">
        <label for="car_no"><strong>Your Car Number:</strong></label>
        <input type="text" name="car_no" value="<?php echo htmlspecialchars($car_no); ?>" placeholder="Enter Car No." required>
    </div>

    <div class="slots">
    <?php
    $result = $conn->query("SELECT * FROM parking_slots WHERE status='Available'");
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $buttonHTML = "<button type='submit' name='book_slot' value='".$row['id']."' class='book'>Book</button>";
            echo "<div class='slot available'>
                    <div>Slot #".htmlspecialchars($row['id'])."</div>
                    <div>Status: Available</div>
                    $buttonHTML
                  </div>";
        }
    } else {
        echo "<p>Sorry, no slots are currently available.</p>";
    }
    $conn->close();
    ?>
    </div>
</form>
</div>
</body>
</html>