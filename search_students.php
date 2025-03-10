<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}
// Check if user has faculty access
if ($_SESSION['usertype'] !== 'faculty') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'db.php';

// Get search term and subject ID
$search = isset($_POST['search']) ? $_POST['search'] : '';
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

if (empty($search) || $subject_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Search for students that are not already enrolled in the subject
$query = "SELECT u.* FROM users u 
          WHERE u.usertype = 'student' 
          AND u.status = 'active'
          AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.id LIKE ?)
          AND u.id NOT IN (
              SELECT user_id FROM usersubjects WHERE subject_id = ?
          )
          ORDER BY u.lastname, u.firstname
          LIMIT 10";

$searchTerm = "%{$search}%";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $subject_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    // Remove sensitive data like password
    unset($row['password']);
    $students[] = $row;
}

header('Content-Type: application/json');
echo json_encode($students);
$conn->close();