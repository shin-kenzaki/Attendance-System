<?php
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

// Get schedule ID
$scheduleId = $_POST['schedule_id'] ?? '';

// Validate schedule ID
if (empty($scheduleId)) {
    echo json_encode(['status' => 'error', 'message' => 'Schedule ID is required']);
    exit();
}

// Fetch schedule data
$sql = "SELECT * FROM schedules WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $scheduleId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $schedule = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $schedule]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Schedule not found']);
}

$stmt->close();
$conn->close();
?>
