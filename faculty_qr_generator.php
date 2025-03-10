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
            <h1 class="h3 mb-0 text-gray-800">Attendance QR Code</h1>
            <p class="mb-0"><?php echo htmlspecialchars($subject['name']); ?> (<?php echo htmlspecialchars($subject['code']); ?>)</p>
        </div>
        <div>
            <a href="take_attendance.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <!-- QR Code and Attendance Row -->
    <div class="row">
        <!-- QR Code Display Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance QR Code</h6>
                    <div class="dropdown no-arrow">
                        <button id="qrStatusBtn" class="btn btn-success btn-sm">
                            <i class="fas fa-check-circle mr-1"></i> QR Active
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
                                Room <?= htmlspecialchars($current_schedule['room']) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <strong>No active schedule found for this time.</strong> 
                                Attendance will still be recorded, but without schedule context.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- QR Code Container -->
                    <div class="qr-wrapper mb-4">
                        <div id="qr-container" class="mx-auto d-flex justify-content-center align-items-center" style="min-height: 300px;">
                            <div id="qrcode" class="border p-3 bg-white rounded"></div>
                        </div>
                        <div id="qr-status" class="text-center mt-3">
                            <span class="badge badge-pill badge-success px-3 py-2 font-weight-bold">QR Code Active</span>
                            <p class="text-muted mt-2">Last refreshed: <span id="last-refresh-time">Just now</span></p>
                        </div>
                    </div>

                    <!-- QR Controls -->
                    <div class="d-flex justify-content-center mb-4">
                        <button id="refresh-qr-btn" class="btn btn-primary mx-1">
                            <i class="fas fa-sync-alt"></i> Refresh QR Code
                        </button>
                        <button id="toggle-qr-btn" class="btn btn-warning mx-1">
                            <i class="fas fa-pause-circle"></i> Pause Sharing
                        </button>
                    </div>
                    
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle mr-1"></i> This QR code refreshes automatically every 30 seconds for security
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Log Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Real-time Attendance</h6>
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

<!-- QR Code library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<!-- Sweet Alert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<script>
// Global variables
let autoRefreshInterval;
let qrActive = true;
let attendanceLog = [];
const subject_id = <?php echo $subject_id; ?>;
const schedule_id = <?php echo $current_schedule ? $current_schedule['id'] : 'null'; ?>;
const faculty_id = <?php echo $_SESSION['user_id']; ?>;

// Initialize on page load
$(document).ready(function() {
    generateQRCode();
    startAutoRefresh();
    checkAttendance();
});

// Generate the QR code with relevant data
function generateQRCode() {
    // Clear previous QR code
    $('#qrcode').empty();
    
    // Get current timestamp
    const now = new Date();
    const timestamp = now.toISOString();
    
    // Update last refresh time
    $('#last-refresh-time').text(now.toLocaleTimeString());
    
    // Create QR code data with relevant information
    const qrData = JSON.stringify({
        subject_id: subject_id,
        subject_code: '<?php echo addslashes($subject['code']); ?>',
        subject_name: '<?php echo addslashes($subject['name']); ?>',
        faculty_id: faculty_id,
        timestamp: timestamp,
        schedule_id: schedule_id,
        expires: new Date(now.getTime() + 30000).toISOString(), // 30 seconds expiry
        type: 'attendance'
    });
    
    // Generate QR code
    const qr = qrcode(0, 'M');
    qr.addData(qrData);
    qr.make();
    
    // Display QR code
    const qrImage = qr.createImgTag(5);
    $('#qrcode').html(qrImage);
}

// Automatically refresh the QR code
function startAutoRefresh() {
    // Clear any existing interval
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    // Set new interval for 30 seconds
    autoRefreshInterval = setInterval(() => {
        if (qrActive) {
            generateQRCode();
        }
    }, 30000); // 30 seconds
}

