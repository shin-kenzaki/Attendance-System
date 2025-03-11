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

// Get subject details with schedule
$query = "SELECT s.*, sch.day, sch.start_time, sch.end_time, sch.room,
          u.firstname, u.middle_init, u.lastname
          FROM subjects s
          LEFT JOIN schedules sch ON s.id = sch.subject_id
          LEFT JOIN users u ON s.faculty_id = u.id
          WHERE s.id = ? AND s.status = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subject_id);
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
$attendance_percentage = $total_classes > 0 ? number_format(($total_classes / 18) * 100, 1) : 0;

include 'includes/header.php';
?>

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
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subject Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h4 class="text-info font-weight-bold"><?php echo htmlspecialchars($subject['code']); ?></h4>
                        <h5 class="text-dark"><?php echo htmlspecialchars($subject['name']); ?></h5>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Instructor:</h6>
                        <?php if ($subject['faculty_id']): ?>
                            <p class="mb-0">
                                <?php echo htmlspecialchars($subject['firstname'] . ' ' . 
                                ($subject['middle_init'] ? $subject['middle_init'] . '. ' : '') . 
                                $subject['lastname']); ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-0">No instructor assigned</p>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Schedule:</h6>
                        <?php if ($subject['day']): ?>
                            <p class="mb-0">
                                <i class="fas fa-calendar-day mr-2 text-gray-500"></i>
                                <?php echo htmlspecialchars($subject['day']); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock mr-2 text-gray-500"></i>
                                <?php echo date("h:i A", strtotime($subject['start_time'])) . ' - ' . 
                                         date("h:i A", strtotime($subject['end_time'])); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-door-open mr-2 text-gray-500"></i>
                                Room <?php echo htmlspecialchars($subject['room']); ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-0">No schedule set</p>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <a href="add_attendance.php?subject_id=<?php echo $subject_id; ?>" 
                           class="btn btn-success btn-block">
                            <i class="fas fa-qrcode mr-2"></i>Mark Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <!-- Attendance Statistics Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Attendance Rate
                                            </div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                        <?php echo $attendance_percentage; ?>%
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                            style="width: <?php echo $attendance_percentage; ?>%"
                                                            aria-valuenow="<?php echo $attendance_percentage; ?>" 
                                                            aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Classes Attended
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_classes; ?> / 18
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance History Table -->
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
                                        <td><?php echo date('M d, Y', $attendance['time_in']); ?></td>
                                        <td><?php echo date('h:i A', $attendance['time_in']); ?></td>
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
