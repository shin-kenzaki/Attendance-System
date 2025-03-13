<?php
session_start();
// Check if user is logged in AND has admin access
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'includes/header.php';

// Get total users count
$userQuery = "SELECT COUNT(*) as total FROM users";
$userResult = $conn->query($userQuery);
$totalUsers = $userResult->fetch_assoc()['total'];

// Get active users count
$activeUserQuery = "SELECT COUNT(*) as active FROM users WHERE status = 'active'";
$activeResult = $conn->query($activeUserQuery);
$activeUsers = $activeResult->fetch_assoc()['active'];

// Get total subjects
$subjectQuery = "SELECT COUNT(*) as total FROM subjects";
$subjectResult = $conn->query($subjectQuery);
$totalSubjects = $subjectResult->fetch_assoc()['total'];

// Get today's attendance count
$today = date('Y-m-d');
$attendanceQuery = "SELECT COUNT(*) as today FROM attendances WHERE DATE(FROM_UNIXTIME(time_in)) = '$today'";
$attendanceResult = $conn->query($attendanceQuery);
$todayAttendance = $attendanceResult->fetch_assoc()['today'];

// Get user counts by usertype for the pie chart
$userTypeQuery = "SELECT usertype, COUNT(*) as count FROM users GROUP BY usertype";
$userTypeResult = $conn->query($userTypeQuery);
$userTypeCounts = [];
$userTypeLabels = [];

// Initialize with 0 for common user types to handle missing types
$userTypeCounts = [
    'admin' => 0,
    'faculty' => 0, 
    'student' => 0
];

if ($userTypeResult && $userTypeResult->num_rows > 0) {
    while ($row = $userTypeResult->fetch_assoc()) {
        $userTypeCounts[$row['usertype']] = (int)$row['count'];
        $userTypeLabels[] = ucfirst($row['usertype']);  // Capitalize first letter
    }
}

// Get faculty counts
$totalFacultyQuery = "SELECT COUNT(*) as total FROM users WHERE usertype = 'faculty'";
$totalFacultyResult = $conn->query($totalFacultyQuery);
$totalFaculty = $totalFacultyResult->fetch_assoc()['total'];

$activeFacultyQuery = "SELECT COUNT(*) as active FROM users WHERE usertype = 'faculty' AND status = 'active'";
$activeFacultyResult = $conn->query($activeFacultyQuery);
$activeFaculty = $activeFacultyResult->fetch_assoc()['active'];

// Get student counts
$totalStudentsQuery = "SELECT COUNT(*) as total FROM users WHERE usertype = 'student'";
$totalStudentsResult = $conn->query($totalStudentsQuery);
$totalStudents = $totalStudentsResult->fetch_assoc()['total'];

$activeStudentsQuery = "SELECT COUNT(*) as active FROM users WHERE usertype = 'student' AND status = 'active'";
$activeStudentsResult = $conn->query($activeStudentsQuery);
$activeStudents = $activeStudentsResult->fetch_assoc()['active'];

// Get active subjects and their attendance data
$activeSubjectsQuery = "SELECT id, code, name FROM subjects WHERE status = 1";
$activeSubjectsResult = $conn->query($activeSubjectsQuery);

// Prepare data structures
$subjectsData = [];
$chartLabels = [];

// Generate dates for the last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($date));
    $dates[] = $date;
}

// Define a color palette for the chart lines
$colorPalette = [
    ['bg' => "rgba(78, 115, 223, 0.05)", 'border' => "rgba(78, 115, 223, 1)"],
    ['bg' => "rgba(28, 200, 138, 0.05)", 'border' => "rgba(28, 200, 138, 1)"],
    ['bg' => "rgba(54, 185, 204, 0.05)", 'border' => "rgba(54, 185, 204, 1)"],
    ['bg' => "rgba(246, 194, 62, 0.05)", 'border' => "rgba(246, 194, 62, 1)"],
    ['bg' => "rgba(231, 74, 59, 0.05)", 'border' => "rgba(231, 74, 59, 1)"],
    ['bg' => "rgba(116, 83, 186, 0.05)", 'border' => "rgba(116, 83, 186, 1)"],
    ['bg' => "rgba(255, 99, 132, 0.05)", 'border' => "rgba(255, 99, 132, 1)"],
    ['bg' => "rgba(75, 192, 192, 0.05)", 'border' => "rgba(75, 192, 192, 1)"],
    ['bg' => "rgba(255, 159, 64, 0.05)", 'border' => "rgba(255, 159, 64, 1)"],
    ['bg' => "rgba(153, 102, 255, 0.05)", 'border' => "rgba(153, 102, 255, 1)"]
];

