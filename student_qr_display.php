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
                    <h6 class="m-0 font-weight-bold text-primary">Show QR Code to Instructor</h6>
                    <span id="qr-refresh-badge" class="badge badge-success">Active</span>
                </div>
                <div class="card-body">
                    <!-- Student Info -->
                    <div class="text-center mb-4">
                        <h5 class="font-weight-bold"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                        <p class="text-muted mb-0">Email: <?php echo htmlspecialchars($student['email']); ?></p>
                    </div>

                    <!-- QR Code Display -->
                    <div class="qr-wrapper mb-4">
                        <div id="qrcode" class="mx-auto text-center p-3 p-md-4 bg-light rounded" style="max-width: 100%; overflow: hidden;"></div>
                        <div class="text-center mt-3">
                            <p class="text-muted mb-1">Last updated: <span id="last-update">Just now</span></p>
                            <p class="text-muted">Keep this screen visible for the instructor to scan</p>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        This QR code updates automatically every 30 seconds. Please keep the screen on and visible.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php include 'includes/footer.php'; ?>

<!-- QR Code library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<script>
let autoRefreshInterval;

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
    
    // Create student data for QR code
    const studentData = {
        user_id: <?php echo $_SESSION['user_id']; ?>,
        student_id: '<?php echo addslashes($_SESSION['user_id']); ?>', // Added for compatibility
        email: '<?php echo addslashes($student['email']); ?>',
        name: '<?php echo addslashes($student['full_name']); ?>',
        subject_id: <?php echo $subject_id; ?>,
        timestamp: now.toISOString(),
        type: 'student_attendance', // Indicate this is a student QR
        department: '<?php echo addslashes($student['department']); ?>'
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
}

// Start auto-refresh timer
function startAutoRefresh() {
    // Clear any existing interval
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    // Refresh QR code every 30 seconds
    autoRefreshInterval = setInterval(generateQRCode, 30000);
}

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, clear interval
        clearInterval(autoRefreshInterval);
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

</body>
</html>
<?php
$conn->close();
?>