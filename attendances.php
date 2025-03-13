<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User not logged in - redirect to login page
    header("Location: index.php");
    exit();
} 
// Allow access if user is admin or faculty (admins should have access to all pages)
else if ($_SESSION['usertype'] !== 'admin' && $_SESSION['usertype'] !== 'faculty') {
    // User logged in but wrong role - show 404 page
    header("Location: 404.php");
    exit();
}

include 'includes/header.php';
require 'db.php';

// Get filter parameters
$filter_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$filter_date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$filter_date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$filter_usertype = isset($_GET['usertype']) ? $_GET['usertype'] : '';

// Build SQL query with possible filters
$sql = "SELECT a.id AS attendance_id, 
        DATE_FORMAT(a.time_in, '%Y-%m-%d %r') AS attendance_time, 
        CONCAT(u.lastname, ', ', u.firstname) AS user_name, 
        s.code AS subject_code, 
        s.name AS subject_name 
        FROM attendances a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN subjects s ON a.subject_id = s.id
        WHERE u.usertype = 'student'"; // Added condition to only get attendances where usertype is 'student'

// Add WHERE clauses based on filters
$where_clauses = [];

if (!empty($filter_subject)) {
    $where_clauses[] = "a.subject_id = " . $conn->real_escape_string($filter_subject);
}

if (!empty($filter_date_start) && !empty($filter_date_end)) {
    $where_clauses[] = "DATE(a.time_in) BETWEEN '" . $conn->real_escape_string($filter_date_start) . "' AND '" . $conn->real_escape_string($filter_date_end) . "'";
}

if (!empty($filter_usertype)) {
    $where_clauses[] = "u.usertype = '" . $conn->real_escape_string($filter_usertype) . "'";
}

// Add WHERE clause to the query if any filters were applied
if (!empty($where_clauses)) {
    $sql .= " AND " . implode(" AND ", $where_clauses);
}

$result = $conn->query($sql);

// Get subjects for the dropdown
$subjects_sql = "SELECT id, name FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_sql);