// Collect data for each subject
if ($activeSubjectsResult && $activeSubjectsResult->num_rows > 0) {
    $colorIndex = 0;
    
    while ($subject = $activeSubjectsResult->fetch_assoc()) {
        $attendanceData = [];
        
        // Get attendance for each day
        foreach ($dates as $date) {
            $attendanceQuery = "SELECT COUNT(*) as count FROM attendances 
                                WHERE subject_id = {$subject['id']} 
                                AND DATE(FROM_UNIXTIME(time_in)) = '$date'";
            $attendanceResult = $conn->query($attendanceQuery);
            $attendanceData[] = $attendanceResult->fetch_assoc()['count'];
        }
        
        // Assign a color (cycling through the palette)
        $color = $colorPalette[$colorIndex % count($colorPalette)];
        
        // Store the subject data
        $subjectsData[] = [
            'code' => $subject['code'],
            'name' => $subject['name'],
            'attendance' => $attendanceData,
            'color' => $color
        ];
        
        $colorIndex++;
    }
}

// Get recent logins from updates table
$recentLoginsQuery = "SELECT u.id, u.title, u.message, u.timestamp, 
                      usr.firstname, usr.lastname, usr.email 
                      FROM updates u 
                      JOIN users usr ON u.user_id = usr.id 
                      WHERE u.title = 'Login' 
                      ORDER BY u.timestamp DESC 
                      LIMIT 10";
$recentLoginsResult = $conn->query($recentLoginsQuery);
$recentLogins = [];
if ($recentLoginsResult && $recentLoginsResult->num_rows > 0) {
    while ($row = $recentLoginsResult->fetch_assoc()) {
        $recentLogins[] = $row;
    }
}

// Get recent activities from updates table (after the recentLogins query)
$recentActivitiesQuery = "SELECT u.id, u.title, u.message, u.timestamp, 
                          usr.firstname, usr.lastname 
                          FROM updates u 
                          JOIN users usr ON u.user_id = usr.id 
                          WHERE u.title != 'Login' 
                          ORDER BY u.timestamp DESC 
                          LIMIT 10";
