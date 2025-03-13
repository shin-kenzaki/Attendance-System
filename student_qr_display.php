<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'student') {
    $_SESSION['error'] = "Please login as a student to access this page";
    header("Location: index.php");
    exit();
}

// Include database connection
include 'db.php';

// Get subject_id parameter
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Validate subject_id is not zero
if ($subject_id === 0) {
    $_SESSION['error'] = "No subject selected. Please select a subject first.";
    header("Location: student_subjects.php");
    exit();
}

// Check if attendance already recorded today
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$user_id = $_SESSION['user_id'];

$attendance_query = "SELECT a.*, TIME_FORMAT(a.time_in, '%h:%i %p') as formatted_time 
                     FROM attendances a 
                     WHERE a.user_id = ? AND a.subject_id = ? AND a.time_in BETWEEN ? AND ?";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("iiss", $user_id, $subject_id, $today_start, $today_end);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$has_attendance = $attendance_result->num_rows > 0;
$attendance_data = $has_attendance ? $attendance_result->fetch_assoc() : null;

// Get subject information
$query = "SELECT s.*, sch.day, sch.start_time, sch.end_time, sch.room
          FROM subjects s
          LEFT JOIN schedules sch ON s.id = sch.subject_id
          WHERE s.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: student_subjects.php");
    exit();
}

$subject = $result->fetch_assoc();

// Get student information
$student_query = "SELECT *, CONCAT(firstname, ' ', COALESCE(middle_init, ''), ' ', lastname) as full_name FROM users WHERE id = ?";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $_SESSION['user_id']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

// Include common header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">My QR Code</h1>
            <p class="mb-0"><?php echo htmlspecialchars($subject['name']); ?> (<?php echo htmlspecialchars($subject['code']); ?>)</p>
        </div>
        <div>
            <a href="add_attendance.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <!-- QR Code Display Card -->
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10 col-sm-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo $has_attendance ? 'Attendance Already Recorded' : 'Show QR Code to Instructor'; ?>
                    </h6>
                    <?php if (!$has_attendance): ?>
                    <span id="qr-refresh-badge" class="badge badge-success">Active</span>
                    <?php else: ?>
                    <span class="badge badge-success">Present</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <!-- Student Info -->
                    <div class="text-center mb-4">
                        <h5 class="font-weight-bold"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                        <p class="text-muted mb-0">Email: <?php echo htmlspecialchars($student['email']); ?></p>
                    </div>

                    <?php if ($has_attendance): ?>
                    <!-- Attendance Already Recorded Message -->
                    <div class="text-center my-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success fa-5x"></i>
                        </div>
                        <h4 class="mb-3">Your attendance has already been recorded today!</h4>
                        <div class="card bg-light mb-4 mx-auto" style="max-width: 400px;">
                            <div class="card-body">
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($attendance_data['time_in'])); ?></p>
                                <p class="mb-1"><strong>Time:</strong> <?php echo $attendance_data['formatted_time']; ?></p>
                                <p class="mb-0"><strong>Status:</strong> <span class="badge badge-success">Present</span></p>
                            </div>
                        </div>
                        <a href="add_attendance.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Attendance Options
                        </a>
                    </div>
                    <?php else: ?>
                    <!-- QR Code Display -->
                    <div class="qr-wrapper mb-4">
                        <div id="qrcode" class="mx-auto text-center p-3 p-md-4 bg-light rounded" style="max-width: 100%; overflow: hidden;"></div>
                        <div class="text-center mt-3">
                            <p class="text-muted mb-1">Last updated: <span id="last-update">Just now</span></p>
                            <p class="text-muted">Keep this screen visible for the instructor to scan</p>
                            
                            <!-- Add countdown timer display -->
                            <div class="progress mt-3" style="height: 5px;">
                                <div id="qr-refresh-progress" class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                            </div>
                            <p class="mt-1 mb-0 small text-muted">Refreshes in <span id="qr-countdown">30</span> seconds</p>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        This QR code updates automatically every 30 seconds. Please keep the screen on and visible.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php include 'includes/footer.php'; ?>

