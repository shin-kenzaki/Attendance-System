<?php
session_start();
require '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subject_id'])) {
    $subject_id = $_POST['subject_id'];
    
    // Get the subject's data
    $query = "SELECT id, code, name, faculty_id, status FROM subjects WHERE id = $subject_id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $subject_data = $result->fetch_assoc();
        echo json_encode($subject_data);
    } else {
        echo json_encode(['error' => 'Subject not found']);
    }
    
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
