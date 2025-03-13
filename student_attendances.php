<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'student') {
    $_SESSION['error'] = "Please login as a student to access this page";
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get all subjects the student is enrolled in
$subject_query = "SELECT s.id, s.code, s.name 
                 FROM subjects s
                 INNER JOIN usersubjects us ON s.id = us.subject_id
                 WHERE us.user_id = ?";
$stmt_subjects = $conn->prepare($subject_query);
$stmt_subjects->bind_param("i", $student_id);
$stmt_subjects->execute();
$subjects_result = $stmt_subjects->get_result();

// Initialize filter variables
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get the first and last attendance dates for the selected subject
$attendance_dates_query = "SELECT MIN(DATE(time_in)) AS start_date, MAX(DATE(time_in)) AS end_date 
                           FROM attendances 
                           WHERE subject_id = ? AND user_id = ?";
$attendance_dates_stmt = $conn->prepare($attendance_dates_query);
$attendance_dates_stmt->bind_param("ii", $subject_filter, $student_id);
$attendance_dates_stmt->execute();
$attendance_dates_result = $attendance_dates_stmt->get_result();
$attendance_dates = $attendance_dates_result->fetch_assoc();
$attendance_dates_stmt->close();

$start_date = $attendance_dates['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $attendance_dates['end_date'] ?? date('Y-m-d');

// Build attendance query based on filters
$attendance_query = "SELECT a.id, a.time_in, s.code, s.name, 
                    DATE(a.time_in) as attendance_date,
                    TIME(a.time_in) as attendance_time
                    FROM attendances a
                    INNER JOIN subjects s ON a.subject_id = s.id
                    WHERE a.user_id = ?";

$params = [$student_id];
$types = "i";

// Add subject filter if selected
if ($subject_filter !== 'all') {
    $attendance_query .= " AND a.subject_id = ?";
    $params[] = $subject_filter;
    $types .= "i";
}

// Add date filters if provided
if (!empty($date_from)) {
    $attendance_query .= " AND DATE(a.time_in) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $attendance_query .= " AND DATE(a.time_in) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

// Order by most recent first
$attendance_query .= " ORDER BY a.time_in DESC";

$stmt_attendance = $conn->prepare($attendance_query);

if (!empty($params)) {
    $stmt_attendance->bind_param($types, ...$params);
}

$stmt_attendance->execute();
$attendance_result = $stmt_attendance->get_result();

// Get attendance statistics
$stats_query = "SELECT s.id, s.code, s.name, COUNT(a.id) as attended_classes,
               (SELECT COUNT(DISTINCT DATE(time_in)) FROM attendances WHERE subject_id = s.id) as total_class_days
               FROM subjects s
               LEFT JOIN attendances a ON s.id = a.subject_id AND a.user_id = ?
               INNER JOIN usersubjects us ON s.id = us.subject_id AND us.user_id = ?
               GROUP BY s.id, s.code, s.name";

$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("ii", $student_id, $student_id);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();

// Create an array to hold attendance stats by subject
$attendance_stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $attendance_stats[$row['id']] = $row;
}

// Include header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Attendance Records</h1>
        <div>
            <button class="btn btn-info" data-toggle="modal" data-target="#filterModal">
                <i class="fas fa-filter fa-sm text-white-50"></i> Filter Attendance
            </button>
            <a href="student_attendances.php" class="btn btn-secondary ml-2">
                <i class="fas fa-sync fa-sm text-white-50"></i> Reset Filters
            </a>
        </div>
    </div>

    <!-- Attendance Statistics Cards -->
    <div class="row">
        <?php 
        foreach ($attendance_stats as $stat): 
            // Calculate attendance rate (would need total scheduled classes for real percentage)
            $attendance_rate = $stat['attended_classes']; // For now, just showing count
        ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                <?php echo htmlspecialchars($stat['code']); ?>
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $stat['total_class_days']; ?> classes</div>
                                </div>
                            </div>
                            <div class="small mt-2"><?php echo htmlspecialchars($stat['name']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Attendance Records Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-info">Attendance Records</h6>
            <?php if ($subject_filter !== 'all' || !empty($date_from) || !empty($date_to)): ?>
                <div class="small">
                    <span class="font-weight-bold">Active Filters:</span>
                    <?php 
                    if ($subject_filter !== 'all') {
                        foreach ($subjects_result as $subject) {
                            if ($subject['id'] == $subject_filter) {
                                echo '<span class="badge badge-info">' . htmlspecialchars($subject['code']) . '</span> ';
                                break;
                            }
                        }
                        // Reset the result pointer for later use
                        $subjects_result->data_seek(0);
                    }
                    
                    if (!empty($date_from)) {
                        echo '<span class="badge badge-secondary">From: ' . htmlspecialchars($date_from) . '</span> ';
                    }
                    
                    if (!empty($date_to)) {
                        echo '<span class="badge badge-secondary">To: ' . htmlspecialchars($date_to) . '</span>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($attendance_result->num_rows > 0):
                            while ($row = $attendance_result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['code']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo date('F d, Y', strtotime($row['attendance_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['attendance_time'])); ?></td>
                                <td><span class="badge badge-success">Present</span></td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="5" class="text-center">No attendance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Attendance Records</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="student_attendances.php" method="GET">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select class="form-control" id="subject" name="subject">
                            <option value="all" <?php echo $subject_filter === 'all' ? 'selected' : ''; ?>>All Subjects</option>
                            <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                                <option value="<?php echo $subject['id']; ?>" 
                                        <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['code'] . ' - ' . $subject['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#attendanceTable').DataTable({
        "order": [[2, "desc"], [3, "desc"]], // Sort by date and time descending
        "language": {
            "emptyTable": "No attendance records found"
        }
    });
    
    // Date range validation
    $('#date_to').change(function() {
        var dateFrom = $('#date_from').val();
        var dateTo = $(this).val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('End date must be after start date');
            $(this).val('');
        }
    });
    
    $('#date_from').change(function() {
        var dateFrom = $(this).val();
        var dateTo = $('#date_to').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('Start date must be before end date');
            $(this).val('');
        }
    });
});
</script>
