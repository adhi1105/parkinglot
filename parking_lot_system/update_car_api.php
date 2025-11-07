<?php
session_start();
// Default response
$response = ['status' => 'error', 'message' => 'Not logged in'];

// Check if user is logged in
if (isset($_SESSION['username'])) {
    // Get the data sent from JavaScript
    $data = json_decode(file_get_contents('php://input'), true);
    $new_car_no = $data['car_no'] ?? '';
    
    $conn = new mysqli("localhost", "root", "", "parking_lot");
    if ($conn->connect_error) {
        $response['message'] = 'Database connection failed';
    } else {
        $user = $_SESSION['username'];
        
        // SECURE: Use prepared statement
        $stmt = $conn->prepare("UPDATE users SET car_no = ? WHERE username = ?");
        $stmt->bind_param("ss", $new_car_no, $user);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Car number updated!';
        } else {
            $response['message'] = 'Database update failed';
        }
        $stmt->close();
        $conn->close();
    }
}

// Send a JSON response back to the JavaScript
header('Content-Type: application/json');
echo json_encode($response);
?>