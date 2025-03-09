<?php
session_start();
require '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $subject_id = $_GET['id'];
    
    // Get subject info before deletion
    $get_subject = "SELECT code, name FROM subjects WHERE id = $subject_id";
    $subject_result = $conn->query($get_subject);
    
    if ($subject_result->num_rows > 0) {
        $subject_data = $subject_result->fetch_assoc();
        $subject_code = $subject_data['code'];
        $subject_name = $subject_data['name'];
        
        // Delete from usersubjects table first to maintain referential integrity
        $delete_usersubjects = "DELETE FROM usersubjects WHERE subject_id = $subject_id";
        $conn->query($delete_usersubjects);
        
        // Delete from schedules table
        $delete_schedules = "DELETE FROM schedules WHERE subject_id = $subject_id";
        $conn->query($delete_schedules);
        
        // Delete from attendances table
        $delete_attendances = "DELETE FROM attendances WHERE subject_id = $subject_id";
        $conn->query($delete_attendances);
        
        // Finally delete the subject
        $delete_subject = "DELETE FROM subjects WHERE id = $subject_id";
        
        if ($conn->query($delete_subject) === TRUE) {
            // Log the deletion
            $update_title = "Subject Deleted";
            $update_message = "Subject $subject_code: $subject_name has been deleted";
            $timestamp = date('Y-m-d H:i:s');
            
            $log_query = "INSERT INTO updates (user_id, title, message, timestamp) 
                         VALUES ('" . $_SESSION['user_id'] . "', '$update_title', '$update_message', '$timestamp')";
            $conn->query($log_query);
            
            $_SESSION['success'] = "Subject deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting subject: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Subject not found";
    }
    
    $conn->close();
    header("Location: subjects.php");
    exit();
} else {
    header("Location: subjects.php");
    exit();
}
?>
