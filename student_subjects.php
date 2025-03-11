<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'student') {
    $_SESSION['error'] = "Please login as a student to access this page";
    // header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id']; // Changed from id to user_id to match header.php

// Get student's subjects
$query = "SELECT s.*, us.id as enrollment_id, 
          sch.day, sch.start_time, sch.end_time, sch.room
          FROM subjects s
          INNER JOIN usersubjects us ON s.id = us.subject_id
          LEFT JOIN schedules sch ON s.id = sch.subject_id
          WHERE us.user_id = ? AND s.status = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Get all available subjects for joining
$available_subjects = "SELECT * FROM subjects WHERE status = 1 AND id NOT IN 
                      (SELECT subject_id FROM usersubjects WHERE user_id = ?)";
$stmt_available = $conn->prepare($available_subjects);
$stmt_available->bind_param("i", $student_id);
$stmt_available->execute();
$result_available = $stmt_available->get_result();

// Handle subject enrollment
if (isset($_POST['join_subject'])) {
    $joincode = trim($_POST['joincode']);
    
    // Verify join code and enroll student
    $verify_query = "SELECT id FROM subjects WHERE joincode = ? AND status = 1";
    $stmt_verify = $conn->prepare($verify_query);
    $stmt_verify->bind_param("s", $joincode);
    $stmt_verify->execute();
    $subject_result = $stmt_verify->get_result();
    
    if ($subject_result->num_rows > 0) {
        $subject = $subject_result->fetch_assoc();
        
        // Check if already enrolled
        $check_enrollment = "SELECT id FROM usersubjects WHERE user_id = ? AND subject_id = ?";
        $stmt_check = $conn->prepare($check_enrollment);
        $stmt_check->bind_param("ii", $student_id, $subject['id']);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows == 0) {
            // Enroll student
            $enroll_query = "INSERT INTO usersubjects (user_id, subject_id) VALUES (?, ?)";
            $stmt_enroll = $conn->prepare($enroll_query);
            $stmt_enroll->bind_param("ii", $student_id, $subject['id']);
            
            if ($stmt_enroll->execute()) {
                // Log the enrollment
                $log_query = "INSERT INTO updates (user_id, title, message, timestamp) 
                            VALUES (?, 'Subject Enrollment', 'Enrolled in subject with code: " . $joincode . "', NOW())";
                $stmt_log = $conn->prepare($log_query);
                $stmt_log->bind_param("i", $student_id);
                $stmt_log->execute();
                
                $_SESSION['success'] = "Successfully enrolled in the subject!";
            } else {
                $_SESSION['error'] = "Error enrolling in subject.";
            }
        } else {
            $_SESSION['error'] = "You are already enrolled in this subject.";
        }
    } else {
        $_SESSION['error'] = "Invalid join code.";
    }
    
    header("Location: student_subjects.php");
    exit();
}

// Include the header - must come after session checks
include 'includes/header.php';
?>

