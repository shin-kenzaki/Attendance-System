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

// Include common header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Students</h1>
            <p class="mb-0"><?php echo htmlspecialchars($subject['name']); ?> (<?php echo htmlspecialchars($subject['code']); ?>)</p>
        </div>
        <div>
            <a href="faculty_subjects.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Subjects
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Enrolled Students</h6>
            <div>
                <span class="mr-2">Join Code: <strong><?php echo htmlspecialchars($subject['joincode']); ?></strong></span>
                <button class="btn btn-sm btn-outline-primary" onclick="copyJoinCode('<?php echo htmlspecialchars($subject['joincode']); ?>')">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Last Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get all students enrolled in this subject with their latest attendance
                        $studentQuery = "SELECT u.id, u.firstname, u.middle_init, u.lastname, u.email, u.department, 
                                        MAX(a.time_in) as last_attendance
                                        FROM users u
                                        JOIN usersubjects us ON u.id = us.user_id
                                        LEFT JOIN attendances a ON u.id = a.user_id AND a.subject_id = ?
                                        WHERE us.subject_id = ? AND u.usertype = 'student'
                                        GROUP BY u.id
                                        ORDER BY u.lastname, u.firstname";
                        
                        $studentStmt = $conn->prepare($studentQuery);
                        $studentStmt->bind_param("ii", $subject_id, $subject_id);
                        $studentStmt->execute();
                        $studentResult = $studentStmt->get_result();
                        
                        if ($studentResult->num_rows > 0) {
                            while ($student = $studentResult->fetch_assoc()) {
                                $fullName = htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . 
                                           ($student['middle_init'] ? ' ' . $student['middle_init'] . '.' : ''));
                                $lastAttendance = $student['last_attendance'] ? date('M d, Y h:i A', $student['last_attendance']) : 'Never';
                        ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                                    <td><?php echo $fullName; ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department']); ?></td>
                                    <td><?php echo $lastAttendance; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewAttendance(<?php echo $student['id']; ?>)">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="removeStudent(<?php echo $student['id']; ?>, '<?php echo addslashes($fullName); ?>')">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="6" class="text-center">No students enrolled in this subject yet.</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- View Attendance History Modal -->
<div class="modal fade" id="attendanceHistoryModal" tabindex="-1" role="dialog" aria-labelledby="attendanceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceHistoryModalLabel">Student Attendance History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="attendanceHistoryContent">
                    <!-- Attendance history will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Sweet Alert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#studentsTable').DataTable();
});

// Remove student from subject
function removeStudent(studentId, studentName) {
    Swal.fire({
        title: 'Remove Student',
        text: `Are you sure you want to remove ${studentName} from this subject?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'process_enrollment.php',
                type: 'POST',
                data: {
                    action: 'remove',
                    student_id: studentId,
                    subject_id: <?php echo $subject_id; ?>
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to remove student from subject',
                        icon: 'error'
                    });
                }
            });
        }
    });
}

// View student attendance history
function viewAttendance(studentId) {
    // Load attendance history via AJAX
    $.ajax({
        url: 'get_student_attendance.php',
        type: 'GET',
        data: {
            student_id: studentId,
            subject_id: <?php echo $subject_id; ?>
        },
        success: function(response) {
            $('#attendanceHistoryContent').html(response);
            $('#attendanceHistoryModal').modal('show');
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Failed to load attendance history',
                icon: 'error'
            });
        }
    });
}

// Copy join code to clipboard
function copyJoinCode(joinCode) {
    navigator.clipboard.writeText(joinCode).then(function() {
        Swal.fire({
            title: 'Copied!',
            text: 'Join code copied to clipboard',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
    }, function() {
        Swal.fire({
            title: 'Error',
            text: 'Failed to copy join code',
            icon: 'error'
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>