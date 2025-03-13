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

// Get today's attendance records for this subject
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

$attendance_query = "SELECT a.*, u.id as user_id, 
                     CONCAT(u.firstname, ' ', COALESCE(u.middle_init, ''), ' ', u.lastname) as student_name,
                     TIME_FORMAT(a.time_in, '%h:%i %p') as formatted_time
                     FROM attendances a 
                     JOIN users u ON a.user_id = u.id
                     WHERE a.subject_id = ? AND a.time_in BETWEEN ? AND ?
                     ORDER BY a.time_in DESC";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("iss", $subject_id, $today_start, $today_end);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance_count = $attendance_result->num_rows;

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
        <div class="col-lg-6 col-md-12 mb-4">
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
                        <div id="scanner-container" class="mx-auto" style="max-width: 100%; border: 2px solid #eaeaea; border-radius: 10px; overflow: hidden; position: relative;">
                            <!-- Camera switch loading overlay -->
                            <div id="camera-loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; border-radius: 10px; z-index: 10;">
                                <div class="text-center text-white">
                                    <div class="spinner-border text-light mb-2" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p>Switching camera...</p>
                                </div>
                            </div>
                            
                            <!-- Add scanner initialization overlay -->
                            <div id="scanner-init-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center; border-radius: 10px; z-index: 10;">
                                <div class="text-center text-white">
                                    <div class="spinner-grow text-light mb-2" style="width: 3rem; height: 3rem;" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 font-weight-bold">Initializing Camera...</p>
                                    <p class="small">Please grant camera access when prompted</p>
                                </div>
                            </div>
                            
                            <!-- Add scan success animation overlay -->
                            <div id="scan-success-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); display: none; justify-content: center; align-items: center; border-radius: 10px; z-index: 11;">
                                <div class="text-center">
                                    <div class="spinner-grow text-success mb-2" style="width: 3rem; height: 3rem;" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <h5 class="mt-3 text-success font-weight-bold">Scan Successful!</h5>
                                </div>
                            </div>
                            
                            <div id="reader" style="width: 100%; min-height: 300px;"></div>
                        </div>
                        <div id="scanner-status" class="text-center mt-3">
                            <p class="text-muted">Initializing camera...</p>
                        </div>
                    </div>

                    <!-- Camera Controls -->
                    <div class="d-flex flex-column flex-sm-row justify-content-center mb-4">
                        <button id="toggle-camera-btn" class="btn btn-primary mb-2 mb-sm-0 mx-1">
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
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Attendance</h6>
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center mr-3">
                            <span id="attendance-count" class="badge badge-primary mr-2"><?php echo $attendance_count; ?></span>
                            <div id="refresh-indicator" class="spinner-border spinner-border-sm text-primary d-none" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <button id="export-csv-btn" class="btn btn-sm btn-outline-secondary" <?php echo ($attendance_count > 0) ? '' : 'disabled'; ?>>
                            <i class="fas fa-download mr-1"></i> Export CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-2 text-muted small">
                        <span>Last updated: <span id="last-updated-time"><?php echo date('h:i:s A'); ?></span></span>
                        <button id="refresh-now-btn" class="btn btn-link btn-sm text-primary">
                            <i class="fas fa-sync-alt"></i> Refresh Now
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendance-table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="sorting" data-sort="student_id">Student ID <i class="fas fa-sort ml-1"></i></th>
                                    <th class="sorting" data-sort="name">Name <i class="fas fa-sort ml-1"></i></th>
                                    <th class="sorting" data-sort="time_in">Time <i class="fas fa-sort ml-1"></i></th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-log">
                                <?php if ($attendance_count > 0): ?>
                                    <?php while ($attendance = $attendance_result->fetch_assoc()): ?>
                                    <tr data-user-id="<?php echo $attendance['user_id']; ?>">
                                        <td><?php echo htmlspecialchars($attendance['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($attendance['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attendance['formatted_time']); ?></td>
                                        <td><span class="badge badge-success">Present</span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr id="empty-log-row">
                                        <td colspan="4" class="text-center">No attendance records yet today.</td>
                                    </tr>
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
let isFrontCamera = true; // Track if front camera is in use
let refreshInterval = null;
let currentSort = { column: 'time_in', direction: 'desc' };
const subject_id = <?php echo $subject_id; ?>;
const schedule_id = <?php echo $current_schedule ? $current_schedule['id'] : 'null'; ?>;

// Initialize on page load
$(document).ready(function() {
    initializeScanner();
    loadInitialAttendanceData();
    startAutoRefresh();
    setupSorting();
    
    // Manual refresh button handler
    $('#refresh-now-btn').on('click', function() {
        refreshAttendanceData();
    });
});

// Load initial attendance data
function loadInitialAttendanceData() {
    <?php if ($attendance_count > 0): ?>
    // Reset the result pointer
    <?php $attendance_result->data_seek(0); ?>
    // Initialize attendance log array
    attendanceLog = [
        <?php 
        $first = true;
        while ($attendance = $attendance_result->fetch_assoc()): 
        if (!$first) echo ',';
        $first = false;
        ?>
        {
            user_id: <?php echo $attendance['user_id']; ?>,
            student_id: "<?php echo addslashes($attendance['user_id']); ?>",
            name: "<?php echo addslashes($attendance['student_name']); ?>",
            timestamp: "<?php echo addslashes($attendance['formatted_time']); ?>",
            raw_timestamp: "<?php echo addslashes($attendance['time_in']); ?>"
        },
        <?php endwhile; ?>
    ];
    <?php endif; ?>
    
    // Enable export button if we have records
    if (attendanceLog.length > 0) {
        $('#export-csv-btn').prop('disabled', false);
    }
}

// Start automatic refresh of attendance data
function startAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    // Refresh attendance data every 30 seconds
    refreshInterval = setInterval(function() {
        refreshAttendanceData();
    }, 30000); // 30 seconds
}

