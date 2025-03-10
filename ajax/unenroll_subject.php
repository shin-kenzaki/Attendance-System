<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'student') {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

if (isset($_POST['enrollment_id'])) {
    $enrollment_id = intval($_POST['enrollment_id']);
    
    // Verify the enrollment belongs to the current student
    $query = "DELETE FROM usersubjects WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $enrollment_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Log the unenrollment
        $log_query = "INSERT INTO updates (user_id, title, message, timestamp) 
                      VALUES (?, 'Subject Unenrollment', 'Unenrolled from subject', NOW())";
        $stmt_log = $conn->prepare($log_query);
        $stmt_log->bind_param("i", $_SESSION['user_id']);
        $stmt_log->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}