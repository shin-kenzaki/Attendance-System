<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User not logged in - redirect to login page
    header("Location: index.php");
    exit();
} 
// Allow access if user is admin or faculty (admins should have access to all pages)
else if ($_SESSION['usertype'] !== 'admin' && $_SESSION['usertype'] !== 'faculty') {
    // User logged in but wrong role - show 404 page
    header("Location: 404.php");
    exit();
}

include 'includes/header.php';
require 'db.php';

// Get filter parameters
$filter_faculty = isset($_GET['faculty_id']) ? $_GET['faculty_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_code = isset($_GET['code_prefix']) ? $_GET['code_prefix'] : '';
$filter_enrollment_min = isset($_GET['enrollment_min']) ? $_GET['enrollment_min'] : '';
$filter_enrollment_max = isset($_GET['enrollment_max']) ? $_GET['enrollment_max'] : '';

// Build SQL query with possible filters
$sql = "SELECT s.*, 
        CONCAT(u.lastname, ', ', u.firstname) AS faculty_name,
        (SELECT COUNT(*) FROM usersubjects WHERE subject_id = s.id) AS enrollment_count 
        FROM subjects s
        LEFT JOIN users u ON s.faculty_id = u.id";

// Add WHERE clauses based on filters
$where_clauses = [];

if (!empty($filter_faculty)) {
    $where_clauses[] = "s.faculty_id = " . $conn->real_escape_string($filter_faculty);
}

if ($filter_status !== '') {
    $where_clauses[] = "s.status = " . $conn->real_escape_string($filter_status);
}

if (!empty($filter_code)) {
    $where_clauses[] = "s.code LIKE '" . $conn->real_escape_string($filter_code) . "%'";
}

// Add WHERE clause to the query if any filters were applied
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$result = $conn->query($sql);

// Get unique subject code prefixes for the filter dropdown
$code_prefixes_sql = "SELECT DISTINCT SUBSTRING_INDEX(code, ' ', 1) AS prefix FROM subjects ORDER BY prefix";
$code_prefixes_result = $conn->query($code_prefixes_sql);
$code_prefixes = [];
if ($code_prefixes_result->num_rows > 0) {
    while($row = $code_prefixes_result->fetch_assoc()) {
        $code_prefixes[] = $row['prefix'];
    }
}

// Get faculty list for filter dropdown
$faculty_sql = "SELECT id, firstname, lastname FROM users WHERE usertype = 'faculty' ORDER BY lastname, firstname";
$faculty_result = $conn->query($faculty_sql);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Subjects</h1>
            <p class="mb-4">
            Manage all subjects and their schedules in one place.
            </p>
        </div>
        <div>
            <div class="btn-group mr-2">
                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
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
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addSubjectModal">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add Subject
            </a>
        </div>
    </div>
    
    <!-- Subjects Stats Cards -->
    <div class="row">
        <!-- Active Subjects -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Subjects</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $active_sql = "SELECT COUNT(*) as count FROM subjects WHERE status = 1";
                                $active_result = $conn->query($active_sql);
                                $active_row = $active_result->fetch_assoc();
                                echo $active_row['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Enrolled Students -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Enrollments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $enrollment_sql = "SELECT COUNT(*) as count FROM usersubjects";
                                $enrollment_result = $conn->query($enrollment_sql);
                                $enrollment_row = $enrollment_result->fetch_assoc();
                                echo $enrollment_row['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Students Per Subject -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Avg. Students/Subject</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $avg_sql = "SELECT ROUND(AVG(cnt), 1) as avg_count FROM (
                                            SELECT COUNT(*) as cnt FROM usersubjects GROUP BY subject_id
                                            ) as counts";
                                $avg_result = $conn->query($avg_sql);
                                $avg_row = $avg_result->fetch_assoc();
                                echo $avg_row['avg_count'] ?: '0';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Popular Subject -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Most Popular Subject</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $popular_sql = "SELECT s.code, COUNT(*) as count 
                                              FROM usersubjects us 
                                              JOIN subjects s ON us.subject_id = s.id 
                                              GROUP BY us.subject_id 
                                              ORDER BY count DESC 
                                              LIMIT 1";
                                $popular_result = $conn->query($popular_sql);
                                if($popular_result->num_rows > 0) {
                                    $popular_row = $popular_result->fetch_assoc();
                                    echo htmlspecialchars($popular_row['code']) . " (" . $popular_row['count'] . ")";
                                } else {
                                    echo "None";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filter Subjects</h6>
            <button class="btn btn-sm btn-link" type="button" data-toggle="collapse" data-target="#filtersCollapse" aria-expanded="true" aria-controls="filtersCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="filtersCollapse">
            <div class="card-body">
                <form method="get" action="subjects.php" id="filterForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="code_prefix"><i class="fas fa-tag mr-1"></i> Subject Code</label>
                            <select class="form-control uniform-filter-size" id="code_prefix" name="code_prefix">
                                <option value="">All Codes</option>
                                <?php foreach($code_prefixes as $prefix): ?>
                                    <option value="<?php echo htmlspecialchars($prefix); ?>" <?php echo ($filter_code == $prefix) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prefix); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="faculty_id"><i class="fas fa-user-tie mr-1"></i> Faculty</label>
                            <select class="form-control uniform-filter-size" id="faculty_id" name="faculty_id">
                                <option value="">All Faculty</option>
                                <option value="null" <?php echo ($filter_faculty === 'null') ? 'selected' : ''; ?>>Not Assigned</option>
                                <?php
                                if ($faculty_result->num_rows > 0) {
                                    while($faculty = $faculty_result->fetch_assoc()) {
                                        $selected = ($filter_faculty == $faculty['id']) ? 'selected' : '';
                                        echo '<option value="' . $faculty["id"] . '" ' . $selected . '>' . $faculty["lastname"] . ', ' . $faculty["firstname"] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="status"><i class="fas fa-toggle-on mr-1"></i> Status</label>
                            <select class="form-control uniform-filter-size" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="1" <?php echo ($filter_status === '1') ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo ($filter_status === '0') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="enrollment_range"><i class="fas fa-users mr-1"></i> Enrollment</label>
                            <div class="d-flex">
                                <input type="number" class="form-control uniform-filter-size mr-2" id="enrollment_min" name="enrollment_min" placeholder="Min" min="0" value="<?php echo htmlspecialchars($filter_enrollment_min); ?>">
                                <input type="number" class="form-control uniform-filter-size" id="enrollment_max" name="enrollment_max" placeholder="Max" min="0" value="<?php echo htmlspecialchars($filter_enrollment_max); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter mr-1"></i> Apply Filters
                            </button>
                            <a href="subjects.php" class="btn btn-secondary btn-sm ml-2">
                                <i class="fas fa-undo mr-1"></i> Reset Filters
                            </a>
                            <button type="button" class="btn btn-info btn-sm ml-2" id="saveFilters">
                                <i class="fas fa-save mr-1"></i> Save Filters
                            </button>
                            <div class="btn-group ml-2" role="group">
                                <button id="loadFiltersDropdown" type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-folder-open mr-1"></i> Load Saved Filters
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" id="savedFiltersDropdown">
                                    <div class="dropdown-item text-muted">No saved filters</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3" id="activeFilters">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap" id="activeFilterTags">
                                <!-- Active filter tags will be inserted here via JavaScript -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Subjects Data</h6>
            <div>
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
                                <th width="30px"><input type="checkbox" id="selectAllSubjects"></th>
                                <th width="80px">ID</th>
                                <th width="120px">Code</th>
                                <th width="200px">Name</th>
                                <th width="200px">Faculty</th>
                                <th width="100px">Status</th>
                                <th width="120px">Enrollment</th>
                                <th width="150px">Join Code</th>
                                <th width="100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                // Output data of each row
                                while($row = $result->fetch_assoc()) {
                                    $statusBadge = ($row["status"] == 1) ? 
                                        '<span class="badge badge-success">Active</span>' : 
                                        '<span class="badge badge-danger">Inactive</span>';
                                    
                                    echo "<tr>";
                                    echo "<td><input type='checkbox' name='subject_checkbox' value='" . htmlspecialchars($row["id"]) . "'></td>";
                                    echo "<td><span class='truncate' title='" . htmlspecialchars($row["id"]) . "'>" . htmlspecialchars($row["id"]) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . htmlspecialchars($row["code"]) . "'>" . htmlspecialchars($row["code"]) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . htmlspecialchars($row["name"]) . "'>" . htmlspecialchars($row["name"]) . "</span></td>";
                                    echo "<td><span class='truncate' title='" . ($row["faculty_name"] ?: 'Not assigned') . "'>" . ($row["faculty_name"] ?: '<span class="text-muted">Not assigned</span>') . "</span></td>";
                                    echo "<td class='text-center'>" . $statusBadge . "</td>";
                                    echo "<td class='text-center'><span class='badge badge-info'>" . $row["enrollment_count"]. " students</span></td>";
                                    echo "<td>
                                            <div class='d-flex align-items-center'>
                                                <span class='join-code mr-2'>" . htmlspecialchars($row["joincode"]) . "</span>
                                                <button class='btn btn-sm btn-outline-secondary' onclick='showJoinCode(" . $row["id"] . ", \"" . htmlspecialchars($row["joincode"]) . "\", \"" . htmlspecialchars($row["code"]) . "\")'>
                                                    <i class='fas fa-eye'></i>
                                                </button>
                                            </div>
                                          </td>";
                                    echo "<td class='actions-cell text-center'><div style='max-width: 200px; white-space: nowrap;'>
                                            <div class='dropdown no-arrow'>
                                                <a class='dropdown-toggle btn btn-sm btn-primary' href='#' role='button' id='dropdownMenuLink' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                                    Actions
                                                </a>
                                                <div class='dropdown-menu dropdown-menu-right shadow animated--fade-in' aria-labelledby='dropdownMenuLink'>
                                                    <a class='dropdown-item' href='view_subject.php?id=" . $row["id"] . "'><i class='fas fa-eye fa-sm fa-fw mr-2 text-gray-400'></i>View</a>
                                                    <a class='dropdown-item' href='#' onclick='editSubject(" . $row["id"] . ")'><i class='fas fa-edit fa-sm fa-fw mr-2 text-gray-400'></i>Edit</a>
                                                    <a class='dropdown-item' href='view_subject.php?id=" . $row["id"] . "#schedulesTable'><i class='fas fa-calendar fa-sm fa-fw mr-2 text-gray-400'></i>Manage Schedule</a>
                                                    <div class='dropdown-divider'></div>
                                                    <a class='dropdown-item' href='#' onclick='changeJoinCode(" . $row["id"] . ", \"" . $row["code"] . "\")'><i class='fas fa-sync fa-sm fa-fw mr-2 text-gray-400'></i>Change Join Code</a>
                                                    <a class='dropdown-item text-danger' href='#' onclick='deleteSubject(" . $row["id"] . ")'><i class='fas fa-trash fa-sm fa-fw mr-2 text-danger'></i>Delete</a>
                                                </div>
                                            </div>
                                        </div></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No subjects found</td></tr>";
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
                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subjects</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="subjects-container">
                        <div class="subject-entry">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="code">Subject Code</label>
                                    <input type="text" class="form-control" name="code[]" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">Subject Name</label>
                                    <input type="text" class="form-control" name="name[]" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="faculty_search">Search Faculty</label>
                                    <input type="text" class="form-control faculty_search" placeholder="Search Faculty...">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="faculty_id">Faculty (Optional)</label>
                                    <select class="form-control faculty_id" name="faculty_id[]">
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
                                <div class="form-group col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger remove-subject w-100">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add-more-subjects">Add More Subjects</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Subjects</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#add-more-subjects').click(function() {
        var newEntry = $('.subject-entry:first').clone();
        newEntry.find('input').val('');
        newEntry.find('select').val('');
        $('#subjects-container').append(newEntry);
    });

    $(document).on('input', '.faculty_search', function() {
        const searchValue = $(this).val().toLowerCase();
        $(this).closest('.subject-entry').find('.faculty_id option').each(function() {
            const optionText = $(this).text().toLowerCase();
            $(this).toggle(optionText.includes(searchValue));
        });
    });

    $(document).on('click', '.remove-subject', function() {
        if ($('.subject-entry').length > 1) {
            $(this).closest('.subject-entry').remove();
        } else {
            alert('At least one subject entry is required.');
        }
    });
});
</script>

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
    // Display active filter tags
    updateActiveFilterTags();
    
    // Initialize DataTables - SINGLE INITIALIZATION WITH ALL OPTIONS
    var dataTable = $('#dataTable').DataTable({
        order: [[2, 'asc']], // Order by subject code by default
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
        },
        initComplete: function() {
            this.api().columns([5]).every(function() {
                var column = this;
                var select = $('<select class="form-control form-control-sm ml-2"><option value="">All Status</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select>')
                    .appendTo($('#dataTable_filter'))
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? val : '', true, false).draw();
                    });
            });
        }
    });
    
    // Apply client-side filtering for enrollment count
    if ($('#enrollment_min').val() || $('#enrollment_max').val()) {
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var min = parseInt($('#enrollment_min').val(), 10) || 0;
                var max = parseInt($('#enrollment_max').val(), 10) || Infinity;
                // The enrollment count is in the 7th column (index 6)
                var enrollmentText = data[6];
                var enrollment = parseInt(enrollmentText.match(/\d+/)[0], 10);
                
                if ((min <= enrollment && enrollment <= max)) {
                    return true;
                }
                return false;
            }
        );
        dataTable.draw();
    }
    
    // Load saved filters from localStorage
    loadSavedFilters();
    
    // Handle saving filters
    $('#saveFilters').click(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Save Current Filters',
            input: 'text',
            inputLabel: 'Filter Set Name',
            inputPlaceholder: 'Enter a name for this filter set',
            showCancelButton: true,
            confirmButtonText: 'Save',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to enter a name!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Get current filter values
                const filterSet = {
                    name: result.value,
                    code_prefix: $('#code_prefix').val(),
                    faculty_id: $('#faculty_id').val(),
                    status: $('#status').val(),
                    enrollment_min: $('#enrollment_min').val(),
                    enrollment_max: $('#enrollment_max').val()
                };
                
                // Get existing saved filters or initialize empty array
                let savedFilters = JSON.parse(localStorage.getItem('subjectFilters')) || [];
                
                // Add new filter set
                savedFilters.push(filterSet);
                
                // Save back to localStorage
                localStorage.setItem('subjectFilters', JSON.stringify(savedFilters));
                
                // Update dropdown
                loadSavedFilters();
                
                Swal.fire('Saved!', 'Your filter set has been saved.', 'success');
            }
        });
    });

    // Check if there's a success message for subject creation
    <?php if(isset($_SESSION['subject_success'])) { ?>
        Swal.fire({
            title: 'Subjects Added Successfully!',
            html: '<ul><?php foreach ($_SESSION["subject_success"] as $message) { echo "<li>$message</li>"; } ?></ul>',
            icon: 'success',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        <?php 
        // Clear the session variables
        unset($_SESSION['subject_success']);
        ?>
    <?php } ?>
    
    // Check if there's an error message for subject creation
    <?php if(isset($_SESSION['subject_error'])) { ?>
        Swal.fire({
            title: 'Error Adding Subjects',
            html: '<ul><?php foreach ($_SESSION["subject_error"] as $message) { echo "<li>$message</li>"; } ?></ul>',
            icon: 'error',
            confirmButtonColor: '#d33',
            confirmButtonText: 'OK'
        });
        <?php 
        // Clear the session variables
        unset($_SESSION['subject_error']);
        ?>
    <?php } ?>

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

    // Handle "Select All" for Subjects table
    $('#selectAllSubjects').change(function() {
        const isChecked = $(this).prop('checked');
        $('input[name="subject_checkbox"]').prop('checked', isChecked);
    });

    // Update header checkbox when individual checkboxes change
    $('#dataTable tbody').on('change', 'input[name="subject_checkbox"]', function() {
        const totalCheckboxes = $('input[name="subject_checkbox"]').length;
        const checkedCheckboxes = $('input[name="subject_checkbox"]:checked').length;
        $('#selectAllSubjects').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
    });
});

