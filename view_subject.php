<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';

require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: subjects.php");
    exit();
}

$subjectId = $_GET['id'];
$sql = "SELECT * FROM subjects WHERE id = $subjectId";
$result = $conn->query($sql);
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <?php
    if ($result && $result->num_rows > 0) {
        $subject = $result->fetch_assoc();
        // Restructure the header to include the buttons
        echo '<div class="d-flex justify-content-between align-items-center mb-4">';
        echo '<div>';
        echo "<h1 class='mb-1'>{$subject['name']} ({$subject['code']})</h1>";
        echo '</div>';
        echo '<div>';
        echo '<button type="button" class="btn btn-info mr-2" data-toggle="modal" data-target="#qrCodeModal">View QR Code</button>';
        echo '<button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#scanQrCodeModal">Scan QR Code</button>';
        echo '</div>';
        echo '</div>';
    } else {
        echo "Subject not found.";
    }
    ?>
    
    <!-- Schedules Section (Using card layout) -->
    <div class="card shadow mb-4 mt-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Schedules</h6>
            <div class="d-flex align-items-center">
                <div class="mr-3 px-3 py-2 border rounded bg-light">
                    <span class="font-weight-bold">Join Code:</span> 
                    <span class="px-2 py-1"><?= $subject['joincode'] ?></span>
                </div>
                <a href="#" class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#addScheduleModal">
                    <i class="fas fa-plus mr-1"></i> Add Schedule
                </a>
                <a href="#" class="btn btn-primary btn-sm">
                    <i class="fas fa-file-pdf mr-1"></i> Export as PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php
            $sqlSchedules = "SELECT * FROM schedules WHERE subject_id = $subjectId";
            $resultSchedules = $conn->query($sqlSchedules);
            ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="schedulesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Room</th>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultSchedules && $resultSchedules->num_rows > 0): ?>
                            <?php while ($rowSch = $resultSchedules->fetch_assoc()): ?>
                            <tr>
                                <td><?= $rowSch['id'] ?></td>
                                <td><?= $rowSch['room'] ?></td>
                                <td><?= $rowSch['day'] ?></td>
                                <td><?= $rowSch['start_time'] ?></td>
                                <td><?= $rowSch['end_time'] ?></td>
                                <td class="text-center">
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle btn btn-sm btn-secondary" href="#" role="button" id="dropdownMenuLink<?= $rowSch['id'] ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink<?= $rowSch['id'] ?>">
                                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); editSchedule(<?= $rowSch['id'] ?>)">
                                                <i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i>Edit
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); deleteSchedule(<?= $rowSch['id'] ?>)">
                                                <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>Delete
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No schedules found for this subject.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Enrolled Users Section (Using card layout) -->
    <div class="card shadow mb-4 mt-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Enrolled Users</h6>
        </div>
        <div class="card-body">
            <?php
            $sqlUsers = "SELECT u.* FROM users u 
                         JOIN usersubjects us ON u.id = us.user_id 
                         WHERE us.subject_id = $subjectId AND u.usertype = 'Student'";
            $resultUsers = $conn->query($sqlUsers);
            ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultUsers && $resultUsers->num_rows > 0): ?>
                            <?php while ($rowUser = $resultUsers->fetch_assoc()): ?>
                            <tr>
                                <?php $name = $rowUser['firstname'] . ' ' . $rowUser['middle_init'] . ' ' . $rowUser['lastname']; ?>
                                <td><?= $name ?></td>
                                <td><?= $rowUser['email'] ?></td>
                                <td class="text-center">
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle btn btn-sm btn-secondary" href="#" role="button" id="dropdownMenuLink<?= $rowUser['id'] ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink<?= $rowUser['id'] ?>">
                                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); viewProfile(<?= $rowUser['id'] ?>)">
                                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>View Profile
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); removeUser(<?= $rowUser['id'] ?>)">
                                                <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>Remove
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No users enrolled in this subject.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<?php include 'includes/footer.php'; ?>

