<?php
session_start();
require '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subject_id']) && isset($_POST['new_joincode'])) {
    $subject_id = $_POST['subject_id'];
    $new_joincode = $_POST['new_joincode'];
    
    // Get the subject details
    $subject_query = "SELECT code, name FROM subjects WHERE id = $subject_id";
    $subject_result = $conn->query($subject_query);
    
    if ($subject_result->num_rows > 0) {
        $subject_data = $subject_result->fetch_assoc();
        $subject_code = $subject_data['code'];
        $subject_name = $subject_data['name'];
        
        // Update the join code
        $update_query = "UPDATE subjects SET joincode = '$new_joincode' WHERE id = $subject_id";
        
        if ($conn->query($update_query) === TRUE) {
            // Log the join code update
            $update_title = "Join Code Changed";
            $update_message = "Join code for subject $subject_code: $subject_name has been changed to $new_joincode";
            $timestamp = date('Y-m-d H:i:s');
            
            $log_query = "INSERT INTO updates (user_id, title, message, timestamp) 
                          VALUES ('" . $_SESSION['user_id'] . "', '$update_title', '$update_message', '$timestamp')";
            $conn->query($log_query);
            
            // Return success response
            echo json_encode([
                'status' => 'success', 
                'subject_code' => $subject_code,
                'subject_name' => $subject_name,
                'new_joincode' => $new_joincode
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update join code']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Subject not found']);
    }
    
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
