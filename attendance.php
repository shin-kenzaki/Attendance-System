<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User not logged in - redirect to login page
    header("Location: index.php");
    exit();
} 
// Allow access only to admin
else if ($_SESSION['usertype'] !== 'admin') {
    // User logged in but wrong role - show 404 page
    header("Location: 404.php");
    exit();
}

include 'includes/header.php';
require 'db.php';

// Set default filter values
$current_date = date('Y-m-d');
$filter_date = isset($_GET['date']) ? $_GET['date'] : $current_date;
$filter_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Build the SQL query for attendance with filters
$sql = "SELECT a.id, a.time_in, 
        CONCAT(u.lastname, ', ', u.firstname) AS student_name, 
        u.id AS student_id,
        s.code AS subject_code, 
        s.name AS subject_name, 
        s.id AS subject_id,
        sc.room, sc.day, sc.start_time, sc.end_time
        FROM attendances a
        JOIN users u ON a.user_id = u.id
        JOIN subjects s ON a.subject_id = s.id
        LEFT JOIN schedules sc ON s.id = sc.subject_id
        WHERE 1=1";

// Add filters if provided
if (!empty($filter_date)) {
    $sql .= " AND DATE(FROM_UNIXTIME(a.time_in)) = '$filter_date'";
}

if (!empty($filter_subject)) {
    $sql .= " AND a.subject_id = $filter_subject";
}

if ($filter_status == 'present') {
    $sql .= " AND a.time_in IS NOT NULL";
} elseif ($filter_status == 'absent') {
    // This is simplified - for actual absence tracking you would need a more complex query
    // comparing scheduled classes against attendance records
    $sql .= " AND a.time_in IS NULL";
}

$sql .= " ORDER BY a.time_in DESC";
$result = $conn->query($sql);

