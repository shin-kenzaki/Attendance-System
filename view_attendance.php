<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
// Check if user has faculty access
if ($_SESSION['usertype'] !== 'faculty' && $_SESSION['usertype'] !== 'admin') {
    header("Location: 404.php");
    exit();
}

// Include database connection
include 'db.php';

// Get subject ID from URL parameter
if (!isset($_GET['subject_id'])) {
    header("Location: faculty_subjects.php");
    exit();
}
$subject_id = $_GET['subject_id'];

// Get faculty ID from session
$faculty_id = $_SESSION['user_id'];

// Check if the subject belongs to this faculty or if user is admin
$query = "SELECT * FROM subjects WHERE id = ?";
if ($_SESSION['usertype'] === 'faculty') {
    $query .= " AND faculty_id = ?";
}

$stmt = $conn->prepare($query);
if ($_SESSION['usertype'] === 'faculty') {
    $stmt->bind_param("ii", $subject_id, $faculty_id);
} else {
    $stmt->bind_param("i", $subject_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Subject not found or doesn't belong to this faculty
    header("Location: 404.php");
    exit();
}

$subject = $result->fetch_assoc();

// Include common header
include 'includes/header.php';

// Get date filter if set
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Attendance Records: <?php echo htmlspecialchars($subject['code']); ?> - <?php echo htmlspecialchars($subject['name']); ?>
        </h1>
        <div>
            <a href="faculty_subjects.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Subjects
            </a>
            <a href="#" class="btn btn-primary btn-sm" onclick="printAttendance()">
                <i class="fas fa-print fa-sm text-white-50"></i> Print Records
            </a>
            <a href="#" class="btn btn-success btn-sm" onclick="exportToCSV()">
                <i class="fas fa-file-csv fa-sm text-white-50"></i> Export to CSV
            </a>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Attendance</h6>
                </div>
                <div class="card-body">
                    <form method="get" action="view_attendance.php" id="filterForm">
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">Select Date:</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Apply Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Attendance Summary</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Get total enrolled students
                    $sql = "SELECT COUNT(*) as total FROM usersubjects WHERE subject_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $total_students = $stmt->get_result()->fetch_assoc()['total'];

                    // Get attendance for the selected date
                    $sql = "SELECT COUNT(DISTINCT user_id) as present FROM attendances 
                            WHERE subject_id = ? AND FROM_UNIXTIME(time_in, '%Y-%m-%d') = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $subject_id, $date_filter);
                    $stmt->execute();
                    $present_students = $stmt->get_result()->fetch_assoc()['present'];

                    $absent_students = $total_students - $present_students;
                    $attendance_rate = $total_students > 0 ? ($present_students / $total_students * 100) : 0;
                    ?>
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h4 class="text-primary"><?php echo $total_students; ?></h4>
                            <p class="small text-gray-600">Total Students</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-success"><?php echo $present_students; ?></h4>
                            <p class="small text-gray-600">Present</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-danger"><?php echo $absent_students; ?></h4>
                            <p class="small text-gray-600">Absent</p>
                        </div>
                    </div>
                    <div class="progress mt-3">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendance_rate; ?>%" 
                            aria-valuenow="<?php echo $attendance_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo round($attendance_rate, 1); ?>%
                        </div>
                    </div>
                    <p class="text-center small mt-2">Attendance Rate for <?php echo date('F j, Y', strtotime($date_filter)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Records for <?php echo date('F j, Y', strtotime($date_filter)); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID No.</th>
                            <th>Student Name</th>
                            <th>Time In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get all enrolled students first
                        $sql = "SELECT u.id, u.firstname, u.middle_init, u.lastname 
                                FROM users u 
                                JOIN usersubjects us ON u.id = us.user_id 
                                WHERE us.subject_id = ? 
                                ORDER BY u.lastname, u.firstname";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $subject_id);
                        $stmt->execute();
                        $students_result = $stmt->get_result();

                        if ($students_result->num_rows > 0) {
                            while ($student = $students_result->fetch_assoc()) {
                                // Check if this student has attendance for the selected date
                                $sql = "SELECT time_in FROM attendances 
                                        WHERE user_id = ? AND subject_id = ? AND FROM_UNIXTIME(time_in, '%Y-%m-%d') = ?
                                        ORDER BY time_in ASC LIMIT 1";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("iis", $student['id'], $subject_id, $date_filter);
                                $stmt->execute();
                                $attendance_result = $stmt->get_result();
                                $attendance = $attendance_result->fetch_assoc();
                                
                                $middle_initial = !empty($student['middle_init']) ? " " . $student['middle_init'] . "." : "";
                                $full_name = $student['firstname'] . $middle_initial . " " . $student['lastname'];
                        ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($full_name); ?></td>
                            <td>
                                <?php 
                                if ($attendance) {
                                    echo date('h:i:s A', $attendance['time_in']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($attendance): ?>
                                    <span class="badge badge-success">Present</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Absent</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="4" class="text-center">No students enrolled in this subject</td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<script>
// Print attendance records
function printAttendance() {
    window.print();
}

// Export attendance to CSV
function exportToCSV() {
    let table = document.getElementById("dataTable");
    let rows = table.querySelectorAll("tr");
    
    let csv = [];
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            // Get text content and remove badges
            let cellText = cols[j].innerText.replace(/\s+/g, ' ').trim();
            // Quote fields with commas
            row.push('"' + cellText + '"');
        }
        
        csv.push(row.join(","));        
    }
    
    // Download CSV file
    let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "attendance_<?php echo $subject['code']; ?>_<?php echo $date_filter; ?>.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Initialize DataTables
$(document).ready(function() {
    $('#dataTable').DataTable({
        order: [[3, 'asc'], [1, 'asc']], // Sort by status first (present/absent), then by name
        language: {
            search: "Search student:"
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>