// Refresh attendance data via AJAX
function refreshAttendanceData() {
    // Show loading indicator
    $('#refresh-indicator').removeClass('d-none');
    
    // Get latest attendance data from server
    $.ajax({
        url: 'get_attendance_data.php',
        type: 'GET',
        data: {
            subject_id: subject_id,
            today_only: true
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Update the table with new data
                updateAttendanceTable(response.data);
                
                // Update last updated time
                $('#last-updated-time').text(new Date().toLocaleTimeString());
            } else {
                console.error("Error refreshing attendance data:", response.message);
            }
            
            // Hide loading indicator
            $('#refresh-indicator').addClass('d-none');
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            $('#refresh-indicator').addClass('d-none');
        }
    });
}

// Update attendance table with new data
function updateAttendanceTable(data) {
    if (!data || !Array.isArray(data)) return;
    
    // Update attendance log with new data
    attendanceLog = data;
    
    // Clear table
    $('#attendance-log').empty();
    
    // Update count
    $('#attendance-count').text(data.length);
    
    // Enable/disable export button
    $('#export-csv-btn').prop('disabled', data.length === 0);
    
    // If no records
    if (data.length === 0) {
        $('#attendance-log').html('<tr id="empty-log-row"><td colspan="4" class="text-center">No attendance records yet today.</td></tr>');
        return;
    }
    
    // Sort the data before displaying
    sortAttendanceData();
    
    // Populate table
    attendanceLog.forEach(record => {
        const newRow = `
            <tr data-user-id="${record.user_id}">
                <td>${record.student_id || record.user_id}</td>
                <td>${record.name}</td>
                <td>${record.timestamp || formatTime(record.raw_timestamp)}</td>
                <td><span class="badge badge-success">Present</span></td>
            </tr>
        `;
        $('#attendance-log').append(newRow);
    });
}

// Initialize the scanner with improved loading animations
function initializeScanner() {
    $('#scanner-status').html('<p class="text-muted">Requesting camera permission...</p>');
    $('#scanner-init-overlay').css('display', 'flex');
    
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
            $('#scanner-init-overlay').hide();
            $('#scanner-status').html('<p class="text-danger">No cameras found. Please connect a camera to continue.</p>');
            $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> No Camera');
        }
    }).catch(err => {
        $('#scanner-init-overlay').hide();
        $('#scanner-status').html(`<p class="text-danger">Error accessing camera: ${err}</p>`);
        $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Camera Error');
        console.error("Error getting cameras", err);
    });
}