<!-- QR Code Modal -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" role="dialog" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrCodeModalLabel">QR Code for Subject</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                <div class="mt-3">
                    <p><strong>Subject:</strong> <?= $subject['name'] ?> (<?= $subject['code'] ?>)</p>
                    <p><strong>Generated:</strong> <span id="qr-timestamp"></span></p>
                </div>
                <button type="button" id="refreshQrBtn" class="btn btn-primary mt-2">
                    <i class="fas fa-sync-alt"></i> Refresh QR Code
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scan QR Code Modal -->
<div class="modal fade" id="scanQrCodeModal" tabindex="-1" role="dialog" aria-labelledby="scanQrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scanQrCodeModalLabel">Scan QR Code</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div id="scanner-container" style="width: 100%; max-width: 500px; margin: 0 auto;">
                        <div id="reader" style="width: 100%; min-height: 300px; border-radius: 10px; overflow: hidden;"></div>
                    </div>
                    <div id="scanner-status" class="mt-2">
                        <p class="text-muted">Initializing camera...</p>
                    </div>
                    <div id="scanner-result" class="mt-3 d-none">
                        <div class="alert alert-success">
                            <h5>QR Code Scanned Successfully</h5>
                            <p id="scanned-data"></p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-2">
                    <button id="toggle-camera-btn" class="btn btn-primary">
                        <i class="fas fa-video"></i> Toggle Camera
                    </button>
                    <button id="change-camera-btn" class="btn btn-secondary ml-2">
                        <i class="fas fa-exchange-alt"></i> Change Camera
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" role="dialog" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addScheduleForm" method="post" action="add_schedule.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">Add New Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria.hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
                    <div class="form-group">
                        <label for="room">Room</label>
                        <input type="text" class="form-control" id="room" name="room" required>
                    </div>
                    <div class="form-group">
                        <label for="day">Day</label>
                        <select class="form-control" id="day" name="day" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="start_time">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="end_time">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-labelledby="editScheduleModalLabel" aria.hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editScheduleForm" method="post" action="update_schedule.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria.hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
                    <div class="form-group">
                        <label for="edit_room">Room</label>
                        <input type="text" class="form-control" id="edit_room" name="room" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_day">Day</label>
                        <select class="form-control" id="edit_day" name="day" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_start_time">Start Time</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_end_time">End Time</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<!-- Sweet Alert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<!-- QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
$(document).ready(function() {
    // Handle schedule form submission via AJAX
    $('#addScheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate that end time is after start time
        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();
        
        if (startTime >= endTime) {
            Swal.fire('Error', 'End time must be after start time', 'error');
            return;
        }
        
        // Submit the form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Schedule Added Successfully!',
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload page to see the new schedule
                        location.reload();
                    });
                } else if (response.status === 'conflict') {
                    // Format the conflicts for display
                    let conflictsHtml = '<ul class="text-left">';
                    response.conflicts.forEach(conflict => {
                        conflictsHtml += `<li><strong>${conflict.subject_name} (${conflict.subject_code})</strong>: 
                                          ${conflict.day} ${conflict.start_time} - ${conflict.end_time} in ${conflict.room}</li>`;
                    });
                    conflictsHtml += '</ul>';
                    
                    Swal.fire({
                        title: 'Schedule Conflict Detected',
                        html: `<p>The schedule you're trying to add conflicts with:</p>${conflictsHtml}`,
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to add schedule', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'An error occurred while processing your request', 'error');
            }
        });
    });
    
    // Handle edit schedule form submission via AJAX
    $('#editScheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate that end time is after start time
        const startTime = $('#edit_start_time').val();
        const endTime = $('#edit_end_time').val();
        
        if (startTime >= endTime) {
            Swal.fire('Error', 'End time must be after start time', 'error');
            return;
        }
        
        // Submit the form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Schedule Updated Successfully!',
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload page to see the updated schedule
                        location.reload();
                    });
                } else if (response.status === 'conflict') {
                    // Format the conflicts for display
                    let conflictsHtml = '<ul class="text-left">';
                    response.conflicts.forEach(conflict => {
                        conflictsHtml += `<li><strong>${conflict.subject_name} (${conflict.subject_code})</strong>: 
                                          ${conflict.day} ${conflict.start_time} - ${conflict.end_time} in ${conflict.room}</li>`;
                    });
                    conflictsHtml += '</ul>';
                    
                    Swal.fire({
                        title: 'Schedule Conflict Detected',
                        html: `<p>The schedule you're trying to update conflicts with:</p>${conflictsHtml}`,
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to update schedule', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'An error occurred while processing your request', 'error');
            }
        });
    });
    
    // Initialize QR code when modal is opened
    $('#qrCodeModal').on('show.bs.modal', function() {
        generateQRCode();
    });
    
    // Refresh QR code when button is clicked
    $('#refreshQrBtn').on('click', function() {
        generateQRCode();
    });
});

// Function to generate QR code with subject info and timestamp
function generateQRCode() {
    // Clear previous QR code
    $('#qrcode').empty();
    
    // Get current timestamp
    const now = new Date();
    const timestamp = now.toISOString();
    const readableTimestamp = now.toLocaleString();
    
    // Display readable timestamp
    $('#qr-timestamp').text(readableTimestamp);
    
    // Create QR code data with subject info and timestamp
    const qrData = JSON.stringify({
        subject_code: '<?= $subject['code'] ?>',
        subject_name: '<?= $subject['name'] ?>',
        subject_id: <?= $subjectId ?>,
        timestamp: timestamp
    });
    
    // Generate QR code
    const qr = qrcode(0, 'M');
    qr.addData(qrData);
    qr.make();
    
    // Display QR code
    const qrImage = qr.createImgTag(5);
    $('#qrcode').html(qrImage);
}

// Function to edit schedule
function editSchedule(scheduleId) {
    // Fetch schedule data via AJAX
    $.ajax({
        url: 'get_schedule.php',
        type: 'POST',
        data: {
            schedule_id: scheduleId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const schedule = response.data;
                // Populate form fields
                $('#edit_schedule_id').val(schedule.id);
                $('#edit_room').val(schedule.room);
                $('#edit_day').val(schedule.day);
                $('#edit_start_time').val(schedule.start_time);
                $('#edit_end_time').val(schedule.end_time);
                
                // Show the modal
                $('#editScheduleModal').modal('show');
            } else {
                Swal.fire('Error', response.message || 'Failed to load schedule data', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'An error occurred while processing your request', 'error');
        }
    });
}

