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

// Get subject_id parameter
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Validate subject_id is not zero
if ($subject_id === 0) {
    $_SESSION['error'] = "No subject selected. Please select a subject first.";
    header("Location: faculty_subjects.php");
    exit();
}

// Get subject information
$query = "SELECT * FROM subjects WHERE id = ? AND faculty_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: faculty_subjects.php");
    exit();
}

$subject = $result->fetch_assoc();

// Get the current schedule for this subject if any
$current_schedule = null;
$today = date('l'); // Current day of the week
$current_time = date('H:i:s'); // Current time

$schedule_query = "SELECT * FROM schedules WHERE subject_id = ? AND day = ? AND 
                   TIME(?) BETWEEN TIME(start_time) AND TIME(end_time)";
$schedule_stmt = $conn->prepare($schedule_query);
$schedule_stmt->bind_param("iss", $subject_id, $today, $current_time);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();

if ($schedule_result->num_rows > 0) {
    $current_schedule = $schedule_result->fetch_assoc();
}

// Include common header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">QR Code Scanner</h1>
            <p class="mb-0"><?php echo htmlspecialchars($subject['name']); ?> (<?php echo htmlspecialchars($subject['code']); ?>)</p>
        </div>
        <div>
            <a href="take_attendance.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Scanner and Attendance Log Row -->
    <div class="row">
        <!-- QR Scanner Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Scan Student QR Codes</h6>
                    <div class="dropdown no-arrow">
                        <button id="scannerStatusBtn" class="btn btn-success btn-sm">
                            <i class="fas fa-check-circle mr-1"></i> Scanner Active
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($current_schedule): ?>
                            <div class="alert alert-info">
                                <strong>Current Schedule:</strong> <?= $today ?>, 
                                <?= date('h:i A', strtotime($current_schedule['start_time'])) ?> - 
                                <?= date('h:i A', strtotime($current_schedule['end_time'])) ?>, 
                                Room <?= $current_schedule['room'] ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <strong>No active schedule found for this time.</strong> 
                                Attendance will still be recorded, but without schedule context.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Scanner Container -->
                    <div class="scanner-wrapper mb-4">
                        <div id="scanner-container" class="mx-auto" style="max-width: 500px; border: 2px solid #eaeaea; border-radius: 10px; overflow: hidden;">
                            <div id="reader" style="width: 100%; min-height: 400px; transform: scaleX(-1);"></div> <!-- Added transform: scaleX(-1); -->
                        </div>
                        <div id="scanner-status" class="text-center mt-3">
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
                </div>
            </div>
        </div>

        <!-- Attendance Log Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance Log</h6>
                    <div>
                        <span id="attendance-count" class="badge badge-primary mr-2">0</span>
                        <button id="export-csv-btn" class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="fas fa-download mr-1"></i> Export CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendance-table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Timestamp</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-log">
                                <tr id="empty-log-row">
                                    <td colspan="4" class="text-center">No attendance records yet.</td>
                                </tr>
                            </tbody>
                        </table>
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
// Global variables
let html5QrCode = null;
let cameraId = null;
let cameraList = [];
let scanning = false;
let attendanceLog = [];
const subject_id = <?php echo $subject_id; ?>;
const schedule_id = <?php echo $current_schedule ? $current_schedule['id'] : 'null'; ?>;

// Initialize on page load
$(document).ready(function() {
    initializeScanner();
});

// Initialize the scanner
function initializeScanner() {
    $('#scanner-status').html('<p class="text-muted">Requesting camera permission...</p>');
    
    // Create scanner instance
    html5QrCode = new Html5Qrcode("reader");
    
    // Get available cameras
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            cameraList = devices;
            cameraId = devices[0].id;
            startScanner();
        } else {
            $('#scanner-status').html('<p class="text-danger">No cameras found. Please connect a camera to continue.</p>');
            $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> No Camera');
        }
    }).catch(err => {
        $('#scanner-status').html(`<p class="text-danger">Error accessing camera: ${err}</p>`);
        $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Camera Error');
        console.error("Error getting cameras", err);
    });
}

// Start the scanner
function startScanner() {
    if (!html5QrCode) return;
    
    $('#scanner-status').html('<p class="text-primary">Starting camera...</p>');
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
        onScanProgress
    )
    .then(() => {
        $('#scanner-status').html('<p class="text-success">Camera active. Scan a student QR code.</p>');
        $('#toggle-camera-btn').html('<i class="fas fa-video-slash"></i> Stop Camera');
        $('#scannerStatusBtn').removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check-circle mr-1"></i> Scanner Active');
    })
    .catch(err => {
        scanning = false;
        $('#scanner-status').html(`<p class="text-danger">Error starting camera: ${err}</p>`);
        $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
        $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Scanner Error');
        console.error("Error starting camera", err);
    });
}