// Start the scanner with loading animation
function startScanner() {
    if (!html5QrCode) return;
    
    $('#scanner-status').html('<p class="text-primary">Starting camera...</p>');
    $('#scanner-init-overlay').css('display', 'flex');
    scanning = true;
    
    // Configure scanner with responsive settings
    const scannerWidth = $('#scanner-container').width();
    const qrboxSize = Math.min(scannerWidth * 0.7, 250); // Responsive scanning area
    
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
        onScanProgress
    )
    .then(() => {
        // Hide loading overlay with fade effect
        $('#scanner-init-overlay').fadeOut(400);
        
        $('#scanner-status').html('<p class="text-success">Camera active. Scan a student QR code.</p>');
        $('#toggle-camera-btn').html('<i class="fas fa-video-slash"></i> Stop Camera');
        $('#scannerStatusBtn').removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check-circle mr-1"></i> Scanner Active');
        
        // Check camera type and apply appropriate styling
        checkCameraType();
    })
    .catch(err => {
        scanning = false;
        $('#scanner-init-overlay').hide();
        $('#scanner-status').html(`<p class="text-danger">Error starting camera: ${err}</p>`);
        $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
        $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Scanner Error');
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
            $('#scanner-status').html('<p class="text-warning">Camera stopped. Click "Start Camera" to continue scanning.</p>');
            $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-warning').html('<i class="fas fa-pause-circle mr-1"></i> Scanner Paused');
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }
}

