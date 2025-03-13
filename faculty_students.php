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

include('includes/header.php');
include 'db.php';

// Get the faculty ID from the session
$faculty_id = $_SESSION['user_id'];

// Fetch subjects taught by the faculty
$subject_query = "SELECT id, name, code FROM subjects WHERE faculty_id = ?";
$subject_stmt = $conn->prepare($subject_query);
$subject_stmt->bind_param("i", $faculty_id);
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();

$subjects = [];
$subject_ids = [];
while ($subject = $subject_result->fetch_assoc()) {
    $subjects[] = $subject;
    $subject_ids[] = $subject['id'];
}
$subject_stmt->close();

// Get filter values
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get summary statistics
$total_students = 0;
$total_subjects = count($subjects);
$avg_attendance = 0;

if (!empty($subject_ids)) {
    // Count unique students enrolled in faculty's subjects
    $students_query = "SELECT COUNT(DISTINCT user_id) as total_students FROM usersubjects 
                      WHERE subject_id IN (" . implode(',', array_fill(0, count($subject_ids), '?')) . ")";
    $students_stmt = $conn->prepare($students_query);
    $types = str_repeat('i', count($subject_ids));
    $students_stmt->bind_param($types, ...$subject_ids);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    $total_students = $students_result->fetch_assoc()['total_students'];
    $students_stmt->close();
    
    // Calculate average attendance across all subjects
    if ($total_students > 0) {
        $attendance_query = "SELECT COUNT(*) as total_attendance FROM attendances 
                           WHERE subject_id IN (" . implode(',', array_fill(0, count($subject_ids), '?')) . ")";
        $attendance_stmt = $conn->prepare($attendance_query);
        $attendance_stmt->bind_param($types, ...$subject_ids);
        $attendance_stmt->execute();
        $attendance_result = $attendance_stmt->get_result();
        $total_attendance = $attendance_result->fetch_assoc()['total_attendance'];
        $attendance_stmt->close();
        
        // Simple calculation - can be refined based on your specific requirements
        $avg_attendance = $total_attendance > 0 ? 
            round(($total_attendance / ($total_students * count($subject_ids))) * 100) : 0;
        if ($avg_attendance > 100) $avg_attendance = 100; // Cap at 100%
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">My Students</h1>
    <p class="mb-4">Manage and view students enrolled in your subjects</p>
    
    <!-- Summary Cards Row -->
    <div class="row">
        <!-- Total Students Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_students; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Subjects Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Subjects</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_subjects; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Attendance Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Average Attendance Rate</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $avg_attendance; ?>%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar"
                                            style="width: <?php echo $avg_attendance; ?>%" aria-valuenow="<?php echo $avg_attendance; ?>" aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search Bar -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
            <a href="javascript:void(0);" class="btn btn-sm btn-success" id="exportBtn">
                <i class="fas fa-file-export"></i> Export to CSV
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="subject">Filter by Subject:</label>
                    <select class="form-control" id="subject" name="subject">
                        <option value="all" <?php echo ($filter_subject == 'all') ? 'selected' : ''; ?>>All My Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo ($filter_subject == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['code'] . ': ' . $subject['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="search">Search:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by name, ID, or email" value="<?php echo htmlspecialchars($search_term); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-dark" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
        </div>
        <div class="card-body">
            <?php if (empty($subject_ids)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i> You are not currently teaching any subjects. Once subjects are assigned to you, your students will appear here.
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Subjects</th>
                            <th>Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // If the faculty teaches no subjects, display a message
                        if (empty($subject_ids)) {
                            echo "<tr><td colspan='6'>No students found because you are not teaching any subjects.</td></tr>";
                        } else {
                            // Prepare WHERE clause based on filters
                            $where_clause = "u.usertype = 'student'";
                            if ($filter_subject != 'all') {
                                $where_clause .= " AND us.subject_id = " . intval($filter_subject);
                            } else {
                                $placeholders = implode(',', array_fill(0, count($subject_ids), '?'));
                                $where_clause .= " AND us.subject_id IN (" . $placeholders . ")";
                            }
                            
                            // Add search condition if provided
                            if (!empty($search_term)) {
                                $where_clause .= " AND (u.id LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
                            }
                            
                            // Build the query
                            $query = "SELECT DISTINCT u.id, u.firstname, u.middle_init, u.lastname, u.email, u.department, u.gender, u.image 
                                      FROM users u
                                      INNER JOIN usersubjects us ON u.id = us.user_id
                                      WHERE " . $where_clause . " ORDER BY u.lastname, u.firstname";
                            
                            $stmt = $conn->prepare($query);
                            
                            // Bind parameters
                            if ($filter_subject == 'all') {
                                $types = str_repeat('i', count($subject_ids));
                                $params = $subject_ids;
                                
                                if (!empty($search_term)) {
                                    $search_param = '%' . $search_term . '%';
                                    $types .= 'ssss';
                                    $params[] = $search_param;
                                    $params[] = $search_param;
                                    $params[] = $search_param;
                                    $params[] = $search_param;
                                }
                                
                                if (!empty($params)) {
                                    $stmt->bind_param($types, ...$params);
                                }
                            } else {
                                if (!empty($search_term)) {
                                    $search_param = '%' . $search_term . '%';
                                    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
                                }
                            }
                            
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Calculate attendance statistics for this student
                                    $student_id = $row['id'];
                                    
                                    // Get enrolled subjects for this student (from faculty's subjects)
                                    $enrolled_subjects = [];
                                    $student_subjects_query = "SELECT s.id, s.code, s.name
                                                             FROM subjects s
                                                             INNER JOIN usersubjects us ON s.id = us.subject_id
                                                             WHERE us.user_id = ? AND s.faculty_id = ?
                                                             ORDER BY s.code";
                                    $student_subjects_stmt = $conn->prepare($student_subjects_query);
                                    $student_subjects_stmt->bind_param("ii", $student_id, $faculty_id);
                                    $student_subjects_stmt->execute();
                                    $student_subjects_result = $student_subjects_stmt->get_result();
                                    
                                    while ($subject = $student_subjects_result->fetch_assoc()) {
                                        $enrolled_subjects[] = $subject;
                                    }
                                    $student_subjects_stmt->close();
                                    
                                    // Count total attendances for student in faculty's subjects
                                    $attendance_query = "SELECT COUNT(DISTINCT a.id) AS total_present
                                                       FROM attendances a
                                                       WHERE a.user_id = ? AND a.subject_id IN (SELECT id FROM subjects WHERE faculty_id = ?)";
                                    $attendance_stmt = $conn->prepare($attendance_query);
                                    $attendance_stmt->bind_param("ii", $student_id, $faculty_id);
                                    $attendance_stmt->execute();
                                    $attendance_result = $attendance_stmt->get_result()->fetch_assoc();
                                    $total_present = $attendance_result['total_present'] ?: 0;
                                    
                                    // For attendance rate, use the count of subjects student is enrolled in
                                    // This is a simplified approach - you might want to count actual class sessions
                                    $total_sessions = count($enrolled_subjects) > 0 ? count($enrolled_subjects) : 1; // Avoid division by zero
                                    
                                    $attendance_rate = round(($total_present / $total_sessions) * 100);
                                    $attendance_rate = min(100, $attendance_rate); // Cap at 100%
                                    
                                    // Set attendance status color
                                    $attendance_class = '';
                                    if ($attendance_rate < 60) {
                                        $attendance_class = 'danger';
                                    } elseif ($attendance_rate < 80) {
                                        $attendance_class = 'warning';
                                    } else {
                                        $attendance_class = 'success';
                                    }
                                    
                                    // Default profile image based on gender
                                    $profile_img = empty($row['image']) ? 
                                        (strtolower($row['gender']) === 'female' ? 
                                            'img/undraw_profile_1.svg' : 'img/undraw_profile.svg') : $row['image'];
                                    
                                    echo "<tr>";
                                    echo "<td class='d-flex align-items-center'>
                                            <img class='img-profile rounded-circle mr-2' src='{$profile_img}' width='40' height='40' style='object-fit: cover;'>
                                            <div>
                                                <strong>" . htmlspecialchars($row['firstname'] . ' ' . $row['middle_init'] . ' ' . $row['lastname']) . "</strong>
                                                <br><small class='text-muted'>ID: " . htmlspecialchars($row['id']) . "</small>
                                            </div>
                                          </td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                                    
                                    // Display enrolled subjects as badges
                                    echo "<td>";
                                    foreach ($enrolled_subjects as $index => $subject) {
                                        if ($index < 3) {
                                            echo "<span class='badge badge-pill badge-secondary mr-1'>" . htmlspecialchars($subject['code']) . "</span>";
                                        }
                                    }
                                    if (count($enrolled_subjects) > 3) {
                                        echo "<span class='badge badge-pill badge-info'>+" . (count($enrolled_subjects) - 3) . " more</span>";
                                    }
                                    echo "</td>";
                                    
                                    // Attendance progress bar
                                    echo "<td>
                                            <div class='d-flex align-items-center'>
                                                <div class='progress' style='height: 10px; width: 100px;'>
                                                    <div class='progress-bar bg-{$attendance_class}' role='progressbar' style='width: {$attendance_rate}%'></div>
                                                </div>
                                                <span class='ml-2 text-{$attendance_class}'>{$attendance_rate}%</span>
                                            </div>
                                          </td>";
                                    
                                    echo "<td>
                                            <!-- View button removed -->
                                            <a href='mailto:{$row['email']}' class='btn btn-primary btn-sm email-student' data-email='{$row['email']}'>
                                                <i class='fas fa-envelope'></i>
                                            </a>
                                            <a href='faculty_attendances.php?student_id={$row['id']}' class='btn btn-secondary btn-sm'>
                                                <i class='fas fa-clipboard-list'></i>
                                            </a>
                                          </td>";
                                    echo "</tr>";
                                    
                                    $attendance_stmt->close();
                                }
                            } else {
                                echo "<tr><td colspan='6'>No students found matching your criteria.</td></tr>";
                            }
                            $stmt->close();
                        }
                        mysqli_close($conn);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create get_student_details.php file -->
<script>
$(document).ready(function() {
    // Initialize DataTable with export buttons
    var table = $('#dataTable').DataTable({
        "paging": true,
        "ordering": true,
        "info": true,
        "responsive": true,
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'csv',
                text: 'Export CSV',
                className: 'd-none',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            }
        ]
    });
    
    // Export button click handler
    $('#exportBtn').on('click', function() {
        $('.buttons-csv').click();
    });
    
    // Handle email button
    $('.email-student').on('click', function(e) {
        e.preventDefault();
        var email = $(this).data('email');
        window.location.href = 'mailto:' + email;
    });

    // Enhanced CSV export functionality
    function exportStudentData() {
        var data = [];
        var headers = ['Student ID', 'Name', 'Email', 'Department', 'Subjects', 'Attendance Rate'];
        data.push(headers);

        $('#dataTable tbody tr').each(function() {
            var row = [];
            // Get student ID from the small text
            var studentId = $(this).find('td:first-child small').text().replace('ID: ', '');
            // Get student name without the ID
            var studentName = $(this).find('td:first-child strong').text();
            var email = $(this).find('td:eq(1)').text();
            var department = $(this).find('td:eq(2)').text();
            
            // Get subjects (combine all badge texts)
            var subjects = [];
            $(this).find('td:eq(3) .badge').each(function() {
                subjects.push($(this).text());
            });
            var subjectsText = subjects.join(', ');
            
            // Get attendance rate (remove % symbol)
            var attendance = $(this).find('td:eq(4) .text-success, td:eq(4) .text-warning, td:eq(4) .text-danger').text();
            attendance = attendance.replace('%', '');

            row.push(studentId, studentName, email, department, subjectsText, attendance);
            data.push(row);
        });

        // Convert to CSV
        var csvContent = data.map(row => row.join(',')).join('\n');
        
        // Create and trigger download
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        if (link.download !== undefined) {
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'students_report_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    // Update export button click handler
    $('#exportBtn').off('click').on('click', function(e) {
        e.preventDefault();
        exportStudentData();
    });

    // Add keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl/Cmd + F for search
        if ((e.ctrlKey || e.metaKey) && e.keyCode == 70) {
            e.preventDefault();
            $('#search').focus();
        }
        // Ctrl/Cmd + E for export
        if ((e.ctrlKey || e.metaKey) && e.keyCode == 69) {
            e.preventDefault();
            exportStudentData();
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Add responsive handling for table
    $(window).resize(function() {
        if ($(window).width() < 768) {
            $('.table-responsive').addClass('table-mobile');
        } else {
            $('.table-responsive').removeClass('table-mobile');
        }
    }).resize();
});
</script>

<style>
/* Add responsive styles for mobile view */
@media (max-width: 768px) {
    .table-mobile td:first-child {
        white-space: normal;
    }
    .table-mobile .badge {
        display: inline-block;
        margin-bottom: 2px;
    }
    .btn-sm {
        padding: .2rem .4rem;
        font-size: .875rem;
    }
}
</style>

<?php
include('includes/footer.php');
?>