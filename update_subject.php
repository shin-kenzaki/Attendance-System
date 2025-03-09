<?php
session_start();
require '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $_POST['subject_id'];
    $code = $_POST['code'];
    $name = $_POST['name'];
    $faculty_id = !empty($_POST['faculty_id']) ? $_POST['faculty_id'] : NULL;
    $status = $_POST['status'];
    
    // Update subject information
    $sql = "UPDATE subjects SET 
            code = '$code', 
            name = '$name', 
            faculty_id = " . ($faculty_id ? "'$faculty_id'" : "NULL") . ", 
            status = '$status'
            WHERE id = $subject_id";
    
    if ($conn->query($sql) === TRUE) {
        // Log the update
        $update_title = "Subject Updated";
        $update_message = "Subject $code: $name (ID: $subject_id) information has been updated";
        $timestamp = date('Y-m-d H:i:s');
        
        $log_query = "INSERT INTO updates (user_id, title, message, timestamp) 
                     VALUES ('" . $_SESSION['user_id'] . "', '$update_title', '$update_message', '$timestamp')";
        $conn->query($log_query);
        
        $_SESSION['success'] = "Subject updated successfully";
    } else {
        $_SESSION['error'] = "Error updating subject: " . $conn->error;
    }
    
    $conn->close();
    header("Location: subjects.php");
    exit();
}
?>
