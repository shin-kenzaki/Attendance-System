<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not authenticated'
    ]);
    exit();
}

// Include database connection
require 'db.php';

// Check if required fields are provided
if (!isset($_POST['subject_id']) || !isset($_POST['student_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit();
}

// Get parameters
$subject_id = intval($_POST['subject_id']);
$student_id = intval($_POST['student_id']);
$schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
$timestamp = isset($_POST['timestamp']) ? $_POST['timestamp'] : date('Y-m-d H:i:s');

// Check if student is enrolled in the subject
$enrollment_query = "SELECT * FROM usersubjects WHERE user_id = ? AND subject_id = ?";
$enrollment_stmt = $conn->prepare($enrollment_query);
$enrollment_stmt->bind_param("ii", $student_id, $subject_id);
$enrollment_stmt->execute();
$enrollment_result = $enrollment_stmt->get_result();

if ($enrollment_result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Student is not enrolled in this subject'
    ]);
    exit();
}

// Check if attendance already recorded today
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

$check_query = "SELECT * FROM attendances WHERE user_id = ? AND subject_id = ? AND time_in BETWEEN ? AND ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("iiss", $student_id, $subject_id, $today_start, $today_end);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Attendance already recorded today for this subject'
    ]);
    exit();
}

// Record attendance
$insert_query = $schedule_id 
    ? "INSERT INTO attendances (user_id, subject_id, schedule_id, time_in) VALUES (?, ?, ?, ?)" 
    : "INSERT INTO attendances (user_id, subject_id, time_in) VALUES (?, ?, ?)";

$insert_stmt = $conn->prepare($insert_query);

if ($schedule_id) {
    $insert_stmt->bind_param("iiis", $student_id, $subject_id, $schedule_id, $timestamp);
} else {
    $insert_stmt->bind_param("iis", $student_id, $subject_id, $timestamp);
}

if ($insert_stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Attendance recorded successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to record attendance: ' . $conn->error
    ]);
}

$conn->close();
?>