<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Include database connection
require 'db.php';

// Get form data
$scheduleId = $_POST['schedule_id'] ?? '';
$subjectId = $_POST['subject_id'] ?? '';
$room = $_POST['room'] ?? '';
$day = $_POST['day'] ?? '';
$startTime = $_POST['start_time'] ?? '';
$endTime = $_POST['end_time'] ?? '';

// Validate required fields
if (empty($scheduleId) || empty($subjectId) || empty($room) || empty($day) || empty($startTime) || empty($endTime)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit();
}

// Validate that end time is after start time
if ($startTime >= $endTime) {
    echo json_encode(['status' => 'error', 'message' => 'End time must be after start time']);
    exit();
}

// Get faculty_id for the subject
$facultyQuery = "SELECT faculty_id FROM subjects WHERE id = ?";
$stmtFaculty = $conn->prepare($facultyQuery);
$stmtFaculty->bind_param("i", $subjectId);
$stmtFaculty->execute();
$facultyResult = $stmtFaculty->get_result();
$facultyId = null;

if ($facultyResult->num_rows > 0) {
    $facultyRow = $facultyResult->fetch_assoc();
    $facultyId = $facultyRow['faculty_id'];
}
$stmtFaculty->close();

// Check for room conflicts (excluding the current schedule)
$conflictSql = "SELECT s.*, sub.name as subject_name, sub.code as subject_code
                FROM schedules s 
                JOIN subjects sub ON s.subject_id = sub.id
                WHERE s.day = ? 
                AND s.room = ?
                AND s.id != ?
                AND ((s.start_time <= ? AND s.end_time > ?) 
                     OR (s.start_time < ? AND s.end_time >= ?) 
                     OR (s.start_time >= ? AND s.end_time <= ?))";

$stmt = $conn->prepare($conflictSql);
$stmt->bind_param("ssissssss", $day, $room, $scheduleId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
$stmt->execute();
$roomConflictResult = $stmt->get_result();
$stmt->close();

// Check for faculty conflicts if faculty is assigned (excluding the current schedule)
$facultyConflicts = [];
if ($facultyId) {
    $conflictFacultySql = "SELECT s.*, sub.name as subject_name, sub.code as subject_code
                    FROM schedules s 
                    JOIN subjects sub ON s.subject_id = sub.id
                    WHERE s.day = ? 
                    AND sub.faculty_id = ?
                    AND sub.id != ?
                    AND s.id != ?
                    AND ((s.start_time <= ? AND s.end_time > ?) 
                         OR (s.start_time < ? AND s.end_time >= ?) 
                         OR (s.start_time >= ? AND s.end_time <= ?))";

    $stmtFaculty = $conn->prepare($conflictFacultySql);
    $stmtFaculty->bind_param("siissssss", $day, $facultyId, $subjectId, $scheduleId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
    $stmtFaculty->execute();
    $facultyConflictResult = $stmtFaculty->get_result();
    
    while ($row = $facultyConflictResult->fetch_assoc()) {
        $facultyConflicts[] = [
            'subject_name' => $row['subject_name'],
            'subject_code' => $row['subject_code'],
            'day' => $row['day'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'room' => $row['room'],
            'conflict_type' => 'faculty'
        ];
    }
    $stmtFaculty->close();
}

// If conflicts exist, return them
$conflicts = [];
while ($row = $roomConflictResult->fetch_assoc()) {
    $conflicts[] = [
        'subject_name' => $row['subject_name'],
        'subject_code' => $row['subject_code'],
        'day' => $row['day'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'room' => $row['room'],
        'conflict_type' => 'room'
    ];
}

$allConflicts = array_merge($conflicts, $facultyConflicts);

if (count($allConflicts) > 0) {
    echo json_encode(['status' => 'conflict', 'conflicts' => $allConflicts]);
    exit();
}

// Update schedule
$updateSql = "UPDATE schedules SET room = ?, day = ?, start_time = ?, end_time = ? WHERE id = ?";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("ssssi", $room, $day, $startTime, $endTime, $scheduleId);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update schedule: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
