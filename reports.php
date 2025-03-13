<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'admin') {
    // User not logged in or not an admin - redirect to login page
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
require 'db.php';

// Get basic statistics
$statsQuery = "SELECT 
    (SELECT COUNT(DISTINCT user_id) FROM attendances WHERE DATE(time_in) = CURDATE()) as unique_students_today,
    (SELECT COUNT(*) FROM attendances) as total_attendances,
    (SELECT COUNT(*) FROM users WHERE usertype = 'student') as total_students,
    (SELECT COUNT(*) FROM users WHERE usertype = 'faculty') as total_faculty,
    (SELECT COUNT(*) FROM subjects) as total_subjects";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Check if reports table exists
$tableCheckQuery = "SHOW TABLES LIKE 'reports'";
$tableCheckResult = $conn->query($tableCheckQuery);
$tableExists = $tableCheckResult->num_rows > 0;

if (!$tableExists) {
    // Create reports table if it doesn't exist
    $createTableQuery = "CREATE TABLE reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTableQuery);
} else {
    // Check if created_at column exists
    $columnCheckQuery = "SHOW COLUMNS FROM reports LIKE 'created_at'";
    $columnCheckResult = $conn->query($columnCheckQuery);
    $columnExists = $columnCheckResult->num_rows > 0;

    if (!$columnExists) {
        // Add created_at column if it doesn't exist
        $addColumnQuery = "ALTER TABLE reports ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $conn->query($addColumnQuery);
    }
}

// Fetch reports data
$reportsQuery = "SELECT * FROM reports ORDER BY created_at DESC";
$reportsResult = $conn->query($reportsQuery);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Reports</h1>
            <p class="mb-4">Overview of system reports and statistics.</p>
        </div>
        <div>
            <a href="generate_report.php" class="btn btn-primary btn-sm">
                <i class="fas fa-file-alt fa-sm"></i> Generate New Report
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <!-- Unique Students Today Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Unique Students Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['unique_students_today'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Attendances Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Attendances</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_attendances'] ?></div>
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_students'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
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

    <!-- Reports Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Generated Reports</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Report Name</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($reportsResult->num_rows > 0) {
                            while($report = $reportsResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($report['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($report['report_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($report['created_at']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center'>No reports found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
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
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: '<"d-flex align-items-center justify-content-between"<"mr-3"l><"ml-auto"f>>rt<"bottom d-flex align-items-center justify-content-between"ip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search reports...",
            lengthMenu: "Entries per page: _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ reports",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        }
    });
});
</script>

<style>
/* Custom styles for the reports page */
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
</style>

</body>
</html>
<?php
$conn->close();
?>
