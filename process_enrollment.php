<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}
// Check if user has faculty access
if ($_SESSION['usertype'] !== 'faculty') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'db.php';

// Get parameters
$action = isset($_POST['action']) ? $_POST['action'] : '';
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

// Validate parameters
if (empty($action) || $student_id === 0 || $subject_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Verify the subject belongs to the faculty
$subjectQuery = "SELECT * FROM subjects WHERE id = ? AND faculty_id = ?";
$subjectStmt = $conn->prepare($subjectQuery);
$subjectStmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();

if ($subjectResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You are not authorized to manage this subject']);
    exit();
}

// Verify the student exists
$studentQuery = "SELECT * FROM users WHERE id = ? AND usertype = 'student'";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $student_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

$student = $studentResult->fetch_assoc();
$studentName = $student['firstname'] . ' ' . $student['lastname'];

// Process based on action
if ($action === 'add') {
    // Check if student is already enrolled
    $checkQuery = "SELECT * FROM usersubjects WHERE user_id = ? AND subject_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $student_id, $subject_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Student is already enrolled in this subject']);
        exit();
    }
    
    // Add student to the subject
    $insertQuery = "INSERT INTO usersubjects (user_id, subject_id) VALUES (?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ii", $student_id, $subject_id);
    
    if ($insertStmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => "Student {$studentName} has been added to the subject successfully"]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add student to subject']);
    }
    
} else if ($action === 'remove') {
    // Remove student from the subject
    $deleteQuery = "DELETE FROM usersubjects WHERE user_id = ? AND subject_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $student_id, $subject_id);
    
    if ($deleteStmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => "Student {$studentName} has been removed from the subject"]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to remove student from subject']);
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();