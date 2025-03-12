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
        <div class="col-lg-8 col-md-10 col-sm-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo $has_attendance ? 'Attendance Already Recorded' : 'Scan Instructor\'s QR Code'; ?>
                    </h6>
                    <?php if (!$has_attendance): ?>
                    <span id="scanner-status-badge" class="badge badge-warning">Initializing...</span>
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
                    <!-- Scanner Container -->
                    <div class="scanner-wrapper mb-4">
                        <div id="scanner-container" class="mx-auto position-relative" 
                             style="max-width: 100%; border: 2px solid #eaeaea; border-radius: 10px; overflow: hidden;">
                            <!-- Camera switch loading overlay -->
                            <div id="camera-loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; border-radius: 10px; z-index: 10;">
                                <div class="text-center text-white">
                                    <div class="spinner-border text-light mb-2" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p>Switching camera...</p>
                                </div>
                            </div>
                            <div id="reader" style="width: 100%; min-height: 300px;"></div>
                        </div>
                        <div id="scan-status" class="text-center mt-3">
                            <p class="text-muted">Initializing camera...</p>
                        </div>
                    </div>

                    <!-- Camera Controls -->
                    <div class="d-flex flex-column flex-sm-row justify-content-center mb-4">
                        <button id="toggle-camera-btn" class="btn btn-primary mx-1 mb-2 mb-sm-0">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include 'includes/footer.php'; ?>

<?php if (!$has_attendance): ?>
<!-- QR Scanner Scripts -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrCode = null;
let cameraId = null;
let cameraList = [];
let scanning = false;
let isFrontCamera = true; // Track if front camera is in use

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
            // Select back camera by default (usually the second camera in the list)
            if (devices.length > 1) {
                // If multiple cameras, select the second one (usually back camera)
                cameraId = devices[1].id;
                isFrontCamera = false;
            } else {
                // If only one camera, use that
                cameraId = devices[0].id;
                isFrontCamera = true;
            }
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
    $('#camera-loading-overlay').css('display', 'flex');
    scanning = true;
    
    // Configure scanner with responsive sizing
    const scannerWidth = $('#scanner-container').width();
    const qrboxSize = Math.min(scannerWidth * 0.7, 250); // Responsive scanning area
    
    // Configure scanner
    const config = {
        fps: 10,
        qrbox: { width: qrboxSize, height: qrboxSize },
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
        }
    )
    .then(() => {
        $('#scan-status').html('<p class="text-success">Camera active. Point at instructor\'s QR code.</p>');
        $('#scanner-status-badge').removeClass('badge-warning').addClass('badge-success').text('Ready to Scan');
        $('#toggle-camera-btn').html('<i class="fas fa-video-slash"></i> Stop Camera');
        $('#camera-loading-overlay').hide();
        
        // Apply or remove mirror effect based on camera type
        checkCameraType();
    })
    .catch(err => {
        scanning = false;
        $('#scan-status').html(`<p class="text-danger">Error starting camera: ${err}</p>`);
        $('#scanner-status-badge').removeClass('badge-success').addClass('badge-danger').text('Scanner Error');
        $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
        $('#camera-loading-overlay').hide();
        console.error("Error starting camera", err);
    });
}