// Stop the scanner
function stopScanner() {
    if (html5QrCode && scanning) {
        html5QrCode.stop().then(() => {
            scanning = false;
            $('#scanner-status').html('<p class="text-warning">Camera stopped. Click "Start Camera" to continue scanning.</p>');
            $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-warning').html('<i class="fas fa-pause-circle mr-1"></i> Scanner Paused');
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }
}

// Handle successful scan
function onScanSuccess(decodedText, decodedResult) {
    // Play a success sound
    const successAudio = new Audio('assets/sounds/beep-success.mp3');
    successAudio.play();
    
    try {
        // Try to parse the QR data (expecting JSON with student information)
        const data = JSON.parse(decodedText);
        
        // Validate the scanned data has the required fields
        if (!data.user_id || !data.name) {
            throw new Error('Invalid QR code format');
        }
        
        // Check if student has already been scanned
        const existingRecord = attendanceLog.find(record => record.user_id === data.user_id);
        if (existingRecord) {
            // Already scanned, show a warning
            Swal.fire({
                title: 'Already Scanned!',
                text: `${data.name} has already been marked present.`,
                icon: 'warning',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            return;
        }
        
        // Process attendance
        recordAttendance(data);
        
    } catch (error) {
        console.error('Error processing QR code:', error);
        
        // Show error notification
        Swal.fire({
            title: 'Invalid QR Code',
            text: 'Could not process the scanned QR code. Please try again.',
            icon: 'error',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    }
}

// Handle scan progress
function onScanProgress(error) {
    // We don't need to show errors during normal scanning
    // This is called frequently, so we keep it minimal
}

// Record attendance in the system
function recordAttendance(studentData) {
    // Show loading indicator
    $('#scanner-status').html('<p class="text-primary">Processing attendance...</p>');
    
    // Prepare data for the AJAX request
    const attendanceData = {
        user_id: studentData.user_id,
        subject_id: subject_id,
        schedule_id: schedule_id,
        timestamp: new Date().toISOString()
    };
    
    // Send attendance data to server
    $.ajax({
        url: 'record_attendance.php',
        type: 'POST',
        data: attendanceData,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Add record to the attendance log
                const record = {
                    user_id: studentData.user_id,
                    student_id: studentData.student_id || 'N/A',
                    name: studentData.name,
                    timestamp: new Date().toLocaleTimeString(),
                    status: 'Present'
                };
                addAttendanceRecord(record);
                
                // Show success notification
                Swal.fire({
                    title: 'Attendance Recorded!',
                    text: `${studentData.name} marked as present`,
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
                
                // Update scanner status
                $('#scanner-status').html('<p class="text-success">Attendance recorded! Ready for next scan.</p>');
            } else {
                // Handle error
                Swal.fire('Error', response.message || 'Failed to record attendance', 'error');
                $('#scanner-status').html('<p class="text-danger">Error recording attendance. Try again.</p>');
            }
        },
        error: function() {
            Swal.fire('Server Error', 'Could not connect to the server', 'error');
            $('#scanner-status').html('<p class="text-danger">Server error. Try again.</p>');
        }
    });
}

// Add attendance record to the log table
function addAttendanceRecord(record) {
    // Add to our tracking array
    attendanceLog.push(record);
    
    // Remove empty log message if present
    $('#empty-log-row').remove();
    
    // Add row to table
    const newRow = `
        <tr>
            <td>${record.student_id}</td>
            <td>${record.name}</td>
            <td>${record.timestamp}</td>
            <td><span class="badge badge-success">Present</span></td>
        </tr>
    `;
    $('#attendance-log').prepend(newRow);
    
    // Update count
    $('#attendance-count').text(attendanceLog.length);
    
    // Enable export button if we have records
    if (attendanceLog.length > 0) {
        $('#export-csv-btn').prop('disabled', false);
    }
}

// Toggle camera button handler
$('#toggle-camera-btn').on('click', function() {
    if (scanning) {
        stopScanner();
        $(this).html('<i class="fas fa-video"></i> Start Camera');
    } else {
        startScanner();
        $(this).html('<i class="fas fa-video-slash"></i> Stop Camera');
    }
});

// Change camera button handler
$('#change-camera-btn').on('click', function() {
    if (cameraList.length <= 1) {
        Swal.fire('No Alternative Cameras', 'No other cameras are available on this device', 'info');
        return;
    }
    
    // Find the next camera in the list
    const currentIndex = cameraList.findIndex(camera => camera.id === cameraId);
    const nextIndex = (currentIndex + 1) % cameraList.length;
    cameraId = cameraList[nextIndex].id;
    
    // Restart scanner with new camera
    if (scanning) {
        stopScanner();
        startScanner();
    }
});

// Export to CSV handler
$('#export-csv-btn').on('click', function() {
    if (attendanceLog.length === 0) return;
    
    // Create CSV content
    let csvContent = "Student ID,Name,Timestamp,Status\n";
    
    attendanceLog.forEach(record => {
        csvContent += `${record.student_id},${record.name},${record.timestamp},${record.status}\n`;
    });
    
    // Create download link
    const encodedUri = encodeURI("data:text/csv;charset=utf-8," + csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `attendance_${subject_id}_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    
    // Trigger download
    link.click();
    document.body.removeChild(link);
});
</script>

</body>
</html>
<?php
$conn->close();
?>