<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User not logged in - redirect to login page
    header("Location: index.php");
    exit();
} 
// Allow access if user is admin or faculty (admins should have access to all pages)
else if ($_SESSION['usertype'] !== 'admin') {
    // User logged in but wrong role - show 404 page
    header("Location: 404.php");
    exit();
}

include 'includes/header.php';
require 'db.php';

// Fetch users data from the database - we'll get all users at once
$sql = "SELECT * FROM users ORDER BY lastname ASC";
$result = $conn->query($sql);

// Separate users by type
$faculty = [];
$students = [];
$others = []; // For admin or other types

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if ($row["usertype"] == "faculty") {
            $faculty[] = $row;
        } elseif ($row["usertype"] == "student") {
            $students[] = $row;
        } else {
            $others[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Users</h1>
    <p class="mb-4">Manage faculty and student users in separate tabs.</p>

    <!-- DataTales Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Users Data</h6>
            <div>
                <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">Add User</a>
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
            <!-- Nav tabs -->
            <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="faculty-tab" data-toggle="tab" href="#faculty" role="tab" 
                       aria-controls="faculty" aria-selected="true">
                       <i class="fas fa-chalkboard-teacher mr-1"></i> Faculty
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="students-tab" data-toggle="tab" href="#students" role="tab" 
                       aria-controls="students" aria-selected="false">
                       <i class="fas fa-user-graduate mr-1"></i> Students
                    </a>
                </li>
                <?php if(!empty($others)): ?>
                <li class="nav-item">
                    <a class="nav-link" id="others-tab" data-toggle="tab" href="#others" role="tab" 
                       aria-controls="others" aria-selected="false">
                       <i class="fas fa-users-cog mr-1"></i> Administrators
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- Tab content -->
            <div class="tab-content" id="userTabContent">
                <!-- Faculty Tab -->
                <div class="tab-pane fade show active" id="faculty" role="tabpanel" aria-labelledby="faculty-tab">
                    <div class="table-responsive">
                        <div class="px-3">
                            <table class="table table-bordered table-hover" id="facultyTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($faculty)) {
                                        foreach($faculty as $row) {
                                            $status_class = ($row["status"] == 'active') ? 'text-success' : 'text-danger';
                                            $status_icon = ($row["status"] == 'active') ? 'fa-check-circle' : 'fa-times-circle';
                                            $status_text = ucfirst($row["status"]); 
                                            echo "<tr>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["id"]) . "</td>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "</td>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["email"]) . "</td>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["department"]) . "</td>";
                                            echo "<td style='white-space: nowrap;'><span class='{$status_class}'><i class='fas {$status_icon}'></i> " . $status_text . "</span></td>";
                                            echo "<td style='white-space: nowrap;'>" . ($row["last_login"] ? date('M d, Y h:i A', strtotime($row["last_login"])) : 'Never') . "</td>";
                                            echo "<td class='text-center' style='white-space: nowrap;'>
                                                    <div class='dropdown no-arrow'>
                                                        <a class='dropdown-toggle btn btn-sm btn-secondary' href='#' role='button' id='dropdownMenuLink' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                                            Actions
                                                        </a>
                                                        <div class='dropdown-menu dropdown-menu-right shadow animated--fade-in' aria-labelledby='dropdownMenuLink'>
                                                            <a class='dropdown-item' href='#' onclick='editUser(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-edit fa-sm fa-fw mr-2 text-gray-400'></i>Edit</a>
                                                            <a class='dropdown-item' href='#' onclick='generateNewPassword(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-key fa-sm fa-fw mr-2 text-gray-400'></i>Generate New Password</a>
                                                            <a class='dropdown-item' href='#' onclick='deleteUser(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-trash fa-sm fa-fw mr-2 text-gray-400'></i>Delete</a>
                                                        </div>
                                                    </div>
                                                </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No faculty users found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Students Tab -->
                <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab">
                    <div class="table-responsive">
                        <div class="px-3">
                            <table class="table table-bordered table-hover" id="studentsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($students)) {
                                        foreach($students as $row) {
                                            $status_class = ($row["status"] == 'active') ? 'text-success' : 'text-danger';
                                            $status_icon = ($row["status"] == 'active') ? 'fa-check-circle' : 'fa-times-circle';
                                            $status_text = ucfirst($row["status"]);
                                            echo "<tr>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["id"]) . "</td>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "</td>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["email"]) . "</td>";
                                            echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["department"]) . "</td>";
                                            echo "<td style='white-space: nowrap;'><span class='{$status_class}'><i class='fas {$status_icon}'></i> " . $status_text . "</span></td>";
                                            echo "<td style='white-space: nowrap;'>" . ($row["last_login"] ? date('M d, Y h:i A', strtotime($row["last_login"])) : 'Never') . "</td>";
                                            echo "<td class='text-center' style='white-space: nowrap;'>
                                                    <div class='dropdown no-arrow'>
                                                        <a class='dropdown-toggle btn btn-sm btn-secondary' href='#' role='button' id='dropdownMenuLink' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                                            Actions
                                                        </a>
                                                        <div class='dropdown-menu dropdown-menu-right shadow animated--fade-in' aria-labelledby='dropdownMenuLink'>
                                                            <a class='dropdown-item' href='#' onclick='editUser(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-edit fa-sm fa-fw mr-2 text-gray-400'></i>Edit</a>
                                                            <a class='dropdown-item' href='#' onclick='generateNewPassword(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-key fa-sm fa-fw mr-2 text-gray-400'></i>Generate New Password</a>
                                                            <a class='dropdown-item' href='#' onclick='deleteUser(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-trash fa-sm fa-fw mr-2 text-gray-400'></i>Delete</a>
                                                        </div>
                                                    </div>
                                                </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No student users found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php if(!empty($others)): ?>
                <!-- Others Tab (Admins) -->
                <div class="tab-pane fade" id="others" role="tabpanel" aria-labelledby="others-tab">
                    <div class="table-responsive">
                        <div class="px-3">
                            <table class="table table-bordered table-hover" id="othersTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach($others as $row) {
                                        $status_class = ($row["status"] == 'active') ? 'text-success' : 'text-danger';
                                        $status_icon = ($row["status"] == 'active') ? 'fa-check-circle' : 'fa-times-circle';
                                        $status_text = ucfirst($row["status"]);
                                        echo "<tr>";
                                        echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["id"]) . "</td>";
                                        echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "</td>";
                                        echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["email"]) . "</td>";
                                        echo "<td style='white-space: nowrap;'>" . htmlspecialchars(ucfirst($row["usertype"])) . "</td>";
                                        echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row["department"]) . "</td>";
                                        echo "<td style='white-space: nowrap;'><span class='{$status_class}'><i class='fas {$status_icon}'></i> " . $status_text . "</span></td>";
                                        echo "<td style='white-space: nowrap;'>" . ($row["last_login"] ? date('M d, Y h:i A', strtotime($row["last_login"])) : 'Never') . "</td>";
                                        echo "<td class='text-center' style='white-space: nowrap;'>
                                                <div class='dropdown no-arrow'>
                                                    <a class='dropdown-toggle btn btn-sm btn-secondary' href='#' role='button' id='dropdownMenuLink' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                                        Actions
                                                    </a>
                                                    <div class='dropdown-menu dropdown-menu-right shadow animated--fade-in' aria-labelledby='dropdownMenuLink'>
                                                        <a class='dropdown-item' href='#' onclick='editUser(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-edit fa-sm fa-fw mr-2 text-gray-400'></i>Edit</a>
                                                        <a class='dropdown-item' href='#' onclick='generateNewPassword(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-key fa-sm fa-fw mr-2 text-gray-400'></i>Generate New Password</a>
                                                        <a class='dropdown-item' href='#' onclick='deleteUser(" . htmlspecialchars($row["id"]) . ")'><i class='fas fa-trash fa-sm fa-fw mr-2 text-gray-400'></i>Delete</a>
                                                    </div>
                                                </div>
                                            </td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="add_user.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="firstname">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="middle_init">Middle Initial</label>
                            <input type="text" class="form-control" id="middle_init" name="middle_init">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="lastname">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="usertype">User Type</label>
                            <select class="form-control" id="usertype" name="usertype" required>
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="department">Department</label>
                            <select class="form-control" id="department" name="department" required>
                                <option value="BSCS">BSCS</option>
                                <option value="BSAIS">BSAIS</option>
                                <option value="BSA">BSA</option>
                                <option value="BSE">BSE</option>
                                <option value="BSTM">BSTM</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="gender">Gender</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                    <!-- Hidden field for auto-generated password -->
                    <input type="hidden" name="auto_password" id="auto_password">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="update_user.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="edit_firstname">First Name</label>
                            <input type="text" class="form-control" id="edit_firstname" name="firstname" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="edit_middle_init">Middle Initial</label>
                            <input type="text" class="form-control" id="edit_middle_init" name="middle_init">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="edit_lastname">Last Name</label>
                            <input type="text" class="form-control" id="edit_lastname" name="lastname" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label for="edit_email">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="edit_usertype">User Type</label>
                            <select class="form-control" id="edit_usertype" name="usertype" required>
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="edit_department">Department</label>
                            <select class="form-control" id="edit_department" name="department" required>
                                <option value="BSCS">BSCS</option>
                                <option value="BSAIS">BSAIS</option>
                                <option value="BSA">BSA</option>
                                <option value="BSE">BSE</option>
                                <option value="BSTM">BSTM</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="edit_gender">Gender</label>
                            <select class="form-control" id="edit_gender" name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label for="edit_status">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
