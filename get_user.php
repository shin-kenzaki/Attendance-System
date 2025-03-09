<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Get the user's data
    $query = "SELECT id, firstname, middle_init, lastname, email, usertype, department, gender, status 
              FROM users WHERE id = $user_id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        echo json_encode($user_data);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