// Handle successful scan with animation
function onScanSuccess(decodedText, decodedResult) {
    // Play a success sound
    const successAudio = new Audio('assets/sounds/beep-success.mp3');
    successAudio.play().catch(err => console.log('Error playing sound:', err));
    
    // Show success animation
    $('#scan-success-overlay').css('display', 'flex').fadeIn(200);
    
    try {
        // Try to parse the QR data
        const data = JSON.parse(decodedText);
        
        // Validate the scanned data
        if (!data.user_id || !data.name) {
            throw new Error('Invalid QR code format');
        }
        
        // Check if this QR is for the correct subject
        if (parseInt(data.subject_id) !== parseInt(subject_id)) {
            throw new Error('This QR code is for a different subject');
        }
        
        // Check if student has already been scanned
        const existingRecord = attendanceLog.find(record => record.user_id === data.user_id);
        if (existingRecord) {
            // Hide success animation
            setTimeout(() => {
                $('#scan-success-overlay').fadeOut(200);
            }, 1000);
            
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
        
        // Hide success animation after 1.5 seconds and process attendance
        setTimeout(() => {
            $('#scan-success-overlay').fadeOut(200, function() {
                // Process attendance after animation completes
                recordAttendance(data);
            });
        }, 1500);
        
    } catch (error) {
        // Hide success animation
        $('#scan-success-overlay').fadeOut(200);
        
        console.error('Error processing QR code:', error);
        
        // Show error notification
        Swal.fire({
            title: 'Invalid QR Code',
            text: error.message || 'Could not process the scanned QR code. Please try again.',
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
        timestamp: new Date().toISOString(),
        student_data: JSON.stringify({
            name: studentData.name,
            email: studentData.email || '',
            department: studentData.department || ''
        })
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
                    student_id: studentData.student_id || studentData.user_id || 'N/A',
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
            } else if (response.status === 'info') {
                // Already recorded today
                Swal.fire({
                    title: 'Already Recorded',
                    text: `${studentData.name} attendance was already recorded today`,
                    icon: 'info',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
                $('#scanner-status').html('<p class="text-info">Attendance already recorded. Ready for next scan.</p>');
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
    
    // After successful scan, refresh the attendance data
    setTimeout(refreshAttendanceData, 1000);
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

// Format time for display
function formatTime(timeString) {
    if (!timeString) return '';
    return new Date(timeString).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Set up table sorting
function setupSorting() {
    $('.sorting').on('click', function() {
        const column = $(this).data('sort');
        
        // Toggle direction if same column is clicked
        if (currentSort.column === column) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.direction = 'asc';
        }
        
        // Update sort indicators
        $('.sorting').find('i').attr('class', 'fas fa-sort ml-1');
        $(this).find('i').attr('class', 'fas fa-sort-' + 
            (currentSort.direction === 'asc' ? 'up' : 'down') + ' ml-1');
            
        // Sort and redraw the table
        sortAttendanceData();
        updateAttendanceTable(attendanceLog);
    });
}

// Sort attendance data based on current sort settings
function sortAttendanceData() {
    if (!attendanceLog || attendanceLog.length === 0) return;
    
    attendanceLog.sort((a, b) => {
        let valA, valB;
        
        switch (currentSort.column) {
            case 'student_id':
                valA = a.student_id || a.user_id;
                valB = b.student_id || b.user_id;
                break;
            case 'name':
                valA = a.name;
                valB = b.name;
                break;
            case 'time_in':
                valA = a.raw_timestamp || a.timestamp;
                valB = b.raw_timestamp || b.timestamp;
                break;
            default:
                return 0;
        }
        
        // Direction check
        const compareResult = valA > valB ? 1 : (valA < valB ? -1 : 0);
        return currentSort.direction === 'asc' ? compareResult : -compareResult;
    });
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
    
    // Only switch if currently scanning
    if (scanning && html5QrCode) {
        // Update status and show loading overlay with fade in effect
        $('#scanner-status').html(`<p class="text-primary">Switching to camera ${nextIndex + 1}...</p>`);
        $('#camera-loading-overlay').css('display', 'flex').fadeIn(300);
        
        // Disable the button during transition to prevent multiple clicks
        $(this).prop('disabled', true);
        
        // Stop current camera then immediately start new one
        html5QrCode.stop().then(() => {
            // Configure scanner with responsive settings
            const scannerWidth = $('#scanner-container').width();
            const qrboxSize = Math.min(scannerWidth * 0.7, 250); // Responsive scanning area
            
            const config = {
                fps: 10,
                qrbox: { width: qrboxSize, height: qrboxSize },
                aspectRatio: 1.0,
                formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ]
            };
            
            // Immediately start the new camera
            return html5QrCode.start(cameraId, config, onScanSuccess, onScanProgress);
        })
        .then(() => {
            // Hide loading overlay with fade effect
            $('#camera-loading-overlay').fadeOut(300);
            $('#scanner-status').html('<p class="text-success">Camera switched. Scan a student QR code.</p>');
            $('#scannerStatusBtn').removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check-circle mr-1"></i> Scanner Active');
            // Re-enable the button
            $('#change-camera-btn').prop('disabled', false);
            
            // Check camera type and apply appropriate styling
            checkCameraType();
        })
        .catch(err => {
            // Hide loading overlay if there's an error
            $('#camera-loading-overlay').fadeOut(300);
            scanning = false;
            $('#scanner-status').html(`<p class="text-danger">Error switching camera: ${err}</p>`);
            $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
            $('#scannerStatusBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Scanner Error');
            $('#change-camera-btn').prop('disabled', false);
            console.error("Error during camera switch", err);
        });
    } else {
        // If not scanning, just update the selected camera
        $('#scanner-status').html(`<p class="text-info">Selected camera ${nextIndex + 1}. Press "Start Camera" to begin.</p>`);
    }
});

// Export to CSV handler
$('#export-csv-btn').on('click', function() {
    if (attendanceLog.length === 0) return;
    
    // Create CSV content
    let csvContent = "Student ID,Name,Timestamp,Status\n";
    
    attendanceLog.forEach(record => {
        csvContent += `${record.student_id || record.user_id},"${record.name}",${record.timestamp || formatTime(record.raw_timestamp)},Present\n`;
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

// Clean up on page unload
$(window).on('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    if (html5QrCode && scanning) {
        html5QrCode.stop().catch(err => console.error("Error stopping scanner:", err));
    }
});

// Make scanner responsive when window resizes
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

<style>
/* Add success checkmark animation CSS */
.checkmark-circle {
    width: 100px;
    height: 100px;
    position: relative;
    display: inline-block;
    vertical-align: top;
    margin-left: auto;
    margin-right: auto;
}
.checkmark-circle-bg {
    border-radius: 50%;
    position: absolute;
    width: 100px;
    height: 100px;
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
    width: 100px;
    height: 100px;
}
.checkmark.draw:after {
    content: '';
    transform: scaleX(-1) rotate(135deg);
    transform-origin: left top;
    border-right: 18px solid #fff;
    border-top: 18px solid #fff;
    position: absolute;
    left: 25px;
    top: 45px;
    width: 40px;
    height: 20px;
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
</style>

</body>
</html>
<?php
$conn->close();
?>