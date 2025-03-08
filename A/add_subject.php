<?php
session_start();
require '../db.php';

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
    $code = $_POST['code'];
    $name = $_POST['name'];
    $faculty_id = !empty($_POST['faculty_id']) ? $_POST['faculty_id'] : NULL;
    $joincode = generateRandomJoinCode(); // Automatically generate join code
    $status = 1; 
    
    // Insert new subject
    $sql = "INSERT INTO subjects (code, name, faculty_id, status, joincode) 
            VALUES ('$code', '$name', " . ($faculty_id ? "'$faculty_id'" : "NULL") . ", '$status', '$joincode')";
            
    if ($conn->query($sql) === TRUE) {
        $subject_id = $conn->insert_id;
        $timestamp = date('Y-m-d H:i:s');
        
        // Log the new subject in updates table
        $update_title = "New Subject Added";
        $update_message = "Subject $code: $name has been added with join code: $joincode";
        
        // Use admin user ID (1) for the update if no faculty is selected
        $update_user_id = $faculty_id ? $faculty_id : $_SESSION['user_id'];
        
        $update_sql = "INSERT INTO updates (user_id, title, message, timestamp) 
                       VALUES ('$update_user_id', '$update_title', '$update_message', '$timestamp')";
        $conn->query($update_sql);
        
        // Store information for SweetAlert
        $_SESSION['subject_success'] = "Subject added successfully";
        $_SESSION['subject_code'] = $code;
        $_SESSION['subject_name'] = $name;
        $_SESSION['subject_joincode'] = $joincode;
    } else {
        $_SESSION['subject_error'] = "Error: " . $sql . "<br>" . $conn->error;
    }
    
    $conn->close();
    header("Location: subjects.php");
    exit();
}
?>
