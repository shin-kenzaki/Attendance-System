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

// Check if required parameters are provided
if (!isset($_GET['subject_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameter: subject_id'
    ]);
    exit();
}

$subject_id = intval($_GET['subject_id']);
$today_only = isset($_GET['today_only']) && $_GET['today_only'] === 'true';
$detailed = isset($_GET['detailed']) && $_GET['detailed'] === 'true';

// Set date range
if ($today_only) {
    $start_date = date('Y-m-d 00:00:00'); // Today's start
    $end_date = date('Y-m-d 23:59:59');   // Today's end
} else {
    // Default to last 30 days
    $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
    $end_date = date('Y-m-d 23:59:59');
}

// Build query based on request details
if ($detailed) {
    $query = "SELECT a.*, u.id as user_id, 
              CONCAT(u.firstname, ' ', COALESCE(u.middle_init, ''), ' ', u.lastname) as student_name,
              u.email as student_email, 
              TIME_FORMAT(a.time_in, '%h:%i %p') as formatted_time,
              DATE_FORMAT(a.time_in, '%Y-%m-%d %H:%i:%s') as raw_time,
              s.start_time as schedule_start,
              s.end_time as schedule_end,
              s.room as room,
              s.day as schedule_day
              FROM attendances a 
              JOIN users u ON a.user_id = u.id
              LEFT JOIN schedules s ON s.subject_id = a.subject_id 
                   AND s.day = DATE_FORMAT(a.time_in, '%W')
                   AND TIME(a.time_in) BETWEEN s.start_time AND s.end_time
              WHERE a.subject_id = ? AND a.time_in BETWEEN ? AND ?
              ORDER BY a.time_in DESC";
} else {
    $query = "SELECT a.*, u.id as user_id, 
              CONCAT(u.firstname, ' ', COALESCE(u.middle_init, ''), ' ', u.lastname) as student_name,
              TIME_FORMAT(a.time_in, '%h:%i %p') as formatted_time,
              a.time_in as raw_time
              FROM attendances a 
              JOIN users u ON a.user_id = u.id
              WHERE a.subject_id = ? AND a.time_in BETWEEN ? AND ?
              ORDER BY a.time_in DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $subject_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $record = [
        'user_id' => $row['user_id'],
        'student_id' => $row['user_id'],
        'name' => $row['student_name'],
        'timestamp' => $row['formatted_time'],
        'raw_timestamp' => $row['raw_time'],
        'date' => date('M d, Y', strtotime($row['time_in']))
    ];
    
    // Add detailed info if requested
    if ($detailed) {
        $record['email'] = $row['student_email'];
        $record['schedule_start'] = $row['schedule_start'];
        $record['schedule_end'] = $row['schedule_end'];
        $record['room'] = $row['room'];
        $record['schedule_day'] = $row['schedule_day'];
        
        // Calculate status (on time vs late)
        if (!empty($row['schedule_start']) && !empty($row['raw_time'])) {
            $schedule_start = new DateTime($row['schedule_start']);
            $time_in = new DateTime($row['raw_time']);
            
            // Add grace period (15 minutes)
            $schedule_start->add(new DateInterval('PT15M'));
            
            if ($time_in > $schedule_start) {
                $record['status'] = 'Late';
            } else {
                $record['status'] = 'On Time';
            }
        } else {
            $record['status'] = 'Present';
        }
    }
    
    $data[] = $record;
}

echo json_encode([
    'status' => 'success',
    'data' => $data,
    'count' => count($data)
]);

$conn->close();
?>