// Function to delete schedule
function deleteSchedule(scheduleId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send delete request via AJAX
            $.ajax({
                url: 'delete_schedule.php',
                type: 'POST',
                data: {
                    schedule_id: scheduleId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Schedule has been deleted.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to delete schedule', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while processing your request', 'error');
                }
            });
        }
    });
}

// Function to view user profile
function viewProfile(userId) {
    // For now just show an alert (functionality to be implemented)
    Swal.fire({
        title: 'View Profile',
        text: 'View profile functionality will be implemented soon.',
        icon: 'info'
    });
}

// Function to remove user
function removeUser(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send remove request via AJAX
            $.ajax({
                url: 'remove_user.php',
                type: 'POST',
                data: {
                    user_id: userId,
                    subject_id: <?= $subjectId ?>
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Removed!',
                            text: 'User has been removed.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to remove user', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while processing your request', 'error');
                }
            });
        }
    });
}

// Global variables for scanner
let html5QrCode = null;
let cameraId = null;
let cameraList = [];
let scanning = false;

// Handle QR scanner modal events
$('#scanQrCodeModal').on('show.bs.modal', function() {
    initializeScanner();
});

$('#scanQrCodeModal').on('hidden.bs.modal', function() {
    stopScanner();
});

// Replace the initializeScanner() function with this improved version
function initializeScanner() {
    // Reset status elements
    $('#scanner-status').html('<p class="text-muted">Requesting camera permission...</p>');
    $('#scanner-result').addClass('d-none');
    
    // Create instance of scanner with the correct element ID
    html5QrCode = new Html5Qrcode("reader");
    
    // Get list of cameras
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            cameraList = devices;
            cameraId = devices[0].id;
            startScanner();
        } else {
            $('#scanner-status').html('<p class="text-danger">No cameras found!</p>');
        }
    }).catch(err => {
        $('#scanner-status').html(`<p class="text-danger">Error getting cameras: ${err}</p>`);
        console.error("Error getting cameras", err);
    });
}

// Update the startScanner function with better configuration
function startScanner() {
    if (!html5QrCode) return;
    
    $('#scanner-status').html('<p class="text-primary">Starting camera...</p>');
    scanning = true;
    
    // Improved camera configuration
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
        onScanFailure
    )
    .then(() => {
        $('#scanner-status').html('<p class="text-success">Camera active. Scanning for QR code...</p>');
        // Update button text
        $('#toggle-camera-btn').html('<i class="fas fa-video-slash"></i> Stop Camera');
    })
    .catch(err => {
        scanning = false;
        $('#scanner-status').html(`<p class="text-danger">Error starting camera: ${err}</p>`);
        $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
        console.error("Error starting camera", err);
    });
}

// Stop the scanner
function stopScanner() {
    if (html5QrCode && scanning) {
        html5QrCode.stop().then(() => {
            scanning = false;
            console.log('Scanner stopped');
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }
}

// Handle successful scan
function onScanSuccess(decodedText, decodedResult) {
    // Stop scanning after successful scan
    stopScanner();
    
    try {
        // Parse the QR data (assuming JSON format)
        const data = JSON.parse(decodedText);
        
        // Show success message
        $('#scanner-result').removeClass('d-none');
        $('#scanned-data').html(`
            <strong>Subject:</strong> ${data.subject_name} (${data.subject_code})<br>
            <strong>Time:</strong> ${new Date(data.timestamp).toLocaleString()}<br>
            <button class="btn btn-success mt-2" id="record-attendance-btn">Record Attendance</button>
        `);
        
        // Handle attendance recording
        $('#record-attendance-btn').on('click', function() {
            // Add code here to record the attendance via AJAX
            Swal.fire({
                title: 'Attendance Recorded',
                text: 'Student attendance has been recorded successfully',
                icon: 'success'
            });
        });
        
    } catch (error) {
        $('#scanner-result').removeClass('d-none')
            .find('.alert')
            .removeClass('alert-success')
            .addClass('alert-danger')
            .html(`<h5>Invalid QR Code</h5><p>The scanned QR code is not valid for this system.</p>`);
    }
}

// Handle scan failures/errors
function onScanFailure(error) {
    // We don't need to show errors during normal scanning
    console.log('QR code scanning ongoing');
}

// Button handlers
$('#toggle-camera-btn').on('click', function() {
    if (scanning) {
        stopScanner();
        $('#scanner-status').html('<p class="text-warning">Camera stopped</p>');
        $(this).html('<i class="fas fa-video"></i> Start Camera');
    } else {
        startScanner();
        $(this).html('<i class="fas fa-video-slash"></i> Stop Camera');
    }
});

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
</script>

</body>
</html>
<?php
$conn->close();
?>