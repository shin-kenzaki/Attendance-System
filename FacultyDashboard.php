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

// Include database connection if needed
include 'db.php';

// Get faculty ID from session
$faculty_id = $_SESSION['user_id'];

// Get total subjects taught by faculty
$subjectQuery = "SELECT COUNT(*) as total FROM subjects WHERE faculty_id = ?";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$totalSubjects = $stmt->get_result()->fetch_assoc()['total'];

// Get total students across all subjects
$studentsQuery = "SELECT COUNT(DISTINCT us.user_id) as total 
                 FROM usersubjects us 
                 JOIN subjects s ON us.subject_id = s.id 
                 WHERE s.faculty_id = ?";
$stmt = $conn->prepare($studentsQuery);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$totalStudents = $stmt->get_result()->fetch_assoc()['total'];

// Get today's attendance count
$today = date('Y-m-d');
$attendanceQuery = "SELECT COUNT(*) as total 
                   FROM attendances a 
                   JOIN subjects s ON a.subject_id = s.id 
                   WHERE s.faculty_id = ? AND DATE(a.time_in) = ?";
$stmt = $conn->prepare($attendanceQuery);
$stmt->bind_param("is", $faculty_id, $today);
$stmt->execute();
$todayAttendance = $stmt->get_result()->fetch_assoc()['total'];

// Get today's schedule
$dayOfWeek = date('l');
$scheduleQuery = "SELECT s.*, sub.code, sub.name 
                 FROM schedules s 
                 JOIN subjects sub ON s.subject_id = sub.id 
                 WHERE sub.faculty_id = ? AND s.day = ? 
                 ORDER BY s.start_time";
$stmt = $conn->prepare($scheduleQuery);
$stmt->bind_param("is", $faculty_id, $dayOfWeek);
$stmt->execute();
$todaySchedule = $stmt->get_result();

// Get recent attendance records
$recentQuery = "SELECT a.*, u.firstname, u.lastname, s.code as subject_code 
                FROM attendances a 
                JOIN users u ON a.user_id = u.id 
                JOIN subjects s ON a.subject_id = s.id 
                WHERE s.faculty_id = ? 
                ORDER BY a.time_in DESC LIMIT 10";
$stmt = $conn->prepare($recentQuery);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$recentAttendance = $stmt->get_result();

// Include common header (which handles the HTML head, navigation sidebar, and topbar)
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Faculty Dashboard</h1>
        <div>
            <a href="take_attendance.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-qrcode fa-sm text-white-50"></i> Take Attendance
            </a>
            <a href="faculty_reports.php" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Total Subjects Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                My Subjects</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSubjects; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Attendance Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
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

        <!-- Today's Classes Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Today's Classes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $todaySchedule->num_rows; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Today's Schedule -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Schedule (<?php echo $dayOfWeek; ?>)</h6>
                    <a href="faculty_schedules.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-calendar"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($todaySchedule->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Room</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($class = $todaySchedule->fetch_assoc()): 
                                        $now = new DateTime();
                                        $start = new DateTime($class['start_time']);
                                        $end = new DateTime($class['end_time']);
                                        $status = '';
                                        
                                        if ($now > $end) {
                                            $status = 'completed';
                                        } elseif ($now >= $start && $now <= $end) {
                                            $status = 'ongoing';
                                        } else {
                                            $status = 'upcoming';
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($class['start_time'])) . ' - ' . 
                                                       date('h:i A', strtotime($class['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($class['code'] . ': ' . $class['name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['room']); ?></td>
                                            <td>
                                                <?php if ($status == 'ongoing'): ?>
                                                    <a href="take_attendance.php?subject_id=<?php echo $class['subject_id']; ?>" 
                                                       class="btn btn-success btn-sm">Take Attendance</a>
                                                <?php elseif ($status == 'upcoming'): ?>
                                                    <span class="badge badge-info">Upcoming</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-gray-300 mb-3"></i>
                            <p class="mb-0">No classes scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Attendance</h6>
                    <a href="faculty_attendances.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($recentAttendance->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $recentAttendance->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></td>
                                            <td><?php echo htmlspecialchars($record['subject_code']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($record['time_in'])); ?></td>
                                            <td><span class="badge badge-success">Present</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard fa-3x text-gray-300 mb-3"></i>
                            <p class="mb-0">No recent attendance records</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="take_attendance.php" class="btn btn-primary btn-block py-3">
                                <i class="fas fa-qrcode fa-2x mb-2"></i><br>Take Attendance
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="faculty_students.php" class="btn btn-success btn-block py-3">
                                <i class="fas fa-users fa-2x mb-2"></i><br>View Students
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="faculty_reports.php" class="btn btn-info btn-block py-3">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i><br>View Reports
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="faculty_schedules.php" class="btn btn-warning btn-block py-3">
                                <i class="fas fa-calendar-alt fa-2x mb-2"></i><br>View Schedule
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php
// Include the Chart.js scripts before including footer.php
?>
<!-- Page level plugins -->
<script src="vendor/chart.js/Chart.min.js"></script>

<!-- Page level custom scripts -->
<script>
// Area Chart Example
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById("myAttendanceChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [{
                label: "Math 101",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [20, 18, 19, 17, 20, 0, 0],
            },
            {
                label: "Adv. Calculus",
                lineTension: 0.3,
                backgroundColor: "rgba(28, 200, 138, 0.05)",
                borderColor: "rgba(28, 200, 138, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(28, 200, 138, 1)",
                pointBorderColor: "rgba(28, 200, 138, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(28, 200, 138, 1)",
                pointHoverBorderColor: "rgba(28, 200, 138, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [15, 17, 15, 16, 14, 0, 0],
            },
            {
                label: "Statistics",
                lineTension: 0.3,
                backgroundColor: "rgba(54, 185, 204, 0.05)",
                borderColor: "rgba(54, 185, 204, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(54, 185, 204, 1)",
                pointBorderColor: "rgba(54, 185, 204, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(54, 185, 204, 1)",
                pointHoverBorderColor: "rgba(54, 185, 204, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: [12, 11, 12, 10, 12, 0, 0],
            }],
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
                        callback: function(value) { return value; }
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
                position: 'bottom'
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

    // Pie Chart Example
    var ctx2 = document.getElementById("mySubjectChart");
    var myPieChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ["Math 101", "Adv. Calculus", "Statistics", "Discrete Math"],
            datasets: [{
                data: [40, 25, 20, 15],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
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