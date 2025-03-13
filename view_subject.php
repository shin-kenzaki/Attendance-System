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
$sql = "SELECT s.*, CONCAT(u.lastname, ', ', u.firstname) AS faculty_name 
        FROM subjects s 
        LEFT JOIN users u ON s.faculty_id = u.id 
        WHERE s.id = $subjectId";
$result = $conn->query($sql);

// Get enrollment count
$enrollmentSql = "SELECT COUNT(*) as total FROM usersubjects WHERE subject_id = $subjectId";
$enrollmentResult = $conn->query($enrollmentSql);
$enrollmentCount = 0;
if ($enrollmentResult && $enrollmentResult->num_rows > 0) {
    $enrollmentCount = $enrollmentResult->fetch_assoc()['total'];
}

// Get attendance statistics
$attendanceSql = "SELECT 
                    COUNT(*) as total_records,
                    COUNT(DISTINCT user_id) as total_students,
                    COUNT(DISTINCT DATE(time_in)) as total_days,
                    MAX(time_in) as last_attendance
                FROM attendances 
                WHERE subject_id = $subjectId";
$attendanceResult = $conn->query($attendanceSql);
$attendanceStats = $attendanceResult->fetch_assoc();
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <?php
    if ($result && $result->num_rows > 0) {
        $subject = $result->fetch_assoc();
        $statusBadge = ($subject["status"] == 1) ? 
            '<span class="badge badge-success">Active</span>' : 
            '<span class="badge badge-danger">Inactive</span>';
        
        // Subject Header with Card
        echo '<div class="card shadow mb-4">';
        echo '<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">';
        echo '<h6 class="m-0 font-weight-bold text-primary">Subject Information</h6>';
        echo '<div>';
        echo '<button type="button" class="btn btn-info btn-sm mr-2" data-toggle="modal" data-target="#qrCodeModal">
                <i class="fas fa-qrcode mr-1"></i> Generate QR Code
              </button>';
        echo '<button type="button" class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#scanQrCodeModal">
                <i class="fas fa-camera mr-1"></i> Scan QR Code
              </button>';
        echo '</div>';
        echo '</div>';
        
        // Subject content
        echo '<div class="card-body">';
        echo '<div class="row">';
        
        // Left column - Basic info
        echo '<div class="col-md-8">';
        echo "<h2 class='h3 mb-2 text-gray-800'>{$subject['name']} <small class='text-muted'>({$subject['code']})</small></h2>";
        
        echo '<div class="row mt-4">';
        echo '<div class="col-md-6">';
        echo '<table class="table table-borderless">';
        echo '<tr><th width="120">Faculty:</th><td>' . ($subject['faculty_name'] ?: '<span class="text-muted">Not assigned</span>') . '</td></tr>';
        echo '<tr><th>Status:</th><td>' . $statusBadge . '</td></tr>';
        echo '<tr><th>Enrollment:</th><td><span class="badge badge-info">' . $enrollmentCount . ' students</span></td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="col-md-6">';
        echo '<div class="card bg-light mb-3">';
        echo '<div class="card-header d-flex justify-content-between align-items-center">
                <span>Join Code</span>
                <a href="#" onclick="changeJoinCode(' . $subject['id'] . ', \'' . $subject['code'] . '\'); return false;" 
                   class="btn btn-sm btn-outline-secondary" title="Regenerate Join Code">
                   <i class="fas fa-sync-alt"></i>
                </a>
              </div>';
        echo '<div class="card-body text-center">';
        echo '<h3 class="card-title font-weight-bold mb-0">' . $subject['joincode'] . '</h3>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Right column - Attendance statistics
        echo '<div class="col-md-4 border-left">';
        echo '<h5 class="mb-3">Attendance Statistics</h5>';
        
        echo '<div class="row no-gutters">';
        
        // Total attendance records
        echo '<div class="col-6 mb-3">';
        echo '<div class="text-center">';
        echo '<h4 class="font-weight-bold text-primary mb-0">' . number_format($attendanceStats['total_records']) . '</h4>';
        echo '<small>Total Records</small>';
        echo '</div>';
        echo '</div>';
        
        // Unique students
        echo '<div class="col-6 mb-3">';
        echo '<div class="text-center">';
        echo '<h4 class="font-weight-bold text-info mb-0">' . number_format($attendanceStats['total_students']) . '</h4>';
        echo '<small>Active Students</small>';
        echo '</div>';
        echo '</div>';
        
        // Attendance days
        echo '<div class="col-6">';
        echo '<div class="text-center">';
        echo '<h4 class="font-weight-bold text-success mb-0">' . number_format($attendanceStats['total_days']) . '</h4>';
        echo '<small>Class Days</small>';
        echo '</div>';
        echo '</div>';
        
        // Last attendance
        echo '<div class="col-6">';
        echo '<div class="text-center">';
        if ($attendanceStats['last_attendance']) {
            $lastAttendance = date('M d, Y', strtotime($attendanceStats['last_attendance']));
            echo '<h4 class="font-weight-bold text-dark mb-0">' . $lastAttendance . '</h4>';
        } else {
            echo '<h4 class="font-weight-bold text-muted mb-0">N/A</h4>';
        }
        echo '<small>Last Attendance</small>';
        echo '</div>';
        echo '</div>';
        
        // Action buttons
        echo '<div class="col-12 mt-4 text-center">';
        echo '<a href="attendance_report.php?subject_id=' . $subjectId . '" class="btn btn-sm btn-primary">
                <i class="fas fa-chart-bar mr-1"></i> View Full Report
              </a>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // End row
        echo '</div>'; // End card-body
        echo '</div>'; // End card
    } else {
        echo '<div class="alert alert-danger">Subject not found.</div>';
    }
    ?>
    
    <!-- Schedules Section (Using card layout) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Class Schedules</h6>
            <div class="d-flex align-items-center">
                <a href="#" class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#addScheduleModal">
                    <i class="fas fa-plus mr-1"></i> Add Schedule
                </a>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle btn btn-sm btn-light" href="#" role="button" id="exportDropdown" data-toggle="dropdown">
                        <i class="fas fa-download mr-1"></i> Export
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="exportDropdown">
                        <a class="dropdown-item" href="#"><i class="fas fa-file-csv fa-sm fa-fw mr-2 text-gray-400"></i> CSV</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-excel fa-sm fa-fw mr-2 text-gray-400"></i> Excel</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-pdf fa-sm fa-fw mr-2 text-gray-400"></i> PDF</a>
                    </div>
                </div>
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
                            <th width="10%">Day</th>
                            <th width="20%">Room</th>
                            <th width="20%">Start Time</th>
                            <th width="20%">End Time</th>
                            <th width="20%">Duration</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultSchedules && $resultSchedules->num_rows > 0): ?>
                            <?php while ($rowSch = $resultSchedules->fetch_assoc()): 
                                // Calculate duration
                                $start = new DateTime($rowSch['start_time']);
                                $end = new DateTime($rowSch['end_time']);
                                $duration = $start->diff($end);
                                $durationStr = $duration->format('%h hrs %i mins');
                            ?>
                            <tr>
                                <td class="font-weight-bold"><?= $rowSch['day'] ?></td>
                                <td><?= $rowSch['room'] ?></td>
                                <td><?= date('g:i A', strtotime($rowSch['start_time'])) ?></td>
                                <td><?= date('g:i A', strtotime($rowSch['end_time'])) ?></td>
                                <td><?= $durationStr ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-secondary mr-1" onclick="editSchedule(<?= $rowSch['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSchedule(<?= $rowSch['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
            
            <?php if ($resultSchedules && $resultSchedules->num_rows > 0): ?>
            <!-- Weekly schedule visualization -->
            <div class="mt-4">
                <h6 class="font-weight-bold">Weekly Schedule</h6>
                <div class="table-responsive">
                    <table class="table table-bordered bg-white">
                        <thead>
                            <tr>
                                <th style="width: 12.5%">Time</th>
                                <th style="width: 12.5%">Monday</th>
                                <th style="width: 12.5%">Tuesday</th>
                                <th style="width: 12.5%">Wednesday</th>
                                <th style="width: 12.5%">Thursday</th>
                                <th style="width: 12.5%">Friday</th>
                                <th style="width: 12.5%">Saturday</th>
                                <th style="width: 12.5%">Sunday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Create time slots from 7 AM to 10 PM in 1-hour increments
                            for ($hour = 7; $hour <= 22; $hour++) {
                                $timeSlot = sprintf('%02d:00', $hour);
                                $displayTime = date('g:i A', strtotime($timeSlot));
                                
                                echo "<tr>";
                                echo "<td class='font-weight-bold'>{$displayTime}</td>";
                                
                                // Reset schedule result pointer
                                $resultSchedules->data_seek(0);
                                
                                // Loop through days
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($days as $day) {
                                    $cellContent = '';
                                    
                                    // Check if there's a schedule for this day and time slot
                                    while ($schedule = $resultSchedules->fetch_assoc()) {
                                        if ($schedule['day'] == $day) {
                                            $startHour = (int)substr($schedule['start_time'], 0, 2);
                                            $endHour = (int)substr($schedule['end_time'], 0, 2);
                                            
                                            if ($hour >= $startHour && $hour < $endHour) {
                                                $cellContent = "<div class='p-1 bg-primary text-white rounded'>";
                                                $cellContent .= "<small>{$schedule['room']}</small>";
                                                $cellContent .= "</div>";
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Reset for next iteration
                                    $resultSchedules->data_seek(0);
                                    
                                    echo "<td>{$cellContent}</td>";
                                }
                                
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Attendance -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Recent Attendance</h6>
            <a href="attendance_report.php?subject_id=<?= $subjectId ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-list mr-1"></i> View All
            </a>
        </div>
        <div class="card-body">
            <?php
            $recentAttendanceSql = "
                SELECT a.id, a.time_in as timestamp, u.id as student_id, u.firstname, u.lastname, s.day, s.room
                FROM attendances a 
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN schedules s ON a.subject_id = s.subject_id
                WHERE a.subject_id = $subjectId
                ORDER BY a.time_in DESC
                LIMIT 10
            ";
            $recentAttendanceResult = $conn->query($recentAttendanceSql);
            ?>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Date & Time</th>
                            <th>Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentAttendanceResult && $recentAttendanceResult->num_rows > 0): ?>
                            <?php while ($attendance = $recentAttendanceResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= $attendance['lastname'] . ', ' . $attendance['firstname'] ?></td>
                                <td><?= date('M d, Y g:i A', strtotime($attendance['timestamp'])) ?></td>
                                <td>
                                    <?php if ($attendance['day'] && $attendance['room']): ?>
                                        <?= $attendance['day'] ?> (<?= $attendance['room'] ?>)
                                    <?php else: ?>
                                        <span class="text-muted">General attendance</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No attendance records found.</td>
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
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrCodeModalLabel">QR Code for Subject</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <select id="schedule-select" class="form-control mb-2">
                        <option value="">-- Select Schedule (Optional) --</option>
                        <?php 
                        $scheduleQuery = "SELECT * FROM schedules WHERE subject_id = $subjectId";
                        $scheduleResult = $conn->query($scheduleQuery);
                        if ($scheduleResult && $scheduleResult->num_rows > 0) {
                            while ($schedule = $scheduleResult->fetch_assoc()) {
                                echo "<option value='{$schedule['id']}'>{$schedule['day']} ({$schedule['start_time']} - {$schedule['end_time']}) - Room {$schedule['room']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                <div class="mt-3">
                    <p><strong>Subject:</strong> <?= $subject['name'] ?> (<?= $subject['code'] ?>)</p>
                    <p><strong>Generated:</strong> <span id="qr-timestamp"></span></p>
                    <p><strong>Expires:</strong> <span id="qr-expiry"></span></p>
                    <!-- Add countdown timer display -->
                    <div class="progress" style="height: 5px;">
                        <div id="qr-refresh-progress" class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="mt-1 mb-0 small text-muted">Refreshes in <span id="qr-countdown">30</span> seconds</p>
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
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scanQrCodeModalLabel">Scan QR Code</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div id="scanner-container" style="width: 100%; max-width: 500px; margin: 0 auto; position: relative;">
                        <div id="reader" style="width: 100%; min-height: 300px; border-radius: 10px; overflow: hidden; transform: scaleX(-1);"></div>
                        
                        <!-- Camera switch loading overlay -->
                        <div id="camera-loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; border-radius: 10px;">
                            <div class="text-center text-white">
                                <div class="spinner-border text-light mb-2" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p>Switching camera...</p>
                            </div>
                        </div>
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
<div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria.hidden="true">
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
// Add function to change join code from the subject view
function changeJoinCode(subjectId, subjectCode) {
    const newJoinCode = generateRandomJoinCode(6);
    
    Swal.fire({
        title: 'Change Join Code?',
        html: '<p>Are you sure you want to change the join code for subject <strong>' + subjectCode + '</strong>?</p>' +
              '<p>The new join code will be: <span class="h3">' + newJoinCode + '</span></p>' +
              '<p class="text-warning">Note: Students using the old code will no longer be able to join.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to update the join code
            $.ajax({
                url: 'change_joincode.php',
                type: 'POST',
                data: {
                    subject_id: subjectId,
                    new_joincode: newJoinCode
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Join Code Changed!',
                            html: '<p>The join code has been updated successfully:</p>' +
                                  '<p><strong>Subject:</strong> ' + data.subject_code + '</p>' +
                                  '<p><strong>New Join Code:</strong> <span class="h3">' + newJoinCode + '</span></p>' +
                                  '<p>Please share this new code with students.</p>',
                            icon: 'success',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload page to see the updated join code
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', 'Failed to update join code.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to update join code.', 'error');
                }
            });
        }
    });
}

// Function to generate random join code
function generateRandomJoinCode(length) {
    const charset = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // Removed confusing characters like I, O, 0, 1
    let code = "";
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * charset.length);
        code += charset[randomIndex];
    }
    return code;
}

$(document).ready(function() {
    // Initialize DataTable for schedules
    $('#schedulesTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        searching: false,
        ordering: true,
        info: false,
        responsive: true,
        language: {
            paginate: {
                previous: '<i class="fas fa-chevron-left"></i>',
                next: '<i class="fas fa-chevron-right"></i>'
            }
        }
    });
    
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
        startQrAutoRefresh(); // Start automatic refresh when modal is shown
    });
    
    // Stop auto-refresh when modal is closed
    $('#qrCodeModal').on('hidden.bs.modal', function() {
        stopQrAutoRefresh(); // Stop automatic refresh when modal is hidden
    });
    
    // Refresh QR code when button is clicked
    $('#refreshQrBtn').on('click', function() {
        generateQRCode();
        resetQrAutoRefresh(); // Reset the timer after manual refresh
    });
});

// Global variables for QR auto-refresh
let qrAutoRefreshTimer;
let qrCountdownInterval;
let qrTimeRemaining = 30;

// Function to generate QR code with subject info and timestamp
function generateQRCode() {
    // Clear previous QR code
    $('#qrcode').empty();
    
    // Get current timestamp
    const now = new Date();
    const timestamp = now.toISOString();
    const readableTimestamp = now.toLocaleString();
    
    // Set expiry time (30 seconds from now)
    const expiryTime = new Date(now.getTime() + 30000);
    const readableExpiry = expiryTime.toLocaleString();
    
    // Display readable timestamps
    $('#qr-timestamp').text(readableTimestamp);
    $('#qr-expiry').text(readableExpiry);
    
    // Get selected schedule if any
    const scheduleId = $('#schedule-select').val() ? parseInt($('#schedule-select').val()) : null;
    
    // Create QR code data with subject info and timestamp
    const qrData = JSON.stringify({
        subject_id: <?= $subjectId ?>,
        subject_code: '<?= addslashes($subject['code']) ?>',
        subject_name: '<?= addslashes($subject['name']) ?>',
        faculty_id: <?= $_SESSION['user_id'] ?>,
        timestamp: timestamp,
        expires: expiryTime.toISOString(),
        schedule_id: scheduleId,
        type: 'attendance'
    });
    
    // Generate QR code
    const qr = qrcode(0, 'M');
    qr.addData(qrData);
    qr.make();
    
    // Display QR code
    const qrImage = qr.createImgTag(5);
    $('#qrcode').html(qrImage);
    
    // Reset countdown timer
    qrTimeRemaining = 30;
    $('#qr-countdown').text(qrTimeRemaining);
    $('#qr-refresh-progress').css('width', '100%');
}

// Start automatic QR code refresh
function startQrAutoRefresh() {
    // Clear any existing timers
    stopQrAutoRefresh();
    
    // Set up the main refresh timer (30 seconds)
    qrAutoRefreshTimer = setTimeout(function() {
        generateQRCode(); // Generate new QR code
        startQrAutoRefresh(); // Restart the timer
    }, 30000);
    
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
}

// Stop automatic QR refresh
function stopQrAutoRefresh() {
    if (qrAutoRefreshTimer) {
        clearTimeout(qrAutoRefreshTimer);
        qrAutoRefreshTimer = null;
    }
    
    if (qrCountdownInterval) {
        clearInterval(qrCountdownInterval);
        qrCountdownInterval = null;
    }
}

// Reset the auto-refresh timer (used after manual refresh)
function resetQrAutoRefresh() {
    stopQrAutoRefresh();
    startQrAutoRefresh();
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
let isFrontCamera = true; // Track if front camera is in use

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
    
    // Improved camera configuration for better scanning
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
        // Check camera type and apply appropriate styling
        checkCameraType();
    })
    .catch(err => {
        scanning = false;
        $('#scanner-status').html(`<p class="text-danger">Error starting camera: ${err}</p>`);
        $('#toggle-camera-btn').html('<i class="fas fa-video"></i> Start Camera');
        console.error("Error starting camera", err);
    });
}

