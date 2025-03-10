<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

include 'db.php';

// Get parameters
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
$date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');

// Validate subject ID
if ($subject_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
    exit();
}

// Get attendance data for the subject and date
$query = "SELECT a.id, a.student_id, a.timestamp, CONCAT(u.firstname, ' ', u.middle_init, ' ', u.lastname) AS student_name
          FROM attendance a
          JOIN users u ON a.student_id = u.id
          WHERE a.subject_id = ? AND DATE(a.timestamp) = ?
          ORDER BY a.timestamp DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $subject_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$attendanceData = [];
while ($row = $result->fetch_assoc()) {
    $attendanceData[] = [
        'id' => $row['id'],
        'student_id' => $row['student_id'],
        'student_name' => $row['student_name'],
        'timestamp' => $row['timestamp']
    ];
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $attendanceData]);

$stmt->close();
$conn->close();
?>