$recentActivitiesResult = $conn->query($recentActivitiesQuery);
$recentActivities = [];
if ($recentActivitiesResult && $recentActivitiesResult->num_rows > 0) {
    while ($row = $recentActivitiesResult->fetch_assoc()) {
        $recentActivities[] = $row;
    }
}
?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
                        <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                                class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Total Users Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="users.php" style="text-decoration: none;">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Users</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalUsers; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Active Users Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="users.php" style="text-decoration: none;">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Active Users</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $activeUsers; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Total Subjects Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="subjects.php" style="text-decoration: none;">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Total Subjects</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSubjects; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-book fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Today's Attendance Card -->
                        <div class="col-xl-3 col-md=6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Today's Attendance</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $todayAttendance; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Statistics -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">System Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 mb-4">
                                            <a href="users.php#faculty" style="text-decoration: none;">
                                                <div class="card bg-primary text-white shadow">
                                                    <div class="card-body">
                                                        Total Faculty
                                                        <div class="text-white-50 small"><?php echo $totalFaculty; ?></div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-4">
                                            <a href="users.php#faculty" style="text-decoration: none;">
                                                <div class="card bg-success text-white shadow">
                                                    <div class="card-body">
                                                        Active Faculty
                                                        <div class="text-white-50 small"><?php echo $activeFaculty; ?></div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-4">
                                            <a href="users.php#students" style="text-decoration: none;">
                                                <div class="card bg-info text-white shadow">
                                                    <div class="card-body">
                                                        Total Students
                                                        <div class="text-white-50 small"><?php echo $totalStudents; ?></div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-4">
                                            <a href="users.php#students" style="text-decoration: none;">
                                                <div class="card bg-warning text-white shadow">
                                                    <div class="card-body">
                                                        Active Students
                                                        <div class="text-white-50 small"><?php echo $activeStudents; ?></div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Attendance Overview Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Attendance Overview</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Time Range:</div>
                                            <a class="dropdown-item" href="#">Last 7 Days</a>
                                            <a class="dropdown-item" href="#">Last 30 Days</a>
                                            <a class="dropdown-item" href="#">This Semester</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="myAreaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Distribution Pie Chart -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">User Distribution</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">View Options:</div>
                                            <a class="dropdown-item" href="#">By User Type</a>
                                            <a class="dropdown-item" href="#">By Department</a>
                                            <a class="dropdown-item" href="#">By Status</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="myPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <?php foreach (array_keys($userTypeCounts) as $index => $type): 
                                            $colors = ['text-primary', 'text-success', 'text-info']; 
                                            $color = isset($colors[$index]) ? $colors[$index] : 'text-secondary';
                                        ?>
                                        <span class="mr-2">
                                            <i class="fas fa-circle <?php echo $color; ?>"></i> <?php echo ucfirst($type); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Recent Activities -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                                    <a href="recent_activities_logins.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (empty($recentActivities)): ?>
                                        <p class="text-center">No recent activities found</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Action</th>
                                                        <th>User</th>
                                                        <th>Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recentActivities as $activity): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                                            <br><small><?php echo htmlspecialchars($activity['message']); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($activity['firstname'] . ' ' . $activity['lastname']); ?></td>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Logins -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Logins</h6>
                                    <a href="recent_activities_logins.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Login Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recentLogins)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No login records found</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recentLogins as $login): ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo htmlspecialchars($login['firstname'] . ' ' . $login['lastname']); ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($login['email']); ?></small>
                                                        </td>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($login['timestamp'])); ?></td>
                                                        <td>
                                                            <span class="badge badge-success">Success</span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
    // Area Chart - Attendance Overview
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById("myAreaChart");
        
        // Define datasets for the chart
        var datasets = [];
        
        <?php
        // Generate dataset for each subject
        foreach ($subjectsData as $subject) {
            echo "datasets.push({
                label: '{$subject['code']} - {$subject['name']}',
                lineTension: 0.3,
                backgroundColor: '{$subject['color']['bg']}',
                borderColor: '{$subject['color']['border']}',
                pointRadius: 3,
                pointBackgroundColor: '{$subject['color']['border']}',
                pointBorderColor: '{$subject['color']['border']}',
                pointHoverRadius: 3,
                pointHoverBackgroundColor: '{$subject['color']['border']}',
                pointHoverBorderColor: '{$subject['color']['border']}',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [" . implode(',', $subject['attendance']) . "],
            });\n";
        }
        
        // If no subjects found, add a placeholder
        if (empty($subjectsData)) {
            echo "datasets.push({
                label: 'No Active Subjects',
                lineTension: 0.3,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: 'rgba(78, 115, 223, 1)',
                pointHoverRadius: 3,
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [0, 0, 0, 0, 0, 0, 0],
            });\n";
        }
        ?>
        
        var myLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: datasets
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        time: {
                            unit: 'date'
                        },
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            beginAtZero: true,
                            callback: function(value) { return value.toFixed(0); } // Display whole numbers
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }],
                },
                legend: {
                    display: true,
                    position: 'bottom', // Show legend at the bottom
                    labels: {
                        boxWidth: 12 // Smaller legend items
                    }
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10
                }
            }
        });

        // Keep the existing Pie Chart code
        // Pie Chart - User Distribution
        var ctx2 = document.getElementById("myPieChart");
        var myPieChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', array_keys($userTypeCounts))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($userTypeCounts)); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
                legend: {
                    display: false
                },
                cutoutPercentage: 80,
            },
        });
    });
    </script>

<?php include 'includes/footer.php'; ?>