<!-- End of Main Content -->

<?php include 'includes/footer.php'; ?>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<!-- Sweet Alert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<!-- Page specific script -->
<script>
$(document).ready(function() {
    // Check if there's a success message with credentials
    <?php if(isset($_SESSION['success']) && isset($_SESSION['email']) && isset($_SESSION['password'])) { ?>
        Swal.fire({
            title: 'User Added Successfully!',
            html: '<p>User has been created with the following credentials:</p>' +
                  '<p><strong>Email:</strong> <?php echo $_SESSION["email"]; ?></p>' +
                  '<p><strong>Password:</strong> <?php echo $_SESSION["password"]; ?></p>' +
                  '<p>Please save or share these credentials with the user.</p>',
            icon: 'success',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        <?php 
        // Clear the session variables
        unset($_SESSION['success']);
        unset($_SESSION['email']);
        unset($_SESSION['password']);
        ?>
    <?php } ?>

    // Initialize all DataTables
    const tableConfig = {
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"d-flex align-items-center justify-content-between"<"mr-3"l><"ml-auto"f>>rt<"bottom d-flex align-items-center justify-content-between"ip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search users...",
            lengthMenu: "Entries per page: _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        }
    };
    
    $('#facultyTable').DataTable(tableConfig);
    $('#studentsTable').DataTable(tableConfig);
    <?php if(!empty($others)): ?>
    $('#othersTable').DataTable(tableConfig);
    <?php endif; ?>
    
    // Auto generate password when the modal is shown
    $('#addUserModal').on('show.bs.modal', function() {
        const autoPassword = generateRandomPassword(10);
        $('#auto_password').val(autoPassword);
    });
    
    // Handle tab change - redraw tables to fix layout issues
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
    
    // Handle direct linking to tabs via URL hash
    if(window.location.hash) {
        const hash = window.location.hash.substring(1);
        if(hash === 'faculty' || hash === 'students' || hash === 'others') {
            $(`#${hash}-tab`).tab('show');
        }
    }
});

// Function to generate random password
function generateRandomPassword(length) {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * charset.length);
        password += charset[randomIndex];
    }
    return password;
}