// Toggle QR code sharing
$('#toggle-qr-btn').on('click', function() {
    if (qrActive) {
        // Pause QR sharing
        qrActive = false;
        $('#qrcode').addClass('opacity-50').prepend('<div class="position-absolute bg-warning p-2 rounded">QR PAUSED</div>');
        $('#qr-status span.badge').removeClass('badge-success').addClass('badge-warning').text('QR Code Paused');
        $(this).removeClass('btn-warning').addClass('btn-success');
        $(this).html('<i class="fas fa-play-circle"></i> Resume Sharing');
        $('#qrStatusBtn').removeClass('btn-success').addClass('btn-warning').html('<i class="fas fa-pause-circle mr-1"></i> QR Paused');
    } else {
        // Resume QR sharing
        qrActive = true;
        $('#qrcode').removeClass('opacity-50').find('div.position-absolute').remove();
        $('#qr-status span.badge').removeClass('badge-warning').addClass('badge-success').text('QR Code Active');
        $(this).removeClass('btn-success').addClass('btn-warning');
        $(this).html('<i class="fas fa-pause-circle"></i> Pause Sharing');
        $('#qrStatusBtn').removeClass('btn-warning').addClass('btn-success').html('<i class="fas fa-check-circle mr-1"></i> QR Active');
        
        // Generate a new QR code immediately
        generateQRCode();
    }
});

// Refresh QR code manually
$('#refresh-qr-btn').on('click', function() {
    if (!qrActive) {
        Swal.fire({
            title: 'QR Code is Paused',
            text: 'Please resume sharing first',
            icon: 'warning',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        });
        return;
    }
    
    // Generate a new QR code
    generateQRCode();
    
    // Show success notification
    Swal.fire({
        title: 'QR Code Refreshed',
        text: 'A new QR code has been generated',
        icon: 'success',
        timer: 1500,
        timerProgressBar: true,
        showConfirmButton: false
    });
    
    // Reset the auto-refresh timer
    startAutoRefresh();
});

// Check for new attendance records periodically
function checkAttendance() {
    // Poll for new attendance data every 5 seconds
    setInterval(() => {
        $.ajax({
            url: 'get_attendance_data.php',
            type: 'GET',
            data: {
                subject_id: subject_id,
                timestamp: new Date().toISOString()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    updateAttendanceLog(response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error checking attendance:", textStatus, errorThrown);
            }
        });
    }, 5000); // Check every 5 seconds
}

// Update the attendance log with new records
function updateAttendanceLog(newRecords) {
    if (!newRecords || newRecords.length === 0) return;
    
    // Filter to only get records we don't already have
    const newItems = newRecords.filter(record => 
        !attendanceLog.some(existing => existing.user_id === record.user_id)
    );
    
    if (newItems.length === 0) return;
    
    // Add new records to our tracking array
    attendanceLog = [...attendanceLog, ...newItems];
    
    // Remove empty log message if present
    $('#empty-log-row').remove();
    
    // Add rows to table
    newItems.forEach(record => {
        const newRow = `
            <tr>
                <td>${record.student_id || 'N/A'}</td>
                <td>${record.name}</td>
                <td>${new Date(record.timestamp).toLocaleTimeString()}</td>
                <td><span class="badge badge-success">Present</span></td>
            </tr>
        `;
        $('#attendance-log').prepend(newRow);
    });
    
    // Update count
    $('#attendance-count').text(attendanceLog.length);
    
    // Enable export button if we have records
    if (attendanceLog.length > 0) {
        $('#export-csv-btn').prop('disabled', false);
    }
    
    // Play a notification sound for new attendance
    const audio = new Audio('assets/sounds/attendance-alert.mp3');
    audio.play();
}

// Export to CSV handler
$('#export-csv-btn').on('click', function() {
    if (attendanceLog.length === 0) return;
    
    // Create CSV content
    let csvContent = "Student ID,Name,Timestamp,Status\n";
    
    attendanceLog.forEach(record => {
        csvContent += `${record.student_id || 'N/A'},${record.name},${record.timestamp},Present\n`;
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

// Handle page unload to stop QR sharing
$(window).on('beforeunload', function() {
    // Optionally notify server that QR code is no longer active
    $.ajax({
        url: 'stop_qr_session.php',
        type: 'POST',
        data: {
            subject_id: subject_id
        },
        async: false
    });
});
</script>

</body>
</html>
<?php
$conn->close();
?>