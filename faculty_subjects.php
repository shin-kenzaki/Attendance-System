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

// Get faculty ID from session
$faculty_id = $_SESSION['user_id'];

// Include common header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Subjects</h1>
    </div>

    <!-- Content Row -->
    <div class="row">
        <?php
        // Get all subjects taught by this faculty member
        $query = "SELECT * FROM subjects WHERE faculty_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($subject = $result->fetch_assoc()) {
                // Get schedule information for this subject
                $scheduleQuery = "SELECT * FROM schedules WHERE subject_id = ?";
                $scheduleStmt = $conn->prepare($scheduleQuery);
                $scheduleStmt->bind_param("i", $subject['id']);
                $scheduleStmt->execute();
                $scheduleResult = $scheduleStmt->get_result();
                
                // Get enrolled student count
                $studentQuery = "SELECT COUNT(*) as student_count FROM usersubjects WHERE subject_id = ?";
                $studentStmt = $conn->prepare($studentQuery);
                $studentStmt->bind_param("i", $subject['id']);
                $studentStmt->execute();
                $studentCount = $studentStmt->get_result()->fetch_assoc()['student_count'];
        ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php echo htmlspecialchars($subject['code']); ?>
                            </h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                    <div class="dropdown-header">Actions:</div>
                                    <a class="dropdown-item" href="#" onclick="editSubject(<?php echo $subject['id']; ?>)">
                                        <i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i>Edit
                                    </a>
                                    <a class="dropdown-item" href="view_attendance.php?subject_id=<?php echo $subject['id']; ?>">
                                        <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>View Attendance
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="regenerateJoinCode(<?php echo $subject['id']; ?>)">
                                        <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>Regenerate Join Code
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title font-weight-bold"><?php echo htmlspecialchars($subject['name']); ?></h5>
                            <p class="card-text">
                                <span class="badge badge-info">Join Code: <?php echo htmlspecialchars($subject['joincode']); ?></span>
                                <span class="badge badge-success ml-2"><?php echo $studentCount; ?> Students</span>
                                <span class="badge badge-<?php echo $subject['status'] == 1 ? 'success' : 'danger'; ?> ml-2">
                                    <?php echo $subject['status'] == 1 ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                            
                            <?php if ($scheduleResult->num_rows > 0): ?>
                                <div class="mt-3">
                                    <h6 class="font-weight-bold">Schedule:</h6>
                                    <ul class="list-group list-group-flush">
                                    <?php while ($schedule = $scheduleResult->fetch_assoc()): ?>
                                        <li class="list-group-item py-2 px-0 border-top-0 border-bottom">
                                            <i class="fas fa-calendar-day mr-2 text-gray-500"></i>
                                            <?php echo htmlspecialchars($schedule['day']); ?> 
                                            <i class="fas fa-clock ml-2 mr-1 text-gray-500"></i>
                                            <?php 
                                                echo date("h:i A", strtotime($schedule['start_time'])) . ' - ' . 
                                                     date("h:i A", strtotime($schedule['end_time']));
                                            ?>
                                            <i class="fas fa-door-open ml-2 mr-1 text-gray-500"></i>
                                            Room <?php echo htmlspecialchars($schedule['room']); ?>
                                        </li>
                                    <?php endwhile; ?>
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
                                    <a href="take_attendance.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm btn-block">
                                        <i class="fas fa-clipboard-check mr-1"></i> Take Attendance
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="faculty_view_subject_students.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-secondary btn-sm btn-block">
                                        <i class="fas fa-user-graduate mr-1"></i> View Students
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php
            }
        } else {
            // No subjects found
        ?>
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book fa-3x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-600">You don't have any subjects yet</h5>
                        <p class="text-gray-500">Click the 'Add New Subject' button to create your first subject.</p>
                    </div>
                </div>
            </div>
        <?php
        }
        $stmt->close();
        ?>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addSubjectForm" action="process_subject.php" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="subjectCode">Subject Code</label>
                        <input type="text" class="form-control" id="subjectCode" name="code" required>
                    </div>
                    <div class="form-group">
                        <label for="subjectName">Subject Name</label>
                        <input type="text" class="form-control" id="subjectName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="subjectStatus">Status</label>
                        <select class="form-control" id="subjectStatus" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="add">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editSubjectForm" action="process_subject.php" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editSubjectCode">Subject Code</label>
                        <input type="text" class="form-control" id="editSubjectCode" name="code" required>
                    </div>
                    <div class="form-group">
                        <label for="editSubjectName">Subject Name</label>
                        <input type="text" class="form-control" id="editSubjectName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editSubjectStatus">Status</label>
                        <select class="form-control" id="editSubjectStatus" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <input type="hidden" id="editSubjectId" name="subject_id">
                    <input type="hidden" name="action" value="edit">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" role="dialog" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSubjectModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this subject? This action cannot be undone.</p>
                <p>All related attendance records and class schedules will be deleted as well.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteSubjectForm" action="process_subject.php" method="post">
                    <input type="hidden" id="deleteSubjectId" name="subject_id">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">Delete Subject</button>
                </form>
            </div>
        </div>
    </div>
</div>

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
                    <p><strong>Subject:</strong> <span id="qr-subject-name"></span> (<span id="qr-subject-code"></span>)</p>
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

<!-- Add QR Code Library if not already included -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<script>
    // Function to edit subject
    function editSubject(subjectId) {
        // Get subject data via AJAX
        $.ajax({
            type: 'POST',
            url: 'get_subject.php',
            data: {subject_id: subjectId},
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                    return;
                }
                
                // Populate the form
                $('#editSubjectId').val(response.id);
                $('#editSubjectCode').val(response.code);
                $('#editSubjectName').val(response.name);
                $('#editSubjectStatus').val(response.status);
                
                // Show the modal
                $('#editSubjectModal').modal('show');
            },
            error: function() {
                alert('Error fetching subject data');
            }
        });
    }
    
    // Function to confirm subject deletion
    function confirmDelete(subjectId) {
        $('#deleteSubjectId').val(subjectId);
        $('#deleteSubjectModal').modal('show');
    }
    
    // Function to regenerate subject join code
    function regenerateJoinCode(subjectId) {
        if (confirm('Are you sure you want to regenerate the join code? Students using the current code will no longer be able to join.')) {
            $.ajax({
                type: 'POST',
                url: 'process_subject.php',
                data: {
                    subject_id: subjectId,
                    action: 'regenerate_code'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert(response.error);
                    } else {
                        alert('Join code has been regenerated: ' + response.joincode);
                        // Reload the page to show the new code
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error regenerating join code');
                }
            });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>