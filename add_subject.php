<?php
session_start();
require 'db.php';

function generateRandomJoinCode($length = 6) {
    $charset = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // Removed confusing characters like I, O, 0, 1
    $code = "";
    for ($i = 0; $i < $length; $i++) {
        $randomIndex = rand(0, strlen($charset) - 1);
        $code .= $charset[$randomIndex];
    }
    return $code;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codes = $_POST['code'];
    $names = $_POST['name'];
    $faculty_ids = $_POST['faculty_id'];
    
    $stmt = $conn->prepare("INSERT INTO subjects (code, name, faculty_id, status, joincode) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $code, $name, $faculty_id, $status, $joincode);
    
    foreach ($codes as $index => $code) {
        $name = $names[$index];
        $faculty_id = !empty($faculty_ids[$index]) ? $faculty_ids[$index] : NULL;
        $joincode = generateRandomJoinCode(); // Automatically generate join code
        $status = 1; 
        
        if ($stmt->execute()) {
            $subject_id = $conn->insert_id;
            $timestamp = date('Y-m-d H:i:s');
            
            // Log the new subject in updates table
            $update_title = "New Subject Added";
            $update_message = "Subject $code: $name has been added with join code: $joincode";
            
            // Use admin user ID (1) for the update if no faculty is selected
            $update_user_id = $faculty_id ? $faculty_id : $_SESSION['user_id'];
            
            $update_stmt = $conn->prepare("INSERT INTO updates (user_id, title, message, timestamp) VALUES (?, ?, ?, ?)");
            $update_stmt->bind_param("isss", $update_user_id, $update_title, $update_message, $timestamp);
            $update_stmt->execute();
            
            // Store information for SweetAlert
            $_SESSION['subject_success'][] = "Subject $code: $name added successfully with join code: $joincode";
        } else {
            $_SESSION['subject_error'][] = "Error adding subject $code: " . $stmt->error;
        }
    }
    
    $stmt->close();
    $conn->close();
    header("Location: subjects.php");
    exit();
}
?>