// Function to update active filter tags
function updateActiveFilterTags() {
    const $tagsContainer = $('#activeFilterTags');
    $tagsContainer.empty();
    
    let hasFilters = false;
    
    // Check each filter and add a tag if it's active
    if ($('#code_prefix').val()) {
        const prefix = $('#code_prefix').val();
        $tagsContainer.append(createFilterTag('Code: ' + prefix, function() {
            $('#code_prefix').val('');
            $('#filterForm').submit();
        }));
        hasFilters = true;
    }
    
    if ($('#faculty_id').val()) {
        const facultyText = $('#faculty_id option:selected').text();
        $tagsContainer.append(createFilterTag('Faculty: ' + facultyText, function() {
            $('#faculty_id').val('');
            $('#filterForm').submit();
        }));
        hasFilters = true;
    }
    
    if ($('#status').val() !== '') {
        const statusText = $('#status option:selected').text();
        $tagsContainer.append(createFilterTag('Status: ' + statusText, function() {
            $('#status').val('');
            $('#filterForm').submit();
        }));
        hasFilters = true;
    }
    
    if ($('#enrollment_min').val() || $('#enrollment_max').val()) {
        const min = $('#enrollment_min').val() || '0';
        const max = $('#enrollment_max').val() || '∞';
        $tagsContainer.append(createFilterTag('Enrollment: ' + min + ' - ' + max, function() {
            $('#enrollment_min').val('');
            $('#enrollment_max').val('');
            $('#filterForm').submit();
        }));
        hasFilters = true;
    }
    
    // Show or hide the active filters section
    if (hasFilters) {
        $('#activeFilters').show();
    } else {
        $('#activeFilters').hide();
    }
}