function generateNewPassword(userId) {
    const newPassword = generateRandomPassword(10);
    
    // Send AJAX request to update the password
    $.ajax({
        url: 'generate_password.php',
        type: 'POST',
        data: {
            user_id: userId,
            new_password: newPassword
        },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Password Generated!',
                    html: '<p>A new password has been generated for the user:</p>' +
                          '<p><strong>Email:</strong> ' + data.email + '</p>' +
                          '<p><strong>New Password:</strong> ' + newPassword + '</p>' +
                          '<p>Please save or share this password with the user.</p>',
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire('Error', 'Failed to generate new password.', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to generate new password.', 'error');
        }
    });
}

function deleteUser(userId) {
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
            window.location.href = 'delete_user.php?id=' + userId;
        }
    });
}

function editUser(userId) {
    // Fetch user data via AJAX
    $.ajax({
        url: 'get_user.php',
        type: 'POST',
        data: {
            user_id: userId
        },
        success: function(response) {
            const user = JSON.parse(response);
            if (user) {
                // Populate the form with user data
                $('#edit_user_id').val(user.id);
                $('#edit_firstname').val(user.firstname);
                $('#edit_middle_init').val(user.middle_init);
                $('#edit_lastname').val(user.lastname);
                $('#edit_email').val(user.email);
                $('#edit_usertype').val(user.usertype);
                $('#edit_department').val(user.department);
                $('#edit_gender').val(user.gender);
                $('#edit_status').val(user.status.toLowerCase()); // Ensure the status is in lowercase
                
                // Show the modal
                $('#editUserModal').modal('show');
            } else {
                Swal.fire('Error', 'Failed to load user data.', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load user data.', 'error');
        }
    });
}
</script>

</body>
</html>
<?php $conn->close(); ?>