<!-- Add this before closing </head> tag -->
<script src="https://unpkg.com/html5-qrcode"></script>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Subjects</h1>
        <button class="btn btn-info" data-toggle="modal" data-target="#joinSubjectModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> Join New Subject
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Content Row -->
    <div class="row">
        <?php if ($result->num_rows > 0): 
            while ($subject = $result->fetch_assoc()): ?>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-info">
                            <?php echo htmlspecialchars($subject['code']); ?>
                        </h6>
                        
                    </div>
                    <div class="card-body">
                        <h5 class="card-title font-weight-bold"><?php echo htmlspecialchars($subject['name']); ?></h5>
                        
                        <?php if ($subject['day']): ?>
                        <div class="mt-3">
                            <h6 class="font-weight-bold">Schedule:</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item py-2 px-0 border-top-0 border-bottom">
                                    <i class="fas fa-calendar-day mr-2 text-gray-500"></i>
                                    <?php echo htmlspecialchars($subject['day']); ?>
                                    <br>
                                    <i class="fas fa-clock ml-2 mr-1 text-gray-500"></i>
                                    <?php echo date("h:i A", strtotime($subject['start_time'])) . ' - ' . 
                                             date("h:i A", strtotime($subject['end_time'])); ?>
                                    <br>
                                    <i class="fas fa-door-open ml-2 mr-1 text-gray-500"></i>
                                    Room <?php echo htmlspecialchars($subject['room']); ?>
                                </li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i> No schedule set
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="row">
                            <div class="col-6">
                                <a href="student_view_subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-info btn-block">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="add_attendance.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-success btn-block">
                                    <i class="fas fa-qrcode mr-1"></i> Attend
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile;
        else: ?>
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book fa-3x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-600">You haven't enrolled in any subjects yet</h5>
                        <p class="text-gray-500">Use a join code to enroll in your first subject</p>
                        <button class="btn btn-info mt-3" data-toggle="modal" data-target="#joinSubjectModal">
                            <i class="fas fa-plus mr-1"></i> Join Subject
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Join Subject Modal -->
<div class="modal fade" id="joinSubjectModal" tabindex="-1" role="dialog" aria-labelledby="joinSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="joinSubjectModalLabel">Join New Subject</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="student_subjects.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="joincode">Enter Subject Join Code:</label>
                        <input type="text" class="form-control" id="joincode" name="joincode" required 
                               pattern="[A-Z0-9]{6}" title="Join code must be 6 characters long">
                        <small class="form-text text-muted">Enter the 6-character join code provided by your instructor</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="join_subject" class="btn btn-info">Join Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel">Mark Attendance</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="reader"></div>
                <div id="scanResult" class="mt-3 text-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#subjectsTable').DataTable({
        "order": [[0, "asc"]],
        "language": {
            "emptyTable": "No subjects enrolled"
        }
    });

    // Handle unenroll button click
    $('.unenroll-subject').click(function() {
        if (confirm('Are you sure you want to unenroll from this subject? This action cannot be undone.')) {
            const enrollmentId = $(this).data('id');
            $.ajax({
                url: 'ajax/unenroll_subject.php',
                type: 'POST',
                data: { enrollment_id: enrollmentId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.error || 'Error unenrolling from subject');
                    }
                },
                error: function() {
                    alert('Error processing request');
                }
            });
        }
    });

    // Handle view button click
    $('.view-subject').click(function() {
        const subjectId = $(this).data('id');
        window.location.href = 'view_subject.php?id=' + subjectId;
    });
});

let html5QrcodeScanner = null;

// Initialize QR Scanner when modal opens
$('#attendanceModal').on('shown.bs.modal', function (e) {
    const button = $(e.relatedTarget);
    const subjectId = button.data('subject-id');
    const subjectCode = button.data('subject-code');
    
    $('#attendanceModalLabel').text('Mark Attendance - ' + subjectCode);
    
    html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { 
            fps: 10,
            qrbox: {width: 250, height: 250},
            aspectRatio: 1.0
        }
    );
    
    html5QrcodeScanner.render((decodedText, decodedResult) => {
        try {
            // Parse QR code data
            const qrData = JSON.parse(decodedText);
            
            // Validate QR code is for attendance
            if (qrData.type !== 'attendance' || qrData.subject_id !== subjectId) {
                throw new Error('Invalid QR code for this subject');
            }
            
            // Check if QR code has expired
            if (new Date(qrData.expires) < new Date()) {
                throw new Error('QR code has expired');
            }
            
            // Submit attendance
            $.ajax({
                url: 'process_attendance.php',
                type: 'POST',
                data: {
                    subject_id: subjectId,
                    qr_data: decodedText
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#scanResult').html(`
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle mr-2"></i>
                                Attendance recorded successfully!
                            </div>
                        `);
                        
                        // Play success sound
                        const audio = new Audio('assets/sounds/success.mp3');
                        audio.play();
                        
                        // Close modal after delay
                        setTimeout(() => {
                            $('#attendanceModal').modal('hide');
                        }, 2000);
                    } else {
                        throw new Error(response.message || 'Failed to record attendance');
                    }
                },
                error: function() {
                    throw new Error('Server error while recording attendance');
                }
            });
            
        } catch (error) {
            $('#scanResult').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    ${error.message}
                </div>
            `);
            
            // Play error sound
            const audio = new Audio('assets/sounds/error.mp3');
            audio.play();
        }
    });
});

// Clean up scanner when modal closes
$('#attendanceModal').on('hidden.bs.modal', function () {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
        html5QrcodeScanner = null;
    }
    $('#scanResult').empty();
});
</script>