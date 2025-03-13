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
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

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

// Include header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Attendance Reports</h1>
        <div>
            <button class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm" id="printBtn">
                <i class="fas fa-print fa-sm text-white-50"></i> Print Report
            </button>
            <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ml-2" id="exportBtn">
                <i class="fas fa-download fa-sm text-white-50"></i> Export PDF
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Reports</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row">
                <div class="col-md-4 mb-3">
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
                        
                        while ($row = $subjectResult->fetch_assoc()) {
                            $selected = ($subject_id == $row['id']) ? 'selected' : '';
                            echo "<option value='" . $row['id'] . "' $selected>" . 
                                htmlspecialchars($row['code'] . ' - ' . $row['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="month">Month:</label>
                    <select class="form-control" id="month" name="month">
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $selected = ($month == $i) ? 'selected' : '';
                            echo "<option value='$i' $selected>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="year">Year:</label>
                    <select class="form-control" id="year" name="year">
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= $currentYear - 2; $i--) {
                            $selected = ($year == $i) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="faculty_reports.php" class="btn btn-secondary ml-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics Row -->
    <div class="row">
        <!-- Total Attendance Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Attendance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $attendanceQuery = "SELECT COUNT(*) as total FROM attendances a 
                                                  JOIN subjects s ON a.subject_id = s.id 
                                                  WHERE s.faculty_id = ? AND MONTH(a.time_in) = ? AND YEAR(a.time_in) = ?";
                                $params = [$faculty_id, $month, $year];
                                $types = "iis";
                                
                                if ($subject_id > 0) {
                                    $attendanceQuery .= " AND s.id = ?";
                                    $params[] = $subject_id;
                                    $types .= "i";
                                }
                                
                                $stmt = $conn->prepare($attendanceQuery);
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $row = $result->fetch_assoc();
                                echo $row['total'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Attendance Rate Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Attendance Rate</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php
                                        // Calculate attendance rate
                                        $rateQuery = "SELECT 
                                            (COUNT(DISTINCT a.id) / (COUNT(DISTINCT us.user_id) * COUNT(DISTINCT DATE(a.time_in)))) * 100 as rate
                                            FROM usersubjects us
                                            JOIN subjects s ON us.subject_id = s.id
                                            LEFT JOIN attendances a ON us.subject_id = a.subject_id 
                                            AND us.user_id = a.user_id 
                                            AND MONTH(a.time_in) = ? 
                                            AND YEAR(a.time_in) = ?
                                            WHERE s.faculty_id = ?";
                                        
                                        $params = [$month, $year, $faculty_id];
                                        $types = "ssi";
                                        
                                        if ($subject_id > 0) {
                                            $rateQuery .= " AND s.id = ?";
                                            $params[] = $subject_id;
                                            $types .= "i";
                                        }
                                        
                                        $stmt = $conn->prepare($rateQuery);
                                        $stmt->bind_param($types, ...$params);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $row = $result->fetch_assoc();
                                        echo number_format($row['rate'], 1) . '%';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $studentQuery = "SELECT COUNT(DISTINCT us.user_id) as total 
                                               FROM usersubjects us 
                                               JOIN subjects s ON us.subject_id = s.id 
                                               WHERE s.faculty_id = ?";
                                
                                if ($subject_id > 0) {
                                    $studentQuery .= " AND s.id = ?";
                                    $stmt = $conn->prepare($studentQuery);
                                    $stmt->bind_param("ii", $faculty_id, $subject_id);
                                } else {
                                    $stmt = $conn->prepare($studentQuery);
                                    $stmt->bind_param("i", $faculty_id);
                                }
                                
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $row = $result->fetch_assoc();
                                echo $row['total'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Late Attendance Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late Arrivals</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                // Count late arrivals
                                $lateQuery = "SELECT COUNT(*) as total FROM attendances a 
                                            JOIN subjects s ON a.subject_id = s.id 
                                            WHERE s.faculty_id = ? 
                                            AND MONTH(a.time_in) = ? 
                                            AND YEAR(a.time_in) = ?";
                                
                                $params = [$faculty_id, $month, $year];
                                $types = "iis";
                                
                                if ($subject_id > 0) {
                                    $lateQuery .= " AND s.id = ?";
                                    $params[] = $subject_id;
                                    $types .= "i";
                                }
                                
                                $stmt = $conn->prepare($lateQuery);
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $row = $result->fetch_assoc();
                                echo $row['total'];
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

    <!-- Charts Row -->
    <div class="row">
        <!-- Daily Attendance Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Attendance Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="dailyAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Distribution Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance by Subject</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="subjectDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include 'includes/footer.php'; ?>

<script>
// Chart.js configurations
$(document).ready(function() {
    // Daily Attendance Chart
    var ctx = document.getElementById("dailyAttendanceChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["1", "5", "10", "15", "20", "25", "30"],
            datasets: [{
                label: "Attendance",
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
                data: [0, 10, 5, 15, 10, 20, 15],
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
                display: false
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
                caretPadding: 10,
            }
        }
    });

    // Subject Distribution Chart
    var ctx2 = document.getElementById("subjectDistributionChart");
    var myPieChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ["Subject 1", "Subject 2", "Subject 3"],
            datasets: [{
                data: [55, 30, 15],
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

    // Print button handler
    $("#printBtn").click(function() {
        window.print();
    });

    // Export button handler
    $("#exportBtn").click(function() {
        // Add PDF export logic here
        alert('PDF export feature will be implemented here');
    });
});
</script>
