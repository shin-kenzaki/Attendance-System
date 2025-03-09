<?php
session_start();
require '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $firstname = $_POST['firstname'];
    $middle_init = $_POST['middle_init'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $usertype = $_POST['usertype'];
    $department = $_POST['department'];
    $gender = $_POST['gender'];
    $status = $_POST['status'];
    $last_update = date('Y-m-d H:i:s');
    
    // Update user information
    $sql = "UPDATE users SET 
            firstname = '$firstname', 
            middle_init = '$middle_init', 
            lastname = '$lastname', 
            email = '$email', 
            usertype = '$usertype', 
            department = '$department',
            gender = '$gender',
            status = '$status',
            last_update = '$last_update'
            WHERE id = $user_id";
    
    if ($conn->query($sql) === TRUE) {
        // Log the update
        $update_title = "User Updated";
        $update_message = "User $firstname $lastname (ID: $user_id) information has been updated";
        
        $log_query = "INSERT INTO updates (user_id, title, message, timestamp) 
                     VALUES ('$user_id', '$update_title', '$update_message', '$last_update')";
        $conn->query($log_query);
        
        $_SESSION['success'] = "User updated successfully";
    } else {
        $_SESSION['error'] = "Error updating user: " . $conn->error;
    }
    
    $conn->close();
    header("Location: users.php");
    exit();
}
?>
