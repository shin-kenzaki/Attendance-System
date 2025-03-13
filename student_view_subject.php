<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'student') {
    $_SESSION['error'] = "Please login as a student to access this page";
    header("Location: index.php");
    exit();
}

// Check if subject ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No subject specified";
    header("Location: student_subjects.php");
    exit();
}

$subject_id = $_GET['id'];
$student_id = $_SESSION['user_id'];

// Get subject details with schedule and attendance information
$query = "SELECT s.*, sch.day, sch.start_time, sch.end_time, sch.room,
          u.firstname, u.middle_init, u.lastname,
          (SELECT MIN(DATE(time_in)) FROM attendances WHERE subject_id = s.id) as first_attendance,
          (SELECT MAX(DATE(time_in)) FROM attendances WHERE subject_id = s.id) as last_attendance,
          (SELECT COUNT(*) FROM attendances WHERE subject_id = s.id AND user_id = ?) as user_attendance_count,
          (SELECT COUNT(DISTINCT DATE(time_in)) FROM attendances WHERE subject_id = s.id) as total_class_days
          FROM subjects s
          LEFT JOIN schedules sch ON s.id = sch.subject_id
          LEFT JOIN users u ON s.faculty_id = u.id
          WHERE s.id = ? AND s.status = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $student_id, $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Subject not found";
    header("Location: student_subjects.php");
    exit();
}

$subject = $result->fetch_assoc();

// Get student's attendance records for this subject
$attendance_query = "SELECT a.*, 
                    DATE_FORMAT(FROM_UNIXTIME(a.time_in), '%M %d, %Y %h:%i %p') as formatted_time
                    FROM attendances a
                    WHERE a.user_id = ? AND a.subject_id = ?
                    ORDER BY a.time_in DESC";

$stmt_attendance = $conn->prepare($attendance_query);
$stmt_attendance->bind_param("ii", $student_id, $subject_id);
$stmt_attendance->execute();
$attendance_result = $stmt_attendance->get_result();

// Calculate attendance statistics
$total_classes = $attendance_result->num_rows;
$attendance_percentage = ($subject['total_class_days'] > 0) ? number_format(($subject['user_attendance_count'] / $subject['total_class_days']) * 100, 1) : 0;

// Determine session status
$sessionStatus = "Not started";
$sessionStatusColor = "secondary";

if ($subject['first_attendance']) {
    $today = new DateTime();
    $startDate = new DateTime($subject['first_attendance']);
    $endDate = $subject['last_attendance'] ? new DateTime($subject['last_attendance']) : $today;
    
    // Calculate session progress
    $totalDays = $startDate->diff($endDate)->days + 1;
    $elapsedDays = $startDate->diff($today)->days + 1;
    $sessionProgress = min(100, max(0, ($elapsedDays / $totalDays) * 100));
    
    if ($today < $startDate) {
        $sessionStatus = "Starting soon";
        $sessionStatusColor = "info";
    } elseif ($today > $endDate) {
        $sessionStatus = "Completed";
        $sessionStatusColor = "secondary";
    } else {
        $sessionStatus = "Ongoing";
        $sessionStatusColor = "success";
    }
}

// Determine next class
$nextClass = null;
if ($subject['day']) {
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $today = date('w'); // 0 (Sunday) through 6 (Saturday)
    $scheduleDay = array_search($subject['day'], $days);
    
    if ($scheduleDay !== false) {
        $daysUntil = ($scheduleDay - $today + 7) % 7;
        $daysUntil = $daysUntil === 0 && date('H:i:s') > $subject['start_time'] ? 7 : $daysUntil;
        $nextClass = $daysUntil === 0 ? 'Today' : ($daysUntil === 1 ? 'Tomorrow' : $days[$scheduleDay]);
    }
}

include 'includes/header.php';
?>

