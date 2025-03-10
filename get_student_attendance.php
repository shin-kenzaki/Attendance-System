<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not authenticated";
    exit();
}
// Check if user has faculty access
if ($_SESSION['usertype'] !== 'faculty') {
    echo "Unauthorized access";
    exit();
}

// Include database connection
include 'db.php';

// Get parameters
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Validate parameters
if ($student_id === 0 || $subject_id === 0) {
    echo "Missing required parameters";
    exit();
}

// Verify the subject belongs to the faculty
$subjectQuery = "SELECT * FROM subjects WHERE id = ? AND faculty_id = ?";
$subjectStmt = $conn->prepare($subjectQuery);
$subjectStmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();

if ($subjectResult->num_rows === 0) {
    echo "You are not authorized to view this data";
    exit();
}

// Get student information
$studentQuery = "SELECT * FROM users WHERE id = ? AND usertype = 'student'";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $student_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows === 0) {
    echo "Student not found";
    exit();
}

$student = $studentResult->fetch_assoc();
$studentName = $student['firstname'] . ' ' . ($student['middle_init'] ? $student['middle_init'] . '. ' : '') . $student['lastname'];

// Get attendance records
$attendanceQuery = "SELECT a.*, s.day, s.room, s.start_time, s.end_time 
                  FROM attendances a 
                  LEFT JOIN schedules s ON a.schedule_id = s.id
                  WHERE a.user_id = ? AND a.subject_id = ?
                  ORDER BY a.time_in DESC";
$attendanceStmt = $conn->prepare($attendanceQuery);
$attendanceStmt->bind_param("ii", $student_id, $subject_id);
$attendanceStmt->execute();
$attendanceResult = $attendanceStmt->get_result();

// Output attendance history
?>
<div class="text-center mb-3">
    <h5><?php echo htmlspecialchars($studentName); ?></h5>
    <p class="text-muted"><?php echo htmlspecialchars($student['email']); ?> | <?php echo htmlspecialchars($student['department']); ?></p>
</div>

<?php if ($attendanceResult->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Schedule</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($record = $attendanceResult->fetch_assoc()): 
                $date = date('M d, Y', $record['time_in']);
                $time = date('h:i A', $record['time_in']);
                
                // Determine if the student was on time or late
                $status = "Present";
                $statusClass = "success";
                
                if ($record['start_time']) {
                    $scheduleStart = strtotime($date . ' ' . $record['start_time']);
                    $lateThreshold = $scheduleStart + (15 * 60); // 15 minutes grace period
                    
                    if ($record['time_in'] > $lateThreshold) {
                        $status = "Late";
                        $statusClass = "warning";
                    }
                }
                
                $schedule = $record['day'] ? "{$record['day']}, " . date('h:i A', strtotime($record['start_time'])) . 
                          " - " . date('h:i A', strtotime($record['end_time'])) . ", Room {$record['room']}" : "No schedule";
            ?>
                <tr>
                    <td><?php echo $date; ?></td>
                    <td><?php echo $time; ?></td>
                    <td><?php echo $schedule; ?></td>
                    <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-center mt-3">
        <strong>Total Attendance:</strong> <?php echo $attendanceResult->num_rows; ?> days
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle mr-2"></i> No attendance records found for this student in this subject.
    </div>
<?php endif; ?>

<?php $conn->close(); ?>