// Get all subjects for the filter dropdown
$subjects_sql = "SELECT id, code, name FROM subjects ORDER BY code";
$subjects_result = $conn->query($subjects_sql);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-2 text-gray-800">Attendance Management</h1>
        <div>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#recordAttendanceModal">
                <i class="fas fa-plus fa-sm text-white-50"></i> Record Attendance
            </a>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm" data-toggle="modal" data-target="#generateReportModal">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>
    </div>
    
    <p class="mb-4">View and manage attendance records for all subjects and students.</p>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="get" action="attendance.php" class="form-inline">
                <div class="form-group mb-2 mr-sm-2">
                    <label for="date" class="mr-2">Date:</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
                </div>
                <div class="form-group mb-2 mr-sm-2">
                    <label for="subject_id" class="mr-2">Subject:</label>
                    <select class="form-control" id="subject_id" name="subject_id">
                        <option value="">All Subjects</option>
                        <?php 
                        if ($subjects_result->num_rows > 0) {
                            while($subject = $subjects_result->fetch_assoc()) {
                                $selected = ($filter_subject == $subject['id']) ? 'selected' : '';
                                echo "<option value='" . $subject['id'] . "' $selected>" . $subject['code'] . " - " . $subject['name'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group mb-2 mr-sm-2">
                    <label for="status" class="mr-2">Status:</label>
                    <select class="form-control" id="status" name="status">
                        <option value="" <?php echo ($filter_status == '') ? 'selected' : ''; ?>>All</option>
                        <option value="present" <?php echo ($filter_status == 'present') ? 'selected' : ''; ?>>Present</option>
                        <option value="absent" <?php echo ($filter_status == 'absent') ? 'selected' : ''; ?>>Absent</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2">Apply Filters</button>
                <a href="attendance.php" class="btn btn-secondary mb-2 ml-2">Reset</a>
            </form>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
            <div>
                <div class="dropdown no-arrow d-inline">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                        <div class="dropdown-header">Export Options:</div>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-csv fa-sm fa-fw mr-2"></i>CSV</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-excel fa-sm fa-fw mr-2"></i>Excel</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-pdf fa-sm fa-fw mr-2"></i>PDF</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <div class="px-3">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                // Output data of each row
                                while($row = $result->fetch_assoc()) {
                                    $timestamp = date('Y-m-d H:i:s', $row['time_in']);
                                    $attendance_date = date('Y-m-d', $row['time_in']);
                                    $attendance_time = date('h:i A', $row['time_in']);
                                    
                                    // Determine attendance status
                                    $scheduled_time = strtotime($row['start_time']);
                                    $attendance_time_seconds = $row['time_in'];
                                    $status_class = "";
                                    $status_text = "Present";
                                    
                                    // If attendance time is within 15 minutes of scheduled time, it's on time
                                    // This is simplified logic - you may want more complex rules
                                    if (isset($scheduled_time) && ($attendance_time_seconds > ($scheduled_time + 15 * 60))) {
                                        $status_class = "text-warning";
                                        $status_text = "Late";
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row["id"]. "</td>";
                                    echo "<td>" . $attendance_date . "</td>";
                                    echo "<td>" . $attendance_time . "</td>";
                                    echo "<td>" . $row["student_name"]. "</td>";
                                    echo "<td>" . $row["subject_code"]. " - " . $row["subject_name"]. "</td>";
                                    echo "<td>" . ($row["room"] ?? "N/A") . "</td>";
                                    
                                    // Format schedule information
                                    $schedule_info = isset($row["day"]) ? 
                                        $row["day"] . " " . date('h:i A', strtotime($row["start_time"])) . " - " . 
                                        date('h:i A', strtotime($row["end_time"])) : "Not scheduled";
                                    
                                    echo "<td>" . $schedule_info . "</td>";
                                    echo "<td class='" . $status_class . "'>" . $status_text . "</td>";
                                    echo "<td class='text-center'>
                                            <div class='dropdown no-arrow'>
                                                <a class='dropdown-toggle btn btn-sm btn-secondary' href='#' role='button' id='dropdownMenuLink' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                                    Actions
                                                </a>
                                                <div class='dropdown-menu dropdown-menu-right shadow animated--fade-in' aria-labelledby='dropdownMenuLink'>
                                                    <a class='dropdown-item' href='view_attendance.php?id=" . $row["id"] . "'><i class='fas fa-eye fa-sm fa-fw mr-2 text-gray-400'></i>View</a>
                                                    <a class='dropdown-item' href='#' onclick='editAttendance(" . $row["id"] . ")'><i class='fas fa-edit fa-sm fa-fw mr-2 text-gray-400'></i>Edit</a>
                                                    <a class='dropdown-item' href='#' onclick='deleteAttendance(" . $row["id"] . ")'><i class='fas fa-trash fa-sm fa-fw mr-2 text-gray-400'></i>Delete</a>
                                                </div>
                                            </div>
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No attendance records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row">
        <!-- Total Attendance Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Attendance (Today)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $count_sql = "SELECT COUNT(*) as count FROM attendances WHERE DATE(FROM_UNIXTIME(time_in)) = CURDATE()";
                                $count_result = $conn->query($count_sql);
                                $count_row = $count_result->fetch_assoc();
                                echo $count_row['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- On-Time Percentage Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                On-Time Percentage (This Week)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">78%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Classes Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Classes Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $day_of_week = date('l'); // e.g., Monday, Tuesday, etc.
                                $classes_sql = "SELECT COUNT(*) as count FROM schedules WHERE day = '$day_of_week'";
                                $classes_result = $conn->query($classes_sql);
                                $classes_row = $classes_result->fetch_assoc();
                                echo $classes_row['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Absence Rate Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Absence Rate (This Month)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">11%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<?php include 'includes/footer.php'; ?>

<!-- Record Attendance Modal -->
<div class="modal fade" id="recordAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="recordAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="add_attendance.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="recordAttendanceModalLabel">Record Attendance</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="student_id">Student</label>
                        <select class="form-control" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $students_sql = "SELECT id, firstname, lastname FROM users WHERE usertype = 'student'";
                            $students_result = $conn->query($students_sql);
                            if ($students_result->num_rows > 0) {
                                while($student = $students_result->fetch_assoc()) {
                                    echo '<option value="' . $student["id"] . '">' . $student["lastname"] . ', ' . $student["firstname"] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject_id_modal">Subject</label>
                        <select class="form-control" id="subject_id_modal" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php 
                            // Reset the subjects result pointer
                            $subjects_result->data_seek(0);
                            if ($subjects_result->num_rows > 0) {
                                while($subject = $subjects_result->fetch_assoc()) {
                                    echo '<option value="' . $subject["id"] . '">' . $subject["code"] . ' - ' . $subject["name"] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attendance_date">Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="attendance_time">Time</label>
                        <input type="time" class="form-control" id="attendance_time" name="attendance_time" value="<?php echo date('H:i'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Record Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Report Modal -->
<div class="modal fade" id="generateReportModal" tabindex="-1" role="dialog" aria-labelledby="generateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="generate_report.php" target="_blank">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateReportModalLabel">Generate Attendance Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="report_type">Report Type</label>
                        <select class="form-control" id="report_type" name="report_type" required>
                            <option value="daily">Daily Report</option>
                            <option value="weekly">Weekly Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="subject">Subject-wise Report</option>
                            <option value="student">Student-wise Report</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div id="subject_filter" class="form-group">
                        <label for="report_subject">Subject (Optional)</label>
                        <select class="form-control" id="report_subject" name="report_subject">
                            <option value="">All Subjects</option>
                            <?php 
                            // Reset the subjects result pointer
                            $subjects_result->data_seek(0);
                            if ($subjects_result->num_rows > 0) {
                                while($subject = $subjects_result->fetch_assoc()) {
                                    echo '<option value="' . $subject["id"] . '">' . $subject["code"] . ' - ' . $subject["name"] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div id="student_filter" class="form-group">
                        <label for="report_student">Student (Optional)</label>
                        <select class="form-control" id="report_student" name="report_student">
                            <option value="">All Students</option>
                            <?php
                            // Reset the students result pointer
                            $students_result->data_seek(0);
                            if ($students_result->num_rows > 0) {
                                while($student = $students_result->fetch_assoc()) {
                                    echo '<option value="' . $student["id"] . '">' . $student["lastname"] . ', ' . $student["firstname"] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="report_format">Format</label>
                        <select class="form-control" id="report_format" name="report_format" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<!-- Sweet Alert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<!-- Page level custom scripts -->
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#dataTable').DataTable({
        order: [[1, 'desc'], [2, 'desc']], // Sort by date and time by default
        pageLength: 25,
        responsive: true,
        dom: '<"d-flex align-items-center justify-content-between"<"mr-3"l><"ml-auto"f>>rt<"bottom d-flex align-items-center justify-content-between"ip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search attendance records...",
            lengthMenu: "Entries per page: _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ records",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        }
    });
    
    // Show/hide specific fields based on report type
    $('#report_type').change(function() {
        if ($(this).val() === 'subject') {
            $('#subject_filter').show();
            $('#student_filter').show();
        } else if ($(this).val() === 'student') {
            $('#subject_filter').show();
            $('#student_filter').show();
        } else {
            $('#subject_filter').show();
            $('#student_filter').show();
        }
    });
    
    // Initialize with the proper fields shown/hidden
    $('#report_type').trigger('change');
});

// Function to delete attendance
function deleteAttendance(attendanceId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_attendance.php?id=' + attendanceId;
        }
    });
}

// Function to edit attendance
function editAttendance(attendanceId) {
    // You'll need to implement this to fetch attendance data and show in a modal
    // For now, we'll just alert
    Swal.fire({
        title: 'Edit Attendance',
        text: 'Edit attendance record ID: ' + attendanceId,
        icon: 'info',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    });
}
</script>

</body>
</html>
<?php
$conn->close();
?>
