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

// Check if subject_id is provided
if (!isset($_GET['subject_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Subject ID is required'
    ]);
    exit();
}

$subject_id = intval($_GET['subject_id']);

// Get only today's attendance records for this subject
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

$query = "SELECT a.*, u.firstname, u.middle_init, u.lastname, u.student_id AS student_number 
          FROM attendances a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.subject_id = ? AND a.time_in BETWEEN ? AND ?
          ORDER BY a.time_in DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $subject_id, $today_start, $today_end);
$stmt->execute();
$result = $stmt->get_result();

$attendances = [];
while ($row = $result->fetch_assoc()) {
    $name = $row['firstname'] . ' ' . ($row['middle_init'] ? $row['middle_init'] . ' ' : '') . $row['lastname'];
    $attendances[] = [
        'user_id' => $row['user_id'],
        'name' => $name,
        'student_id' => $row['student_number'],
        'timestamp' => $row['time_in'],
        'schedule_id' => $row['schedule_id']
    ];
}

echo json_encode([
    'status' => 'success',
    'data' => $attendances
]);

$conn->close();
?>