<?php if (!$has_attendance): ?>
<!-- QR Code library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<script>
let autoRefreshInterval;
let qrCountdownInterval;
let qrTimeRemaining = 30;

// Initialize on page load
$(document).ready(function() {
    generateQRCode();
    startAutoRefresh();
});

// Generate the QR code
function generateQRCode() {
    // Clear previous QR code
    $('#qrcode').empty();
    
    // Get current timestamp
    const now = new Date();
    
    // Create student data for QR code with standard format
    const studentData = {
        user_id: <?php echo $_SESSION['user_id']; ?>,
        student_id: '<?php echo addslashes($student["student_id"] ?? $_SESSION['user_id']); ?>',
        email: '<?php echo addslashes($student['email']); ?>',
        name: '<?php echo addslashes($student['full_name']); ?>',
        subject_id: <?php echo $subject_id; ?>,
        timestamp: now.toISOString(),
        type: 'student_attendance', // Indicate this is a student QR
        department: '<?php echo addslashes($student['department'] ?? ""); ?>'
    };
    
    // Generate QR code
    const qr = qrcode(0, 'M');
    qr.addData(JSON.stringify(studentData));
    qr.make();
    
    // Calculate responsive QR size based on device
    const containerWidth = $('#qrcode').width();
    const qrSize = Math.min(10, Math.max(5, Math.floor(containerWidth / 40))); // Responsive QR size
    
    // Display QR code with responsive size
    const qrImage = qr.createImgTag(qrSize);
    $('#qrcode').html(qrImage);
    
    // Make sure the image is responsive
    $('#qrcode img').css({
        'max-width': '100%',
        'height': 'auto'
    });
    
    // Update timestamp
    $('#last-update').text(now.toLocaleTimeString());
    
    // Reset countdown timer
    qrTimeRemaining = 30;
    $('#qr-countdown').text(qrTimeRemaining);
    $('#qr-refresh-progress').css('width', '100%');
}

// Start auto-refresh timer with visual countdown
function startAutoRefresh() {
    // Clear any existing interval
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    if (qrCountdownInterval) {
        clearInterval(qrCountdownInterval);
    }
    
    // Set up countdown display
    qrTimeRemaining = 30;
    qrCountdownInterval = setInterval(function() {
        qrTimeRemaining--;
        
        // Update countdown text
        $('#qr-countdown').text(qrTimeRemaining);
        
        // Update progress bar
        const progressPercent = (qrTimeRemaining / 30) * 100;
        $('#qr-refresh-progress').css('width', progressPercent + '%');
        
        // Change progress bar color as time decreases
        if (qrTimeRemaining <= 5) {
            $('#qr-refresh-progress').removeClass('bg-info bg-warning').addClass('bg-danger');
        } else if (qrTimeRemaining <= 10) {
            $('#qr-refresh-progress').removeClass('bg-info bg-danger').addClass('bg-warning');
        } else {
            $('#qr-refresh-progress').removeClass('bg-warning bg-danger').addClass('bg-info');
        }
        
        if (qrTimeRemaining <= 0) {
            clearInterval(qrCountdownInterval);
        }
    }, 1000);
    
    // Refresh QR code every 30 seconds
    autoRefreshInterval = setInterval(function() {
        generateQRCode();
        // Restart countdown
        if (qrCountdownInterval) {
            clearInterval(qrCountdownInterval);
        }
        startAutoRefresh();
    }, 30000);
}

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, clear interval
        clearInterval(autoRefreshInterval);
        clearInterval(qrCountdownInterval);
        $('#qr-refresh-badge').removeClass('badge-success').addClass('badge-warning').text('Paused');
    } else {
        // Page is visible again, generate new QR and restart interval
        generateQRCode();
        startAutoRefresh();
        $('#qr-refresh-badge').removeClass('badge-warning').addClass('badge-success').text('Active');
    }
});

// Clean up on page unload
$(window).on('beforeunload', function() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});

// Handle window resize for responsive QR code
$(window).on('resize', function() {
    generateQRCode();
});
</script>
<?php endif; ?>

</body>
</html>
<?php
$conn->close();
?>