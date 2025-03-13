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

// Get user statistics
$stats = [
    'total_users' => 0,
    'total_faculty' => 0,
    'total_students' => 0,
    'active_users' => 0,
    'inactive_users' => 0,
    'departments' => []
];

$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN usertype = 'faculty' THEN 1 ELSE 0 END) as faculty_count,
    SUM(CASE WHEN usertype = 'student' THEN 1 ELSE 0 END) as student_count,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
    department
    FROM users 
    GROUP BY department"; // Removed the WHERE clause to include admins
$statsResult = $conn->query($statsQuery);

while($row = $statsResult->fetch_assoc()) {
    $stats['departments'][$row['department']] = [
        'total' => $row['total'],
        'faculty' => $row['faculty_count'],
        'students' => $row['student_count']
    ];
    $stats['total_users'] += $row['total'];
    $stats['total_faculty'] += $row['faculty_count'];
    $stats['total_students'] += $row['student_count'];
    $stats['active_users'] += $row['active_count'];
    $stats['inactive_users'] += $row['inactive_count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        .table td, .table th {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .table td.actions-cell {
            overflow: visible; /* Allow dropdowns to be visible */
            white-space: nowrap;
            max-width: none;
        }
        
        .truncate {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Uniform filter input sizes */
        .uniform-filter-size {
            width: 100%;
        }
    </style>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading with batch actions -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Users</h1>
            <p class="mb-4">
                Manage all system users including faculty members, students, and administrators.
            </p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-cog fa-sm"></i> Batch Actions
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="#" onclick="batchAction('activate')">
                    <i class="fas fa-check-circle fa-sm fa-fw mr-2 text-gray-400"></i>Activate Selected
                </a>
                <a class="dropdown-item" href="#" onclick="batchAction('deactivate')">
                    <i class="fas fa-times-circle fa-sm fa-fw mr-2 text-gray-400"></i>Deactivate Selected
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="#" onclick="batchAction('delete')">
                    <i class="fas fa-trash fa-sm fa-fw mr-2"></i>Delete Selected
                </a>
            </div>
        </div>
    </div>


    <!-- Statistics Cards -->
    <div class="row">
        <!-- Total Users Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_users'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Faculty Count Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Faculty Members</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_faculty'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Count Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_students'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active/Inactive Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['active_users'] ?> / <?= $stats['total_users'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add the filters section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filter Users</h6>
            <button class="btn btn-sm btn-link" type="button" data-toggle="collapse" data-target="#filtersCollapse" aria-expanded="true" aria-controls="filtersCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="filtersCollapse">
            <div class="card-body">
                <form id="filterForm" class="form-inline">
                    <div class="row w-100">
                        <div class="col-md-6 mb-3">
                            <label for="departmentFilter" class="d-block text-left">
                                <i class="fas fa-building mr-1"></i> Department
                            </label>
                            <select class="form-control uniform-filter-size w-100" id="departmentFilter">
                                <option value="">All Departments</option>
                                <?php foreach(array_keys($stats['departments']) as $dept): ?>
                                    <option value="<?= $dept ?>"><?= $dept ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="statusFilter" class="d-block text-left">
                                <i class="fas fa-toggle-on mr-1"></i> Status
                            </label>
                            <select class="form-control uniform-filter-size w-100" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Buttons Row -->
                    <div class="row w-100 mt-2">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter mr-1"></i> Apply Filters
                            </button>
                            <a href="users.php" class="btn btn-secondary btn-sm ml-2">
                                <i class="fas fa-undo mr-1"></i> Reset Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

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
                        <table class="table table-bordered" id="facultyTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="30px"><input type="checkbox" id="selectAllFaculty"></th>
                                    <th width="100px">ID</th>
                                    <th width="200px">Name</th>
                                    <th width="200px">Email</th>
                                    <th width="150px">Department</th>
                                    <th width="100px">Status</th>
                                    <th width="150px">Last Login</th>
                                    <th width="100px">Actions</th>
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
                                        echo "<td class='text-center'><input type='checkbox' name='user_checkbox' value='" . htmlspecialchars($row["id"]) . "'></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["id"]) . "'>" . htmlspecialchars($row["id"]) . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "'>" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["email"]) . "'>" . htmlspecialchars($row["email"]) . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["department"]) . "'>" . htmlspecialchars($row["department"]) . "</span></td>";
                                        echo "<td style='white-space: nowrap;'><span class='{$status_class}'><i class='fas {$status_icon}'></i> " . $status_text . "</span></td>";
                                        echo "<td style='white-space: nowrap;'>" . ($row["last_login"] ? date('M d, Y h:i A', strtotime($row["last_login"])) : 'Never') . "</td>";
                                        echo "<td class='actions-cell text-center' style='white-space: nowrap;'>
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
                                    echo "<tr><td colspan='8' class='text-center'>No faculty users found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Students Tab -->
                <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="30px"><input type="checkbox" id="selectAllStudents"></th>
                                    <th width="100px">ID</th>
                                    <th width="200px">Name</th>
                                    <th width="200px">Email</th>
                                    <th width="150px">Department</th>
                                    <th width="100px">Status</th>
                                    <th width="150px">Last Login</th>
                                    <th width="100px">Actions</th>
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
                                        echo "<td class='text-center'><input type='checkbox' name='user_checkbox' value='" . htmlspecialchars($row["id"]) . "'></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["id"]) . "'>" . htmlspecialchars($row["id"]) . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "'>" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["email"]) . "'>" . htmlspecialchars($row["email"]) . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["department"]) . "'>" . htmlspecialchars($row["department"]) . "</span></td>";
                                        echo "<td style='white-space: nowrap;'><span class='{$status_class}'><i class='fas {$status_icon}'></i> " . $status_text . "</span></td>";
                                        echo "<td style='white-space: nowrap;'>" . ($row["last_login"] ? date('M d, Y h:i A', strtotime($row["last_login"])) : 'Never') . "</td>";
                                        echo "<td class='actions-cell text-center' style='white-space: nowrap;'>
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
                                    echo "<tr><td colspan='8' class='text-center'>No student users found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
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
                                        <th><input type="checkbox" id="selectAllOthers"></th>
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
                                        echo "<td><input type='checkbox' name='user_checkbox' value='" . htmlspecialchars($row["id"]) . "'></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["id"]) . "'>" . htmlspecialchars($row["id"]) . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "'>" . htmlspecialchars($row["lastname"] . ", " . $row["firstname"] . " " . $row["middle_init"] . ".") . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["email"]) . "'>" . htmlspecialchars($row["email"]) . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars(ucfirst($row["usertype"])) . "'>" . htmlspecialchars(ucfirst($row["usertype"])) . "</span></td>";
                                        echo "<td><span class='truncate' title='" . htmlspecialchars($row["department"]) . "'>" . htmlspecialchars($row["department"]) . "</span></td>";
                                        echo "<td style='white-space: nowrap;'><span class='{$status_class}'><i class='fas {$status_icon}'></i> " . $status_text . "</span></td>";
                                        echo "<td style='white-space: nowrap;'>" . ($row["last_login"] ? date('M d, Y h:i A', strtotime($row["last_login"])) : 'Never') . "</td>";
                                        echo "<td class='actions-cell text-center' style='white-space: nowrap;'>
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="add_user.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New Users</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="users-container">
                        <div class="user-entry mb-3 border-bottom pb-3">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>First Name</label>
                                    <input type="text" class="form-control" name="firstname[]" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Middle Initial</label>
                                    <input type="text" class="form-control" name="middle_init[]">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Last Name</label>
                                    <input type="text" class="form-control" name="lastname[]" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Email</label>
                                    <input type="email" class="form-control" name="email[]" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>User Type</label>
                                    <select class="form-control" name="usertype[]" required>
                                        <option value="student">Student</option>
                                        <option value="faculty">Faculty</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Department</label>
                                    <select class="form-control" name="department[]" required>
                                        <option value="BSCS">BSCS</option>
                                        <option value="BSAIS">BSAIS</option>
                                        <option value="BSA">BSA</option>
                                        <option value="BSE">BSE</option>
                                        <option value="BSTM">BSTM</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Gender</label>
                                    <select class="form-control" name="gender[]" required>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger remove-user w-100">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add-more-users">Add More Users</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Users</button>
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
    // Check if there are user credentials to display
    <?php if(isset($_SESSION['success']) && isset($_SESSION['user_credentials'])) { ?>
        Swal.fire({
            title: 'Users Added Successfully!',
            html: `<div class="text-left">
                <p>Users have been created with the following credentials:</p>
                <ul>
                    <?php foreach ($_SESSION["user_credentials"] as $user): ?>
                        <li>
                            <strong><?php echo $user["name"]; ?></strong><br>
                            Email: <?php echo $user["email"]; ?><br>
                            Password: <?php echo $user["password"]; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p>Please save or share these credentials with the users.</p>
            </div>`,
            icon: 'success',
            width: '600px',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        <?php 
        // Clear the session variables
        unset($_SESSION['success']);
        unset($_SESSION['user_credentials']);
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
    
    // Add these handlers after the existing document.ready code
    
    // Handle "Select All" for Faculty table
    $('#selectAllFaculty').change(function() {
        const isChecked = $(this).prop('checked');
        $('#facultyTable tbody input[type="checkbox"]').prop('checked', isChecked);
    });

    // Handle "Select All" for Students table
    $('#selectAllStudents').change(function() {
        const isChecked = $(this).prop('checked');
        $('#studentsTable tbody input[type="checkbox"]').prop('checked', isChecked);
    });

    // Handle "Select All" for Others table
    $('#selectAllOthers').change(function() {
        const isChecked = $(this).prop('checked');
        $('#othersTable tbody input[type="checkbox"]').prop('checked', isChecked);
    });

    // Update header checkbox when individual checkboxes change
    function updateHeaderCheckbox(tableId, headerCheckboxId) {
        const totalCheckboxes = $(`#${tableId} tbody input[type="checkbox"]`).length;
        const checkedCheckboxes = $(`#${tableId} tbody input[type="checkbox"]:checked`).length;
        $(`#${headerCheckboxId}`).prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
    }

    // Add change event listeners for individual checkboxes
    $('#facultyTable tbody').on('change', 'input[type="checkbox"]', function() {
        updateHeaderCheckbox('facultyTable', 'selectAllFaculty');
    });

    $('#studentsTable tbody').on('change', 'input[type="checkbox"]', function() {
        updateHeaderCheckbox('studentsTable', 'selectAllStudents');
    });

    $('#othersTable tbody').on('change', 'input[type="checkbox"]', function() {
        updateHeaderCheckbox('othersTable', 'selectAllOthers');
    });
});

// Handle adding more users
$('#add-more-users').click(function() {
    var newEntry = $('.user-entry:first').clone();
    newEntry.find('input').val('');
    newEntry.find('select').prop('selectedIndex', 0);
    $('#users-container').append(newEntry);
});

// Handle removing users
$(document).on('click', '.remove-user', function() {
    if ($('.user-entry').length > 1) {
        $(this).closest('.user-entry').remove();
    } else {
        alert('At least one user entry is required.');
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

function batchAction(action) {
    const selectedIds = [];
    $('input[name="user_checkbox"]:checked').each(function() {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        Swal.fire('No Users Selected', 'Please select users to perform this action.', 'warning');
        return;
    }

    let title, text, confirmButtonText;
    switch(action) {
        case 'activate':
            title = 'Activate Selected Users?';
            text = 'This will activate all selected users.';
            confirmButtonText = 'Yes, activate them!';
            break;
        case 'deactivate':
            title = 'Deactivate Selected Users?';
            text = 'This will deactivate all selected users.';
            confirmButtonText = 'Yes, deactivate them!';
            break;
        case 'delete':
            title = 'Delete Selected Users?';
            text = 'This action cannot be undone!';
            confirmButtonText = 'Yes, delete them!';
            break;
    }

    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'batch_user_action.php',
                type: 'POST',
                data: {
                    action: action,
                    user_ids: selectedIds
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        Swal.fire('Success!', data.message, 'success')
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'An error occurred while processing the request.', 'error');
                }
            });
        }
    });
}

function applyFilters() {
    const department = $('#departmentFilter').val();
    const status = $('#statusFilter').val();
    
    $('#facultyTable').DataTable().columns(3).search(department).draw();
    $('#studentsTable').DataTable().columns(3).search(department).draw();
    
    if (status) {
        const statusIndex = 4; // Adjust based on your table structure
        const searchTerm = status === 'active' ? 'Active' : 'Inactive';
        $('#facultyTable').DataTable().columns(statusIndex).search(searchTerm).draw();
        $('#studentsTable').DataTable().columns(statusIndex).search(searchTerm).draw();
    }
}

function resetFilters() {
    $('#filterForm')[0].reset();
    $('#facultyTable').DataTable().search('').columns().search('').draw();
    $('#studentsTable').DataTable().search('').columns().search('').draw();
}
</script>

</body>
</html>
<?php $conn->close(); ?>