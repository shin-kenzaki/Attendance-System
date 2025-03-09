<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['new_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
    // Get the user's email for the response
    $email_query = "SELECT email FROM users WHERE id = $user_id";
    $email_result = $conn->query($email_query);
    
    if ($email_result->num_rows > 0) {
        $user_data = $email_result->fetch_assoc();
        $email = $user_data['email'];
        
        // Update the user's password
        $update_query = "UPDATE users SET password = '$hashed_password', last_update = NOW() WHERE id = $user_id";
        
        if ($conn->query($update_query) === TRUE) {
            // Log the password change in updates table
            $update_title = "Password Reset";
            $update_message = "Password has been reset for user ID: $user_id";
            $timestamp = date('Y-m-d H:i:s');
            
            $log_query = "INSERT INTO updates (user_id, title, message, timestamp) 
                          VALUES ('$user_id', '$update_title', '$update_message', '$timestamp')";
            $conn->query($log_query);
            
            // Return success response with email
            echo json_encode(['status' => 'success', 'email' => $email]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
    
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