<!-- Add this before closing </head> tag -->
<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.progress-slim {
    height: 5px;
}
.session-dates {
    border-top: 1px solid rgba(0,0,0,0.1);
    padding-top: 10px;
    margin-top: 10px;
}
.attendance-badge {
    font-size: 85%;
}
</style>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Subject Details</h1>
        <a href="student_subjects.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Subjects
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Subject Details Card -->
    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-info">
                        <?php echo htmlspecialchars($subject['code']); ?>
                        <span class="badge badge-<?php echo $sessionStatusColor; ?> ml-2"><?php echo $sessionStatus; ?></span>
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="card-title font-weight-bold"><?php echo htmlspecialchars($subject['name']); ?></h5>
                    
                    <!-- Attendance Statistics -->
                    <div class="mb-3 mt-2">
                        <h6 class="font-weight-bold text-sm">Your Attendance:</h6>
                        <?php
                            $attendanceColor = "success";
                            if ($attendance_percentage < 75) {
                                $attendanceColor = "danger";
                            } else if ($attendance_percentage < 90) {
                                $attendanceColor = "warning";
                            }
                        ?>
                        <div class="d-flex align-items-center">
                            <div class="progress progress-slim flex-grow-1 mr-2">
                                <div class="progress-bar bg-<?php echo $attendanceColor; ?>" role="progressbar" 
                                     style="width: <?php echo round($attendance_percentage); ?>%" 
                                     aria-valuenow="<?php echo round($attendance_percentage); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="attendance-badge badge badge-<?php echo $attendanceColor; ?>">
                                <?php echo round($attendance_percentage); ?>%
                            </span>
                        </div>
                        <div class="small text-muted mt-1">
                            <?php echo $subject['user_attendance_count'] . ' of ' . $subject['total_class_days'] . ' class days'; ?>
                        </div>
                    </div>
                    
                    <?php if ($subject['day']): ?>
                    <div class="mt-3">
                        <h6 class="font-weight-bold">Schedule:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-2 px-0 border-top-0 border-bottom">
                                <i class="fas fa-calendar-day mr-2 text-gray-500"></i>
                                <?php echo htmlspecialchars($subject['day']); ?>
                                <?php if ($nextClass): ?>
                                <span class="badge badge-info ml-2">Next: <?php echo $nextClass; ?></span>
                                <?php endif; ?>
                                <br>
                                <i class="fas fa-clock ml-2 mr-1 text-gray-500"></i>
                                <?php echo date("h:i A", strtotime($subject['start_time'])) . ' - ' . 
                                         date("h:i A", strtotime($subject['end_time'])); ?>
                                <br>
                                <i class="fas fa-door-open ml-2 mr-1 text-gray-500"></i>
                                Room <?php echo htmlspecialchars($subject['room']); ?>
                            </li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i> No schedule set
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($subject['first_attendance']): ?>
                    <div class="session-dates">
                        <h6 class="font-weight-bold">Session Period:</h6>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="small text-muted">
                                <?php echo date('M d, Y', strtotime($subject['first_attendance'])); ?> - 
                                <?php echo $subject['last_attendance'] ? date('M d, Y', strtotime($subject['last_attendance'])) : 'Ongoing'; ?>
                            </div>
                            <?php if ($sessionStatus === "Ongoing"): ?>
                            <div class="text-xs text-muted">
                                <?php echo round($sessionProgress); ?>% complete
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($sessionStatus === "Ongoing"): ?>
                        <div class="progress progress-slim">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?php echo $sessionProgress; ?>%" 
                                 aria-valuenow="<?php echo $sessionProgress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="row">
                        <div class="col-12">
                            <a href="add_attendance.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-success btn-block">
                                <i class="fas fa-qrcode mr-1"></i> Mark Attendance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <!-- Attendance History Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($attendance = $attendance_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($attendance['time_in'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($attendance['time_in'])); ?></td>
                                        <td>
                                            <span class="badge badge-success">Present</span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#attendanceTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "language": {
            "emptyTable": "No attendance records found"
        }
    });
});
</script>
