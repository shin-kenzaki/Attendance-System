<?php
session_start();
require_once 'db.php';

// Verify user is logged in as faculty
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'faculty') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
$schedule_id = isset($_POST['schedule_id']) && $_POST['schedule_id'] !== "null" ? intval($_POST['schedule_id']) : null;
$timestamp = isset($_POST['timestamp']) ? $_POST['timestamp'] : date('Y-m-d H:i:s');

// Validate data
if ($user_id === 0 || $subject_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit();
}

// Check if this user is enrolled in this subject
$check_query = "SELECT * FROM usersubjects WHERE user_id = ? AND subject_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $user_id, $subject_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // Student not enrolled, but we'll still record for manual verification
    $enrollment_status = 'not_enrolled';
} else {
    $enrollment_status = 'enrolled';
}

// Check if attendance already recorded today
$today = date('Y-m-d');
$existing_query = "SELECT * FROM attendances WHERE user_id = ? AND subject_id = ? AND DATE(time_in) = ?";
$existing_stmt = $conn->prepare($existing_query);
$existing_stmt->bind_param("iis", $user_id, $subject_id, $today);
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();

if ($existing_result->num_rows > 0) {
    // Already recorded today
    header('Content-Type: application/json');
    echo json_encode(['status' => 'info', 'message' => 'Attendance already recorded for today']);
    exit();
}

// Format the timestamp properly for MySQL
date_default_timezone_set('Asia/Manila'); // Set timezone to Philippine time
$time_in = date('Y-m-d H:i:s', strtotime($timestamp));

// Record the attendance with schedule_id if available
if ($schedule_id) {
    $insert_query = "INSERT INTO attendances (user_id, subject_id, schedule_id, time_in) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iiis", $user_id, $subject_id, $schedule_id, $time_in);
} else {
    $insert_query = "INSERT INTO attendances (user_id, subject_id, time_in) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iis", $user_id, $subject_id, $time_in);
}

if ($insert_stmt->execute()) {
    // Log this action
    $faculty_id = $_SESSION['user_id'];
    $log_query = "INSERT INTO updates (user_id, title, message, timestamp) VALUES (?, 'Attendance Recorded', ?, NOW())";
    $log_message = "Attendance recorded for student ID: $user_id in subject ID: $subject_id";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("is", $faculty_id, $log_message);
    $log_stmt->execute();
    
    // Return success
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Attendance recorded successfully',
        'enrollment_status' => $enrollment_status
    ]);
} else {
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>