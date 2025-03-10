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
$student_query = "SELECT *, CONCAT(firstname, ' ', COALESCE(middle_init, ''), ' ', lastname) as full_name 
                 FROM users WHERE id = ?";
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
            <h1 class="h3 mb-0 text-gray-800">Scan QR Code</h1>
            <p class="mb-0"><?php echo htmlspecialchars($subject['name']); ?> 
               (<?php echo htmlspecialchars($subject['code']); ?>)</p>
        </div>
        <div>
            <a href="add_attendance.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Scan Instructor's QR Code</h6>
                    <span id="scanner-status-badge" class="badge badge-warning">Initializing...</span>
                </div>
                <div class="card-body">
                    <!-- Student Info -->
                    <div class="text-center mb-4">
                        <h5 class="font-weight-bold"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                        <p class="text-muted mb-0">Email: <?php echo htmlspecialchars($student['email']); ?></p>
                    </div>

                    <!-- Scanner Container -->
                    <div class="scanner-wrapper mb-4">
                        <div id="scanner-container" class="mx-auto" 
                             style="max-width: 500px; border: 2px solid #eaeaea; border-radius: 10px; overflow: hidden;">
                            <div id="reader" style="width: 100%; min-height: 400px;"></div>
                        </div>
                        <div id="scan-status" class="text-center mt-3">
                            <p class="text-muted">Initializing camera...</p>
                        </div>
                    </div>

                    <!-- Camera Controls -->
                    <div class="d-flex justify-content-center mb-4">
                        <button id="toggle-camera-btn" class="btn btn-primary mx-1">
                            <i class="fas fa-video-slash"></i> Stop Camera
                        </button>
                        <button id="change-camera-btn" class="btn btn-secondary mx-1">
                            <i class="fas fa-exchange-alt"></i> Change Camera
                        </button>
                    </div>

                    <!-- Instructions -->
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Point your camera at the instructor's QR code to mark your attendance.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include 'includes/footer.php'; ?>

<!-- QR Scanner Scripts -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrCode = null;
let cameraId = null;
let cameraList = [];
let scanning = false;

// Add success sound for successful scans
let successAudio;
$(document).ready(function() {
    // Initialize success sound
    successAudio = new Audio('assets/sounds/beep-success.mp3');
    initializeScanner();
});

// Initialize the scanner
function initializeScanner() {
    $('#scan-status').html('<p class="text-muted">Requesting camera permission...</p>');
    $('#scanner-status-badge').text('Requesting Permission').addClass('badge-warning');
    
    // Create scanner instance
    html5QrCode = new Html5Qrcode("reader");
    
    // Get available cameras
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            cameraList = devices;
            cameraId = devices[0].id;
            startScanner();
        } else {
            $('#scan-status').html('<p class="text-danger">No cameras found. Please connect a camera to continue.</p>');
            $('#scanner-status-badge').removeClass('badge-success').addClass('badge-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> No Camera');
        }
    }).catch(err => {
        $('#scan-status').html(`<p class="text-danger">Error accessing camera: ${err}</p>`);
        $('#scanner-status-badge').removeClass('badge-success').addClass('badge-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Camera Error');
        console.error("Error getting cameras", err);
    });
}

// Start the scanner
function startScanner() {
    if (!html5QrCode) return;
    
    $('#scan-status').html('<p class="text-primary">Starting camera...</p>');
    scanning = true;
    
    // Configure scanner
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ]
    };
    
    // Start scanning
    html5QrCode.start(
        cameraId, 
        config,
        onScanSuccess,
        (errorMessage) => {
            // This is the onScanProgress callback
            // We don't need to show errors during normal scanning
            // Only update UI for actual errors, not scanning progress
        }
    )
    .then(() => {
        $('#scan-status').html('<p class="text-success">Camera active. Point at instructor\'s QR code.</p>');
        $('#scanner-status-badge').removeClass('badge-warning').addClass('badge-success').text('Ready to Scan');
        $('#toggle-camera-btn').html('<i class="fas fa-video-slash"></i> Stop Camera');
    })
    .catch(err => {
        scanning = false;
        $('#scan-status').html(`<p class="text-danger">Error starting camera: ${err}</p>`);
        $('#scanner-status-badge').removeClass('badge-success').addClass('badge-danger').text('Scanner Error');
        $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
        console.error("Error starting camera", err);
    });
}

// Stop the scanner
function stopScanner() {
    if (html5QrCode && scanning) {
        html5QrCode.stop().then(() => {
            scanning = false;
            $('#scan-status').html('<p class="text-warning">Camera stopped. Click "Start Camera" to continue.</p>');
            $('#scanner-status-badge').text('Camera Stopped').removeClass('badge-success').addClass('badge-warning');
            $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }
}

// Handle successful scan
function onScanSuccess(decodedText, decodedResult) {
    // Play success sound
    if (successAudio) {
        successAudio.play().catch(err => console.log('Error playing sound:', err));
    }

    try {
        const data = JSON.parse(decodedText);
        
        // Validate that this is a faculty attendance QR code
        if (data.type !== 'attendance' || !data.subject_id || data.subject_id != <?php echo $subject_id; ?>) {
            throw new Error('Invalid QR code for this subject');
        }
        
        // Check if QR code has expired
        const expiryTime = new Date(data.expires);
        if (expiryTime < new Date()) {
            throw new Error('QR code has expired. Please ask instructor to refresh.');
        }
        
        // Stop scanning temporarily
        stopScanner();
        
        // Record attendance
        submitAttendance(data);
        
    } catch (error) {
        Swal.fire({
            title: 'Invalid QR Code',
            text: error.message || 'Please scan a valid attendance QR code',
            icon: 'error',
            timer: 3000,
            showConfirmButton: false
        });
    }
}

// Submit attendance to server
function submitAttendance(qrData) {
    const attendanceData = {
        subject_id: qrData.subject_id,
        schedule_id: qrData.schedule_id,
        student_id: <?php echo $_SESSION['user_id']; ?>,
        timestamp: new Date().toISOString()
    };
    
    $.ajax({
        url: 'process_attendance.php',
        type: 'POST',
        data: attendanceData,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    title: 'Attendance Recorded!',
                    text: 'Your attendance has been successfully recorded.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Redirect back to subject page
                    window.location.href = 'add_attendance.php?subject_id=' + <?php echo $subject_id; ?>;
                });
            } else {
                handleError(response.message || 'Failed to record attendance');
                startScanner(); // Resume scanning
            }
        },
        error: function() {
            handleError('Server error. Please try again.');
            startScanner(); // Resume scanning
        }
    });
}

// Handle errors
function handleError(message) {
    $('#scan-status').html(`<p class="text-danger">${message}</p>`);
    $('#scanner-status-badge').text('Error').removeClass('badge-success').addClass('badge-danger');
    console.error(message);
}

// Button handlers
$('#toggle-camera-btn').on('click', function() {
    if (scanning) {
        stopScanner();
    } else {
        startScanner();
    }
});

$('#change-camera-btn').on('click', function() {
    if (cameraList.length <= 1) {
        Swal.fire('No Alternative Cameras', 'No other cameras are available on this device', 'info');
        return;
    }
    
    const currentIndex = cameraList.findIndex(camera => camera.id === cameraId);
    const nextIndex = (currentIndex + 1) % cameraList.length;
    cameraId = cameraList[nextIndex].id;
    
    if (scanning) {
        stopScanner();
        startScanner();
    }
});
</script>

</body>
</html>
<?php
$conn->close();
?>