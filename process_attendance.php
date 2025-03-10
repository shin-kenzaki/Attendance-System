<?php
session_start();
require_once 'db.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
$schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$timestamp = isset($_POST['timestamp']) ? $_POST['timestamp'] : null;

// Validate required fields
if (!$subject_id || !$student_id || !$timestamp) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

// Check if student is enrolled in the subject
$enrollment_query = "SELECT id FROM enrollments WHERE subject_id = ? AND student_id = ?";
$stmt = $conn->prepare($enrollment_query);
$stmt->bind_param("ii", $subject_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'You are not enrolled in this subject']);
    exit();
}

// Check for duplicate attendance within the last hour
$check_query = "SELECT id FROM attendance 
                WHERE subject_id = ? AND student_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $subject_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Attendance already recorded recently']);
    exit();
}

// Insert attendance record
$insert_query = "INSERT INTO attendance (subject_id, schedule_id, student_id, created_at) 
                 VALUES (?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("iii", $subject_id, $schedule_id, $student_id);

if ($insert_stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Attendance recorded successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to record attendance']);
}

$conn->close();
?>