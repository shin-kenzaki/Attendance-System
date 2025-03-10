<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'faculty') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require 'db.php';

// Get POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
$schedule_id = isset($_POST['schedule_id']) && !empty($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;

// Validate required data
if ($user_id === 0 || $subject_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit();
}

// Check if the student is enrolled in this subject
$check_query = "SELECT * FROM usersubjects WHERE user_id = ? AND subject_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $user_id, $subject_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Student is not enrolled in this subject']);
    exit();
}

// Check if attendance for this student/subject/schedule already exists for today
$today = date('Y-m-d');
$check_attendance_query = "SELECT * FROM attendance WHERE user_id = ? AND subject_id = ? AND DATE(timestamp) = ?";
$check_attendance_stmt = $conn->prepare($check_attendance_query);
$check_attendance_stmt->bind_param("iis", $user_id, $subject_id, $today);
$check_attendance_stmt->execute();
$check_attendance_result = $check_attendance_stmt->get_result();

if ($check_attendance_result->num_rows > 0) {
    // Already has attendance record for today
    echo json_encode(['status' => 'success', 'message' => 'Attendance already recorded today', 'duplicate' => true]);
    exit();
}

// Record attendance
if ($schedule_id) {
    $insert_query = "INSERT INTO attendance (user_id, subject_id, schedule_id, timestamp, status) VALUES (?, ?, ?, NOW(), 'present')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iii", $user_id, $subject_id, $schedule_id);
} else {
    $insert_query = "INSERT INTO attendance (user_id, subject_id, timestamp, status) VALUES (?, ?, NOW(), 'present')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $user_id, $subject_id);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Attendance recorded successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to record attendance: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>