// Function to create a filter tag element
function createFilterTag(text, removeCallback) {
    const $tag = $('<span class="badge badge-pill badge-primary mr-2 mb-2 py-2 px-3"></span>').text(text);
    const $removeBtn = $('<i class="fas fa-times-circle ml-2" style="cursor: pointer;"></i>');
    
    $removeBtn.click(function() {
        removeCallback();
    });
    
    $tag.append($removeBtn);
    return $tag;
}

// Function to load saved filters
function loadSavedFilters() {
    const $dropdown = $('#savedFiltersDropdown');
    $dropdown.empty();
    
    const savedFilters = JSON.parse(localStorage.getItem('subjectFilters')) || [];
    
    if (savedFilters.length === 0) {
        $dropdown.append('<div class="dropdown-item text-muted">No saved filters</div>');
        return;
    }
    
    savedFilters.forEach((filterSet, index) => {
        const $item = $('<a class="dropdown-item" href="#"></a>').text(filterSet.name);
        
        $item.click(function(e) {
            e.preventDefault();
            
            // Apply the saved filter values
            $('#code_prefix').val(filterSet.code_prefix || '');
            $('#faculty_id').val(filterSet.faculty_id || '');
            $('#status').val(filterSet.status || '');
            $('#enrollment_min').val(filterSet.enrollment_min || '');
            $('#enrollment_max').val(filterSet.enrollment_max || '');
            
            // Submit the form
            $('#filterForm').submit();
        });
        
        const $deleteBtn = $('<i class="fas fa-trash text-danger ml-2"></i>');
        $deleteBtn.css('cursor', 'pointer');
        
        $deleteBtn.click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Remove this filter set
            savedFilters.splice(index, 1);
            localStorage.setItem('subjectFilters', JSON.stringify(savedFilters));
            
            // Reload filters
            loadSavedFilters();
        });
        
        $item.append($deleteBtn);
        $dropdown.append($item);
    });
    
    // Add a divider and clear all option
    if (savedFilters.length > 0) {
        $dropdown.append('<div class="dropdown-divider"></div>');
        const $clearAll = $('<a class="dropdown-item text-danger" href="#"></a>').text('Clear All Saved Filters');
        
        $clearAll.click(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Clear All Saved Filters?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, clear all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem('subjectFilters');
                    loadSavedFilters();
                    Swal.fire('Cleared!', 'All saved filters have been removed.', 'success');
                }
            });
        });
        
        $dropdown.append($clearAll);
    }
}