// Check camera type and apply appropriate styling
function checkCameraType() {
    // If device has multiple cameras, assume the first is front and others are back cameras
    if (cameraList.length > 1) {
        const currentIndex = cameraList.findIndex(camera => camera.id === cameraId);
        isFrontCamera = currentIndex === 0;
    }
    
    // Apply mirror effect only for front camera
    if (isFrontCamera) {
        $('#reader').css('transform', 'scaleX(-1)');
    } else {
        $('#reader').css('transform', 'none');
    }
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

// Handle successful scan - update for better error handling and data validation
function onScanSuccess(decodedText, decodedResult) {
    // Play success sound
    if (successAudio) {
        successAudio.play().catch(err => console.log('Error playing sound:', err));
    }

    try {
        const data = JSON.parse(decodedText);
        
        // Validate that this is a faculty attendance QR code
        if (data.type !== 'attendance' || !data.subject_id) {
            throw new Error('Invalid QR code for this subject');
        }
        
        // Ensure the subject IDs match
        if (parseInt(data.subject_id) !== <?php echo $subject_id; ?>) {
            throw new Error('This QR code is for a different subject');
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

// Submit attendance to server - update to handle schedule_id properly
function submitAttendance(qrData) {
    const attendanceData = {
        subject_id: qrData.subject_id,
        schedule_id: qrData.schedule_id || null,
        user_id: <?php echo $_SESSION['user_id']; ?>, // Changed from student_id to user_id for consistency
        timestamp: new Date().toISOString()
    };
    
    $.ajax({
        url: 'process_attendance.php',
        type: 'POST',
        data: attendanceData,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Stop the scanner to prevent multiple scans
                stopScanner();
                
                // Hide scanner UI
                $('#scanner-container, #scan-status, .d-flex.flex-column.flex-sm-row, .alert.alert-info').hide();
                
                // Show animated success check
                $('div.card-body').prepend(`
                    <div class="success-animation text-center my-5">
                        <div class="checkmark-circle">
                            <div class="checkmark-circle-bg"></div>
                            <div class="checkmark draw"></div>
                        </div>
                        <h3 class="mt-4 text-success">Attendance Recorded!</h3>
                        <p>Your attendance has been successfully recorded</p>
                        <div class="mt-3 redirect-countdown">Completing in <span id="countdown">3</span> seconds...</div>
                    </div>
                `);
                
                // Add CSS for check animation to the page
                $('head').append(`
                    <style>
                        .checkmark-circle {
                            width: 150px;
                            height: 150px;
                            position: relative;
                            display: inline-block;
                            vertical-align: top;
                            margin-left: auto;
                            margin-right: auto;
                        }
                        .checkmark-circle-bg {
                            border-radius: 50%;
                            position: absolute;
                            width: 150px;
                            height: 150px;
                            background-color: #4CAF50;
                            animation: fill-bg .4s ease-in-out;
                        }
                        .checkmark {
                            border-radius: 0;
                            stroke-width: 6;
                            stroke: #fff;
                            stroke-miterlimit: 10;
                            box-shadow: inset 0px 0px 0px #4CAF50;
                            animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
                            position: relative;
                            width: 150px;
                            height: 150px;
                        }
                        .checkmark.draw:after {
                            content: '';
                            transform: scaleX(-1) rotate(135deg);
                            transform-origin: left top;
                            border-right: 25px solid #fff;
                            border-top: 25px solid #fff;
                            position: absolute;
                            left: 30px;
                            top: 60px;
                            width: 60px;
                            height: 30px;
                            animation: check-draw .5s ease-in-out .7s forwards;
                            opacity: 0;
                        }
                        @keyframes stroke {
                            100% {
                                stroke-dashoffset: 0;
                            }
                        }
                        @keyframes fill-bg {
                            0% { transform: scale(0); }
                            100% { transform: scale(1); }
                        }
                        @keyframes check-draw {
                            0% { opacity: 0; }
                            100% { opacity: 1; }
                        }
                        .redirect-countdown {
                            color: #6c757d;
                            font-size: 0.9rem;
                        }
                    </style>
                `);
                
                // Countdown timer
                let seconds = 3;
                const countdownInterval = setInterval(function() {
                    seconds--;
                    $('#countdown').text(seconds);
                    
                    if (seconds <= 0) {
                        clearInterval(countdownInterval);
                        // Replace countdown with completion message and back button
                        $('.redirect-countdown').fadeOut(200, function() {
                            $(this).html(`
                                <div class="mt-4">
                                    <p class="text-success mb-3"><i class="fas fa-check-circle"></i> Attendance successfully recorded!</p>
                                    <a href="add_attendance.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-arrow-left mr-1"></i> Back to Attendance Options
                                    </a>
                                </div>
                            `).fadeIn(300);
                        });
                    }
                }, 1000);
                
            } else if (response.status === 'warning') {
                Swal.fire({
                    title: 'Already Recorded',
                    text: 'Your attendance for today has already been recorded.',
                    icon: 'info',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'add_attendance.php?subject_id=' + <?php echo $subject_id; ?>;
                });
            } else {
                handleError(response.message || 'Failed to record attendance');
                startScanner(); // Resume scanning
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
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

// Updated camera switch button handler with animation
$('#change-camera-btn').on('click', function() {
    if (cameraList.length <= 1) {
        Swal.fire('No Alternative Cameras', 'No other cameras are available on this device', 'info');
        return;
    }
    
    // Find the next camera in the list
    const currentIndex = cameraList.findIndex(camera => camera.id === cameraId);
    const nextIndex = (currentIndex + 1) % cameraList.length;
    cameraId = cameraList[nextIndex].id;
    
    // Only switch if currently scanning
    if (scanning && html5QrCode) {
        // Update status and show loading overlay with fade in effect
        $('#scan-status').html(`<p class="text-primary">Switching to camera ${nextIndex + 1}...</p>`);
        $('#camera-loading-overlay').css('display', 'flex').fadeIn(300);
        
        // Disable the button during transition to prevent multiple clicks
        $(this).prop('disabled', true);
        
        // Stop current camera then immediately start new one
        html5QrCode.stop().then(() => {
            // Configuration for scanner with responsive sizing
            const scannerWidth = $('#scanner-container').width();
            const qrboxSize = Math.min(scannerWidth * 0.7, 250);
            
            const config = {
                fps: 10,
                qrbox: { width: qrboxSize, height: qrboxSize },
                aspectRatio: 1.0,
                formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ]
            };
            
            // Immediately start the new camera
            return html5QrCode.start(cameraId, config, onScanSuccess, onScanFailure);
        })
        .then(() => {
            // Hide loading overlay with fade effect
            $('#camera-loading-overlay').fadeOut(300);
            $('#scan-status').html('<p class="text-success">Camera switched. Scanning for QR code...</p>');
            // Re-enable the button
            $('#change-camera-btn').prop('disabled', false);
            
            // Update mirror effect based on new camera
            checkCameraType();
        })
        .catch(err => {
            // Hide loading overlay if there's an error
            $('#camera-loading-overlay').fadeOut(300);
            scanning = false;
            $('#scan-status').html(`<p class="text-danger">Error switching camera: ${err}</p>`);
            $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
            $('#change-camera-btn').prop('disabled', false);
            console.error("Error during camera switch", err);
        });
    } else {
        // If not scanning, just update the selected camera
        $('#scan-status').html(`<p class="text-info">Selected camera ${nextIndex + 1}. Press "Start Camera" to begin.</p>`);
    }
});

// Add onScanFailure function to handle scan failures silently
function onScanFailure(error) {
    // We don't need to show errors during normal scanning
    console.log('QR code scanning ongoing');
}

// Make scanner responsive on window resize
$(window).on('resize', function() {
    if (scanning) {
        stopScanner();
        // Add small delay to ensure DOM is fully updated
        setTimeout(() => {
            startScanner();
        }, 200);
    }
});

// Add event listener for sidebar toggle
$("#sidebarToggle, #sidebarToggleTop").on('click', function() {
    if (scanning) {
        // Add delay to let the sidebar animation complete
        setTimeout(() => {
            updateScannerDimensions();
        }, 300);
    }
});

// Function to update scanner dimensions without restarting
function updateScannerDimensions() {
    if (html5QrCode && scanning) {
        const scannerWidth = $('#scanner-container').width();
        const qrboxSize = Math.min(scannerWidth * 0.7, 250);
        
        // Gracefully stop and restart with new dimensions
        stopScanner();
        setTimeout(() => {
            startScanner();
        }, 100);
    }
}
</script>
<?php endif; ?>

</body>
</html>
<?php
$conn->close();
?>