// Get basic statistics
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM attendances) as total_attendances,
    (SELECT COUNT(DISTINCT user_id) FROM attendances WHERE DATE(time_in) = CURDATE()) as unique_students_today,
    (SELECT COUNT(*) FROM subjects) as total_subjects,
    (SELECT COUNT(*) FROM users WHERE usertype = 'faculty') as total_faculty";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Attendances</h1>
            <p class="mb-4">Displaying all attendances data in a table.</p>
        </div>
        <div>
            <div class="btn-group mr-2">
                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-cog fa-sm"></i> Batch Actions
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" onclick="batchAction('delete')">
                        <i class="fas fa-trash fa-sm fa-fw mr-2"></i>Delete Selected
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <!-- Total Attendances Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Attendances</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_attendances'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unique Students Today Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Unique Students Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['unique_students_today'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Subjects Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Subjects</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_subjects'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Faculty Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Faculty</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_faculty'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filter Attendances</h6>
            <button class="btn btn-sm btn-link" type="button" data-toggle="collapse" data-target="#filtersCollapse" aria-expanded="true" aria-controls="filtersCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="filtersCollapse">
            <div class="card-body">
                <form method="get" action="attendances.php" id="filterForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="subject_id"><i class="fas fa-book mr-1"></i> Subject</label>
                            <select class="form-control uniform-filter-size" id="subject_id" name="subject_id">
                                <option value="">All</option>
                                <?php while($subject = $subjects_result->fetch_assoc()): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo $filter_subject == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="date_start"><i class="fas fa-calendar mr-1"></i> Date Start</label>
                            <input type="date" class="form-control uniform-filter-size" id="date_start" name="date_start" value="<?php echo htmlspecialchars($filter_date_start); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="date_end"><i class="fas fa-calendar mr-1"></i> Date End</label>
                            <input type="date" class="form-control uniform-filter-size" id="date_end" name="date_end" value="<?php echo htmlspecialchars($filter_date_end); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="usertype"><i class="fas fa-user-tag mr-1"></i> User Type</label>
                            <select class="form-control uniform-filter-size" id="usertype" name="usertype">
                                <option value="">All</option>
                                <option value="student" <?php echo $filter_usertype == 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="faculty" <?php echo $filter_usertype == 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                <option value="admin" <?php echo $filter_usertype == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter mr-1"></i> Apply Filters
                            </button>
                            <a href="attendances.php" class="btn btn-secondary btn-sm ml-2">
                                <i class="fas fa-undo mr-1"></i> Reset Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Attendances Data</h6>
            <div class="dropdown no-arrow">
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
        <div class="card-body">
            <div class="table-responsive">
                <div class="px-3">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="30px"><input type="checkbox" id="selectAllAttendances"></th>
                                <th>Attendance ID</th>
                                <th>User Name</th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                // Output data of each row
                                while($row = $result->fetch_assoc()) {
                                    $status = "Present";
                                    $statusClass = "success";
                                    // Determine attendance status based on check-in time compared to schedule
                                    if (!empty($row['start_time'])) {
                                        $scheduleTime = strtotime(date('Y-m-d', strtotime($row['attendance_time'])) . ' ' . $row['start_time']);
                                        $checkInTime = strtotime($row['attendance_time']);
                                        $minutesLate = ($checkInTime - $scheduleTime) / 60;
                                        if ($minutesLate > 15) {
                                            $status = "Late";
                                            $statusClass = "warning";
                                        }
                                    }
                                    echo "<tr>";
                                    echo "<td><input type='checkbox' name='attendance_checkbox' value='" . htmlspecialchars($row["attendance_id"]) . "'></td>";
                                    echo "<td><span class='truncate' title='" . htmlspecialchars($row["attendance_id"]) . "'>" . htmlspecialchars($row["attendance_id"]) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . htmlspecialchars($row["user_name"]) . "'>" . htmlspecialchars($row["user_name"]) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . htmlspecialchars($row["subject_code"]) . "'>" . htmlspecialchars($row["subject_code"]) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . htmlspecialchars($row["subject_name"]) . "'>" . htmlspecialchars($row["subject_name"]) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . date('M d, Y', strtotime($row["attendance_time"])) . "'>" . date('M d, Y', strtotime($row["attendance_time"])) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . date('h:i A', strtotime($row["attendance_time"])) . "'>" . date('h:i A', strtotime($row["attendance_time"])) . "</span></td>";
                                    echo "<td><span class='badge badge-" . $statusClass . "'>" . $status . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No attendances found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<?php include 'includes/footer.php'; ?>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"d-flex align-items-center justify-content-between"<"mr-3"l><"ml-auto"f>>rt<"bottom d-flex align-items-center justify-content-between"ip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search attendances...",
            lengthMenu: "Entries per page: _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ attendances",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        }
    });

    // Handle "Select All" for Attendances table
    $('#selectAllAttendances').change(function() {
        const isChecked = $(this).prop('checked');
        $('input[name="attendance_checkbox"]').prop('checked', isChecked);
    });

    // Update header checkbox when individual checkboxes change
    $('#dataTable tbody').on('change', 'input[name="attendance_checkbox"]', function() {
        const totalCheckboxes = $('input[name="attendance_checkbox"]').length;
        const checkedCheckboxes = $('input[name="attendance_checkbox"]:checked').length;
        $('#selectAllAttendances').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
    });
});

// Function to handle batch actions
function batchAction(action) {
    const selectedIds = [];
    $('input[name="attendance_checkbox"]:checked').each(function() {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        Swal.fire('No Attendances Selected', 'Please select attendances to perform this action.', 'warning');
        return;
    }

    let title, text, confirmButtonText, icon = 'warning';
    switch(action) {
        case 'delete':
            title = 'Delete Selected Attendances?';
            text = 'This will permanently delete all selected attendances!';
            confirmButtonText = 'Yes, delete them!';
            icon = 'error';
            break;
    }

    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Processing...',
                html: 'Please wait while we process your request.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: 'batch_attendance_action.php',
                type: 'POST',
                data: {
                    action: action,
                    attendance_ids: selectedIds
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            Swal.fire('Success!', data.message, 'success')
                            .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message || 'An unknown error occurred', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error!', 'Invalid server response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error!', 'Request failed: ' + error, 'error');
                }
            });
        }
    });
}
</script>

<style>
/* Custom styles for the attendances page */
.truncate {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

/* Improve table row hover effect */
#dataTable tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05) !important;
}

/* Uniform filter input sizes */
.uniform-filter-size {
    width: 100%;
}
</style>

</body>
</html>
<?php
$conn->close();
?>