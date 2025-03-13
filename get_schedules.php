<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
} 
// Allow access if user is admin or faculty
else if ($_SESSION['usertype'] !== 'admin' && $_SESSION['usertype'] !== 'faculty') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

require 'db.php';

if (!isset($_POST['subject_id']) || empty($_POST['subject_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Subject ID is required']);
    exit();
}

$subject_id = $conn->real_escape_string($_POST['subject_id']);

// Get schedules for the specified subject
$sql = "SELECT * FROM schedules WHERE subject_id = '$subject_id' ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
$result = $conn->query($sql);

if ($result) {
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    echo json_encode($schedules);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
