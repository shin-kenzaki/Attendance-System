<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
require '../db.php';

// Fetch subjects data from the database
$sql = "SELECT subjects.*, CONCAT(users.lastname, ', ', users.firstname) AS faculty_name 
        FROM subjects 
        LEFT JOIN users ON subjects.faculty_id = users.id";
$result = $conn->query($sql);

?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Subjects</h1>
    <p class="mb-4">Displaying all subjects data in a table.</p>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Subjects Data</h6>
            <div>
                <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSubjectModal">Add Subject</a>
                <div class="dropdown no-arrow d-inline">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                        <div class="dropdown-header">Export Options:</div>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-csv fa-sm fa-fw mr-2"></i>CSV</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-excel fa-sm fa-fw mr-2"></i>Excel</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-file-pdf fa-sm fa-fw mr-2"></i>PDF</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <div class="px-3">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Faculty</th>
                                <th>Status</th>
                                <th>Join Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                // Output data of each row
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr onclick='location.href=\"view_subject.php?id=" . $row["id"] . "\"' style='cursor: pointer;'>";
                                    echo "<td><div style='max-width: 200px; white-space: nowrap;'>" . $row["id"]. "</div></td>";
                                    echo "<td><div style='max-width: 200px; white-space: nowrap;'>" . $row["code"]. "</div></td>";
                                    echo "<td><div style='max-width: 200px; white-space: nowrap;'>" . $row["name"]. "</div></td>";
                                    echo "<td><div style='max-width: 200px; white-space: nowrap;'>" . $row["faculty_name"]. "</div></td>";
                                    echo "<td><div style='max-width: 200px; white-space: nowrap;'>" . $row["status"]. "</div></td>";
                                    echo "<td><div style='max-width: 200px; white-space: nowrap;'>" . $row["joincode"]. "</div></td>";
                                    echo "<td class='text-center'><div style='max-width: 200px; white-space: nowrap;'>
                                            <div class='dropdown no-arrow'>
                                                <a class='dropdown-toggle btn btn-sm btn-secondary' href='#' role='button' id='dropdownMenuLink' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                                    Actions
                                                </a>
                                                <div class='dropdown-menu dropdown-menu-right shadow animated--fade-in' aria-labelledby='dropdownMenuLink'>
                                                    <a class='dropdown-item' href='#' onclick='editSubject(" . $row["id"] . ")'><i class='fas fa-edit fa-sm fa-fw mr-2 text-gray-400'></i>Edit</a>
                                                    <a class='dropdown-item' href='#' onclick='showJoinCode(" . $row["id"] . ", \"" . $row["joincode"] . "\", \"" . $row["code"] . "\")'><i class='fas fa-key fa-sm fa-fw mr-2 text-gray-400'></i>View Join Code</a>
                                                    <a class='dropdown-item' href='#' onclick='changeJoinCode(" . $row["id"] . ", \"" . $row["code"] . "\")'><i class='fas fa-sync fa-sm fa-fw mr-2 text-gray-400'></i>Change Join Code</a>
                                                    <a class='dropdown-item' href='#' onclick='deleteSubject(" . $row["id"] . ")'><i class='fas fa-trash fa-sm fa-fw mr-2 text-gray-400'></i>Delete</a>
                                                </div>
                                            </div>
                                        </div></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>No subjects found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<?php include 'includes/footer.php'; ?>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="add_subject.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="code">Subject Code</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="name">Subject Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="faculty_search">Search Faculty</label>
                            <input type="text" class="form-control" id="faculty_search" placeholder="Search Faculty...">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="faculty_id">Faculty (Optional)</label>
                            <select class="form-control" id="faculty_id" name="faculty_id">
                                <option value="">Select Faculty</option>
                                <?php
                                $faculty_sql = "SELECT id, firstname, lastname FROM users WHERE usertype = 'faculty'";
                                $faculty_result = $conn->query($faculty_sql);
                                if ($faculty_result->num_rows > 0) {
                                    while($faculty = $faculty_result->fetch_assoc()) {
                                        echo '<option value="' . $faculty["id"] . '">' . $faculty["lastname"] . ', ' . $faculty["firstname"] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <!-- Hidden field for auto-generated join code -->
                    <input type="hidden" name="joincode" id="joincode">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="update_subject.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_subject_id" name="subject_id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_code">Subject Code</label>
                            <input type="text" class="form-control" id="edit_code" name="code" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_name">Subject Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_faculty_search">Search Faculty</label>
                            <input type="text" class="form-control" id="edit_faculty_search" placeholder="Search Faculty...">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_faculty_id">Faculty (Optional)</label>
                            <select class="form-control" id="edit_faculty_id" name="faculty_id">
                                <option value="">Select Faculty</option>
                                <?php
                                $faculty_sql = "SELECT id, firstname, lastname FROM users WHERE usertype = 'faculty'";
                                $faculty_result = $conn->query($faculty_sql);
                                if ($faculty_result->num_rows > 0) {
                                    while($faculty = $faculty_result->fetch_assoc()) {
                                        echo '<option value="' . $faculty["id"] . '">' . $faculty["lastname"] . ', ' . $faculty["firstname"] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label for="edit_status">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Subject</button>
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

<!-- Page level custom scripts -->
<script>
$(document).ready(function() {
    // Check if there's a success message for subject creation
    <?php if(isset($_SESSION['subject_success'])) { ?>
        Swal.fire({
            title: 'Subject Added Successfully!',
            html: '<p>Subject has been created with the following details:</p>' +
                  '<p><strong>Code:</strong> <?php echo $_SESSION["subject_code"]; ?></p>' +
                  '<p><strong>Name:</strong> <?php echo $_SESSION["subject_name"]; ?></p>' +
                  '<p><strong>Join Code:</strong> <?php echo $_SESSION["subject_joincode"]; ?></p>' +
                  '<p>Please save the join code to share with students.</p>',
            icon: 'success',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        <?php 
        // Clear the session variables
        unset($_SESSION['subject_success']);
        unset($_SESSION['subject_code']);
        unset($_SESSION['subject_name']);
        unset($_SESSION['subject_joincode']);
        ?>
    <?php } ?>

    $('#dataTable').DataTable({
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"d-flex align-items-center justify-content-between"<"mr-3"l><"ml-auto"f>>rt<"bottom d-flex align-items-center justify-content-between"ip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search subjects...",
            lengthMenu: "Entries per page: _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ subjects",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        }
    });
    
    // Auto generate join code when the modal is shown
    $('#addSubjectModal').on('show.bs.modal', function() {
        const joinCode = generateRandomJoinCode(6);
        $('#joincode').val(joinCode);
        // Add a hidden informational text about the auto-generated code
        if (!$('#joinCodeInfo').length) {
            $('<small id="joinCodeInfo" class="form-text text-muted text-center mt-2">A 6-character join code will be automatically generated.</small>').insertAfter('#joincode');
        }
    });

    // Filter faculty options based on search input
    $('#faculty_search').on('input', function() {
        const searchValue = $(this).val().toLowerCase();
        $('#faculty_id option').each(function() {
            const optionText = $(this).text().toLowerCase();
            $(this).toggle(optionText.includes(searchValue));
        });
    });

    $('#edit_faculty_search').on('input', function() {
        const searchValue = $(this).val().toLowerCase();
        $('#edit_faculty_id option').each(function() {
            const optionText = $(this).text().toLowerCase();
            $(this).toggle(optionText.includes(searchValue));
        });
    });
});

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

// Function to show join code in a SweetAlert
function showJoinCode(subjectId, joinCode, subjectCode) {
    Swal.fire({
        title: 'Subject Join Code',
        html: '<p>Share this code with students so they can join the subject:</p>' +
              '<p><strong>Subject:</strong> ' + subjectCode + '</p>' +
              '<p><strong>Join Code:</strong> <span class="h3">' + joinCode + '</span></p>',
        icon: 'info',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    });
}

// Function to generate and change join code
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

// Function to delete subject
function deleteSubject(subjectId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! All related data will be lost.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_subject.php?id=' + subjectId;
        }
    });
}

// Function to edit subject
function editSubject(subjectId) {
    // Fetch subject data via AJAX
    $.ajax({
        url: 'get_subject.php',
        type: 'POST',
        data: {
            subject_id: subjectId
        },
        success: function(response) {
            const subject = JSON.parse(response);
            if (subject) {
                // Populate the form with subject data
                $('#edit_subject_id').val(subject.id);
                $('#edit_code').val(subject.code);
                $('#edit_name').val(subject.name);
                $('#edit_faculty_id').val(subject.faculty_id);
                $('#edit_status').val(subject.status);
                
                // Show the modal
                $('#editSubjectModal').modal('show');
            } else {
                Swal.fire('Error', 'Failed to load subject data.', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load subject data.', 'error');
        }
    });
}
</script>

</body>
</html>
<?php
$conn->close();
?>