// Function to check camera type and apply appropriate styling
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
        
        // Validate QR code format
        if (data.type !== 'attendance' || !data.subject_id) {
            throw new Error('Invalid QR code format');
        }
        
        // Check if QR code has expired
        const expiryTime = new Date(data.expires);
        if (expiryTime < new Date()) {
            throw new Error('QR code has expired. Please ask for a new code.');
        }
        
        // Show success message
        $('#scanner-result').removeClass('d-none');
        $('#scanned-data').html(`
            <strong>Subject:</strong> ${data.subject_name} (${data.subject_code})<br>
            <strong>Time:</strong> ${new Date(data.timestamp).toLocaleString()}<br>
            <button class="btn btn-success mt-2" id="record-attendance-btn">Record Attendance</button>
        `);
        
        // Handle attendance recording
        $('#record-attendance-btn').on('click', function() {
            const attendanceData = {
                subject_id: data.subject_id,
                schedule_id: data.schedule_id || null,
                user_id: <?= $_SESSION['user_id'] ?>,
                time_in: new Date().toISOString()
            };
            
            $.ajax({
                url: 'process_attendance.php',
                type: 'POST',
                data: attendanceData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Attendance Recorded',
                            text: 'Student attendance has been recorded successfully',
                            icon: 'success'
                        }).then(() => {
                            $('#scanQrCodeModal').modal('hide');
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to record attendance', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while processing your request', 'error');
                }
            });
        });
        
    } catch (error) {
        $('#scanner-result').removeClass('d-none')
            .find('.alert')
            .removeClass('alert-success')
            .addClass('alert-danger')
            .html(`<h5>Invalid QR Code</h5><p>${error.message || 'The scanned QR code is not valid for this system.'}</p>`);
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
        $('#scanner-status').html(`<p class="text-primary">Switching to camera ${nextIndex + 1}...</p>`);
        $('#camera-loading-overlay').css('display', 'flex').fadeIn(300);
        
        // Disable the button during transition to prevent multiple clicks
        $(this).prop('disabled', true);
        
        // Stop current camera then immediately start new one
        html5QrCode.stop().then(() => {
            // Configuration for scanner
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ]
            };
            
            // Immediately start the new camera
            return html5QrCode.start(cameraId, config, onScanSuccess, onScanFailure);
        })
        .then(() => {
            // Hide loading overlay with fade effect
            $('#camera-loading-overlay').fadeOut(300);
            $('#scanner-status').html('<p class="text-success">Camera switched. Scanning for QR code...</p>');
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
            $('#change-camera-btn').prop('disabled', false);
            console.error("Error during camera switch", err);
        });
    } else {
        // If not scanning, just update the selected camera
        $('#scanner-status').html(`<p class="text-info">Selected camera ${nextIndex + 1}. Press "Start Camera" to begin.</p>`);
    }
});

// Schedule change affects QR code
$('#schedule-select').on('change', function() {
    generateQRCode();
    resetQrAutoRefresh(); // Reset timer when schedule changes
});
</script>

</body>
</html>
<?php
$conn->close();
?>