// Function to handle batch actions
function batchAction(action) {
    const selectedIds = [];
    $('input[name="subject_checkbox"]:checked').each(function() {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        Swal.fire('No Subjects Selected', 'Please select subjects to perform this action.', 'warning');
        return;
    }

    let title, text, confirmButtonText, icon = 'warning';
    switch(action) {
        case 'activate':
            title = 'Activate Selected Subjects?';
            text = 'This will activate all selected subjects.';
            confirmButtonText = 'Yes, activate them!';
            break;
        case 'deactivate':
            title = 'Deactivate Selected Subjects?';
            text = 'This will deactivate all selected subjects.';
            confirmButtonText = 'Yes, deactivate them!';
            break;
        case 'delete':
            title = 'Delete Selected Subjects?';
            text = 'This will permanently delete all selected subjects and their related data!';
            confirmButtonText = 'Yes, delete them!';
            icon = 'error';
            break;
    }

    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Processing...',
                html: 'Please wait while we process your request.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: 'batch_subject_action.php',
                type: 'POST',
                data: {
                    action: action,
                    subject_ids: selectedIds
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            Swal.fire('Success!', data.message, 'success')
                            .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message || 'An unknown error occurred', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error!', 'Invalid server response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error!', 'Request failed: ' + error, 'error');
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

<style>
/* Custom styles for the subjects page */
.join-code {
    font-family: monospace;
    font-weight: bold;
    letter-spacing: 1px;
}

.badge {
    padding: 0.5em 0.75em;
    font-size: 80%;
}

.truncate {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

.actions-cell .dropdown-menu {
    min-width: 200px;
}

/* Improve table row hover effect */
#dataTable tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05) !important;
}

/* Filter tag styles */
#activeFilterTags .badge {
    font-size: 85%;
    transition: all 0.2s;
}

#activeFilterTags .badge:hover {
    background-color: #0069d9;
}

#activeFilterTags .fa-times-circle:hover {
    color: #f8f9fc;
}

/* Filter section styles */
#filtersCollapse {
    transition: all 0.3s;
}

.filter-heading {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.filter-heading i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
}

/* Custom filter styles */
.custom-filter-group {
    display: inline-flex;
    align-items: center;
    margin-left: 15px;
}

.custom-filter-group label {
    margin-bottom: 0;
    margin-right: 5px;
    font-weight: bold;
    font-size: 0.8rem;
}

/* Uniform filter input sizes */
.uniform-filter-size {
    width: 100%;
}
</style>

</body>
</html>
<?php
$conn->close();
?>