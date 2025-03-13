<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
// Check if user has faculty access
if ($_SESSION['usertype'] !== 'faculty') {
    header("Location: 404.php");
    exit();
}

// Include database connection
include 'db.php';

// Get faculty ID from session
$faculty_id = $_SESSION['user_id'];

// Set default filters
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Get the first and last attendance dates for the selected subject
$attendance_dates_query = "SELECT MIN(DATE(time_in)) AS start_date, MAX(DATE(time_in)) AS end_date 
                           FROM attendances 
                           WHERE subject_id = ? AND user_id IN 
                           (SELECT user_id FROM usersubjects WHERE subject_id = ?)";
$attendance_dates_stmt = $conn->prepare($attendance_dates_query);
$attendance_dates_stmt->bind_param("ii", $subject_id, $subject_id);
$attendance_dates_stmt->execute();
$attendance_dates_result = $attendance_dates_stmt->get_result();
$attendance_dates = $attendance_dates_result->fetch_assoc();
$attendance_dates_stmt->close();

$start_date = $attendance_dates['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $attendance_dates['end_date'] ?? date('Y-m-d');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Include header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?php
            if ($student_id > 0) {
                // Get student name
                $student_query = "SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?";
                $student_stmt = $conn->prepare($student_query);
                $student_stmt->bind_param("i", $student_id);
                $student_stmt->execute();
                $student_result = $student_stmt->get_result();
                if ($student = $student_result->fetch_assoc()) {
                    echo "Attendance Records - " . htmlspecialchars($student['fullname']);
                } else {
                    echo "Attendance Records";
                }
                $student_stmt->close();
            } else {
                echo "Attendance Records";
            }
            ?>
        </h1>
        <div>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm" id="exportBtn">
                <i class="fas fa-download fa-sm text-white-50"></i> Export Report
            </a>
            <a href="take_attendance.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ml-2">
                <i class="fas fa-clipboard-check fa-sm text-white-50"></i> Take Attendance
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Attendance Records</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row">
                <div class="col-md-3 mb-3">
                    <label for="subject_id">Subject:</label>
                    <select class="form-control" id="subject_id" name="subject_id">
                        <option value="0">All Subjects</option>
                        <?php
                        // Get all subjects taught by this faculty
                        $subjectQuery = "SELECT id, code, name FROM subjects WHERE faculty_id = ?";
                        $subjectStmt = $conn->prepare($subjectQuery);
                        $subjectStmt->bind_param("i", $faculty_id);
                        $subjectStmt->execute();
                        $subjectResult = $subjectStmt->get_result();
                        
                        while ($subjectRow = $subjectResult->fetch_assoc()) {
                            $selected = ($subject_id == $subjectRow['id']) ? 'selected' : '';
                            echo "<option value='" . $subjectRow['id'] . "' $selected>" . 
                                htmlspecialchars($subjectRow['code'] . ' - ' . $subjectRow['name']) . "</option>";
                        }
                        $subjectStmt->close();
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="start_date">Start Date:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="end_date">End Date:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="student_id">Student (Optional):</label>
                    <select class="form-control" id="student_id" name="student_id">
                        <option value="0">All Students</option>
                        <?php
                        // Get all unique students from faculty's subjects
                        $studentQuery = "SELECT DISTINCT u.id, u.firstname, u.lastname 
                                        FROM users u 
                                        JOIN usersubjects us ON u.id = us.user_id
                                        JOIN subjects s ON us.subject_id = s.id
                                        WHERE s.faculty_id = ? AND u.usertype = 'student'
                                        ORDER BY u.lastname, u.firstname";
                        $studentStmt = $conn->prepare($studentQuery);
                        $studentStmt->bind_param("i", $faculty_id);
                        $studentStmt->execute();
                        $studentResult = $studentStmt->get_result();
                        
                        while ($studentRow = $studentResult->fetch_assoc()) {
                            $selected = ($student_id == $studentRow['id']) ? 'selected' : '';
                            echo "<option value='" . $studentRow['id'] . "' $selected>" . 
                                htmlspecialchars($studentRow['lastname'] . ', ' . $studentRow['firstname']) . "</option>";
                        }
                        $studentStmt->close();
                        ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="faculty_attendances.php" class="btn btn-secondary ml-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Records Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Options:</div>
                    <a class="dropdown-item" href="#" id="printBtn"><i class="fas fa-print fa-sm fa-fw mr-2 text-gray-400"></i>Print</a>
                    <a class="dropdown-item" href="#" id="refreshBtn"><i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>Refresh</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Check-in Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build the SQL query based on the filters
                        $query = "SELECT a.id, a.user_id, a.subject_id, a.time_in, 
                                  u.id AS student_id, u.firstname, u.middle_init, u.lastname,
                                  s.code, s.name AS subject_name
                                  FROM attendances a
                                  JOIN users u ON a.user_id = u.id
                                  JOIN subjects s ON a.subject_id = s.id
                                  WHERE s.faculty_id = ? ";
                        
                        $params = array($faculty_id);
                        $types = "i";
                        
                        if ($subject_id > 0) {
                            $query .= "AND a.subject_id = ? ";
                            $params[] = $subject_id;
                            $types .= "i";
                        }
                        
                        if ($student_id > 0) {
                            $query .= "AND a.user_id = ? ";
                            $params[] = $student_id;
                            $types .= "i";
                        }
                        
                        $query .= "AND DATE(a.time_in) BETWEEN ? AND ? ";
                        $params[] = $start_date;
                        $params[] = $end_date;
                        $types .= "ss";
                        
                        $query .= "ORDER BY a.time_in DESC";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $fullName = htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . 
                                           ($row['middle_init'] ? ' ' . $row['middle_init'] . '.' : ''));
                                
                                // Determine attendance status based on check-in time compared to schedule
                                $status = "Present";
                                $statusClass = "success";
                                
                                if (!empty($row['start_time'])) {
                                    $scheduleTime = strtotime(date('Y-m-d', strtotime($row['time_in'])) . ' ' . $row['start_time']);
                                    $checkInTime = strtotime($row['time_in']);
                                    $minutesLate = ($checkInTime - $scheduleTime) / 60;
                                    
                                    if ($minutesLate > 15) {
                                        $status = "Late";
                                        $statusClass = "warning";
                                    }
                                }
                                
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['student_id']) . '</td>';
                                echo '<td>' . $fullName . '</td>';
                                echo '<td>' . htmlspecialchars($row['code']) . '</td>';
                                echo '<td>' . date('M d, Y', strtotime($row['time_in'])) . '</td>';
                                echo '<td>' . date('h:i A', strtotime($row['time_in'])) . '</td>';
                                echo '<td><span class="badge badge-' . $statusClass . '">' . $status . '</span></td>';
                                echo '<td>
                                        <button class="btn btn-sm btn-info" onclick="viewAttendanceDetails(' . $row['id'] . ')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteAttendance(' . $row['id'] . ')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                      </td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">No attendance records found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Attendance Summary Card -->
    <div class="row">
        <!-- Attendance Rate Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Overall Attendance Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                // Calculate attendance rate for this period
                                $attendanceQuery = "SELECT 
                                    COUNT(DISTINCT CONCAT(a.user_id, '_', a.subject_id, '_', DATE(a.time_in))) AS total_present,
                                    COUNT(DISTINCT us.user_id, us.subject_id) * 
                                    (DATEDIFF('$end_date', '$start_date') + 1) AS total_expected
                                    FROM usersubjects us
                                    JOIN subjects s ON us.subject_id = s.id
                                    LEFT JOIN attendances a ON us.user_id = a.user_id 
                                        AND us.subject_id = a.subject_id
                                        AND DATE(a.time_in) BETWEEN ? AND ?
                                    WHERE s.faculty_id = ?";
                                
                                if ($subject_id > 0) {
                                    $attendanceQuery .= " AND s.id = ?";
                                    $paramTypes = "ssii";
                                    $params = [$start_date, $end_date, $faculty_id, $subject_id];
                                } else {
                                    $paramTypes = "ssi";
                                    $params = [$start_date, $end_date, $faculty_id];
                                }
                                
                                $attendanceStmt = $conn->prepare($attendanceQuery);
                                $attendanceStmt->bind_param($paramTypes, ...$params);
                                $attendanceStmt->execute();
                                $attendanceResult = $attendanceStmt->get_result();
                                $attendanceRow = $attendanceResult->fetch_assoc();
                                
                                $attendanceRate = ($attendanceRow['total_expected'] > 0) 
                                    ? ($attendanceRow['total_present'] / $attendanceRow['total_expected']) * 100 
                                    : 0;
                                
                                echo round($attendanceRate) . '%';
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

        <!-- Total Students Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                // Count unique students across all subjects
                                $studentCountQuery = "SELECT COUNT(DISTINCT us.user_id) AS total_students
                                                    FROM usersubjects us
                                                    JOIN subjects s ON us.subject_id = s.id
                                                    WHERE s.faculty_id = ?";
                                
                                if ($subject_id > 0) {
                                    $studentCountQuery .= " AND s.id = ?";
                                    $countStmt = $conn->prepare($studentCountQuery);
                                    $countStmt->bind_param("ii", $faculty_id, $subject_id);
                                } else {
                                    $countStmt = $conn->prepare($studentCountQuery);
                                    $countStmt->bind_param("i", $faculty_id);
                                }
                                
                                $countStmt->execute();
                                $countResult = $countStmt->get_result();
                                $countRow = $countResult->fetch_assoc();
                                echo $countRow['total_students'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classes Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Classes in Period</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                // Calculate number of classes in this period based on schedules
                                $classesQuery = "SELECT COUNT(DISTINCT DATE_FORMAT(a.time_in, '%Y-%m-%d')) AS total_classes
                                              FROM attendances a
                                              JOIN subjects s ON a.subject_id = s.id
                                              WHERE s.faculty_id = ? 
                                              AND DATE(a.time_in) BETWEEN ? AND ?";
                                
                                if ($subject_id > 0) {
                                    $classesQuery .= " AND s.id = ?";
                                    $classesStmt = $conn->prepare($classesQuery);
                                    $classesStmt->bind_param("issi", $faculty_id, $start_date, $end_date, $subject_id);
                                } else {
                                    $classesStmt = $conn->prepare($classesQuery);
                                    $classesStmt->bind_param("iss", $faculty_id, $start_date, $end_date);
                                }
                                
                                $classesStmt->execute();
                                $classesResult = $classesStmt->get_result();
                                $classesRow = $classesResult->fetch_assoc();
                                echo $classesRow['total_classes'];
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

        <!-- Late Arrivals Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Late Arrivals</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                // Count late arrivals (more than 15 minutes after schedule start)
                                $lateQuery = "SELECT COUNT(*) AS late_count
                                            FROM attendances a
                                            JOIN subjects s ON a.subject_id = s.id
                                            WHERE s.faculty_id = ? 
                                            AND DATE(a.time_in) BETWEEN ? AND ?";
                                
                                if ($subject_id > 0) {
                                    $lateQuery .= " AND s.id = ?";
                                    $lateStmt = $conn->prepare($lateQuery);
                                    $lateStmt->bind_param("issi", $faculty_id, $start_date, $end_date, $subject_id);
                                } else {
                                    $lateStmt = $conn->prepare($lateQuery);
                                    $lateStmt->bind_param("iss", $faculty_id, $start_date, $end_date);
                                }
                                
                                $lateStmt->execute();
                                $lateResult = $lateStmt->get_result();
                                $lateRow = $lateResult->fetch_assoc();
                                echo $lateRow['late_count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- Attendance Details Modal -->
<div class="modal fade" id="attendanceDetailsModal" tabindex="-1" role="dialog" aria-labelledby="attendanceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceDetailsModalLabel">Attendance Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="attendanceDetails">
                <!-- Attendance details will be loaded here -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="deleteAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAttendanceModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this attendance record?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteAttendanceForm" action="process_attendance.php" method="post">
                    <input type="hidden" id="deleteAttendanceId" name="attendance_id">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">Delete Record</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to view attendance details
    function viewAttendanceDetails(attendanceId) {
        $('#attendanceDetails').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
        $('#attendanceDetailsModal').modal('show');
        
        // Get attendance data via AJAX
        $.ajax({
            url: 'get_attendance_details.php',
            type: 'POST',
            data: {attendance_id: attendanceId},
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    $('#attendanceDetails').html('<div class="alert alert-danger">' + response.error + '</div>');
                    return;
                }
                
                let html = `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">${response.student_name}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">ID: ${response.student_id}</h6>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Subject:</strong> ${response.subject_code} - ${response.subject_name}
                            </li>
                            <li class="list-group-item">
                                <strong>Date:</strong> ${response.date}
                            </li>
                            <li class="list-group-item">
                                <strong>Time:</strong> ${response.time}
                            </li>
                            <li class="list-group-item">
                                <strong>Status:</strong> 
                                <span class="badge badge-${response.status_class}">${response.status}</span>
                            </li>
                        </ul>
                    </div>
                `;
                
                $('#attendanceDetails').html(html);
            },
            error: function() {
                $('#attendanceDetails').html('<div class="alert alert-danger">Error loading attendance details</div>');
            }
        });
    }
    
    // Function to confirm attendance deletion
    function deleteAttendance(attendanceId) {
        $('#deleteAttendanceId').val(attendanceId);
        $('#deleteAttendanceModal').modal('show');
    }
    
    // Document ready function
    $(document).ready(function() {
        // Initialize DataTable
        $('#attendanceTable').DataTable({
            "order": [[3, "desc"], [4, "desc"]],
            "pageLength": 25
        });
        
        // Export button functionality
        $('#exportBtn').click(function() {
            // Build export URL with current filters
            let exportUrl = 'export_attendance.php?faculty_id=<?php echo $faculty_id; ?>';
            
            if (<?php echo $subject_id; ?> > 0) {
                exportUrl += '&subject_id=<?php echo $subject_id; ?>';
            }
            
            if (<?php echo $student_id; ?> > 0) {
                exportUrl += '&student_id=<?php echo $student_id; ?>';
            }
            
            exportUrl += '&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>';
            
            window.location.href = exportUrl;
        });
        
        // Print button functionality
        $('#printBtn').click(function() {
            window.print();
        });
        
        // Refresh button functionality
        $('#refreshBtn').click(function() {
            location.reload();
        });
    });
</script>

<?php include 'includes/footer.php'; ?>