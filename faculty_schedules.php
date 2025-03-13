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

// Get filter values
$filter_day = isset($_GET['day']) ? $_GET['day'] : 'all';

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
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">My Teaching Schedule</h1>
    
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
                    <label for="day">Filter by Day:</label>
                    <select class="form-control" id="day" name="day">
                        <option value="all" <?php echo ($filter_day == 'all') ? 'selected' : ''; ?>>All Days</option>
                        <option value="Monday" <?php echo ($filter_day == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                        <option value="Tuesday" <?php echo ($filter_day == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                        <option value="Wednesday" <?php echo ($filter_day == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                        <option value="Thursday" <?php echo ($filter_day == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                        <option value="Friday" <?php echo ($filter_day == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                        <option value="Saturday" <?php echo ($filter_day == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Schedule</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="scheduleTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Subject</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // If the faculty teaches no subjects, display a message
                        if (empty($subject_ids)) {
                            echo "<tr><td colspan='5'>No schedules found because you are not teaching any subjects.</td></tr>";
                        } else {
                            // Prepare WHERE clause based on filters
                            $where_clause = "s.subject_id IN (" . implode(',', array_fill(0, count($subject_ids), '?')) . ")";
                            
                            if ($filter_day != 'all') {
                                $where_clause .= " AND s.day = ?";
                            }
                            
                            // Build the query
                            $query = "SELECT s.id, s.day, s.room, s.start_time, s.end_time, 
                                            sub.code as subject_code, sub.name as subject_name, sub.id as subject_id
                                    FROM schedules s
                                    INNER JOIN subjects sub ON s.subject_id = sub.id
                                    WHERE " . $where_clause . "
                                    ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), s.start_time";
                            
                            $stmt = $conn->prepare($query);
                            
                            // Bind parameters
                            $types = str_repeat('i', count($subject_ids));
                            $params = $subject_ids;
                            
                            if ($filter_day != 'all') {
                                $types .= 's';
                                $params[] = $filter_day;
                            }
                            
                            $stmt->bind_param($types, ...$params);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Format the time nicely
                                    $start_time = date('h:i A', strtotime($row['start_time']));
                                    $end_time = date('h:i A', strtotime($row['end_time']));
                                    
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['day']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['subject_code'] . ': ' . $row['subject_name']) . "</td>";
                                    echo "<td>" . $start_time . " - " . $end_time . "</td>";
                                    echo "<td>" . htmlspecialchars($row['room']) . "</td>";
                                    echo "<td>
                                            <a href='faculty_students.php?subject=" . $row['subject_id'] . "' class='btn btn-info btn-sm'>
                                                <i class='fas fa-users'></i> View Students
                                            </a>
                                            <a href='faculty_attendances.php?subject_id=" . $row['subject_id'] . "' class='btn btn-primary btn-sm'>
                                                <i class='fas fa-clipboard-list'></i> Attendance
                                            </a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No schedules found for your subjects.</td></tr>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Weekly Calendar View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Weekly Schedule Overview</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th>Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($subject_ids)) {
                            // Get all schedules for this faculty's subjects
                            $cal_query = "SELECT s.day, s.room, s.start_time, s.end_time, 
                                        sub.code as subject_code, sub.name as subject_name
                                    FROM schedules s
                                    INNER JOIN subjects sub ON s.subject_id = sub.id
                                    WHERE s.subject_id IN (" . implode(',', array_fill(0, count($subject_ids), '?')) . ")
                                    ORDER BY s.start_time";
                            
                            $cal_stmt = $conn->prepare($cal_query);
                            
                            $cal_types = str_repeat('i', count($subject_ids));
                            $cal_stmt->bind_param($cal_types, ...$subject_ids);
                            $cal_stmt->execute();
                            $cal_result = $cal_stmt->get_result();
                            
                            // Organize schedules by day and time
                            $schedules_by_day = [
                                'Monday' => [],
                                'Tuesday' => [],
                                'Wednesday' => [],
                                'Thursday' => [],
                                'Friday' => [],
                                'Saturday' => []
                            ];
                            
                            $time_slots = [];
                            
                            while ($schedule = $cal_result->fetch_assoc()) {
                                $start_time = strtotime($schedule['start_time']);
                                $end_time = strtotime($schedule['end_time']);
                                
                                // Create a unique time slot identifier
                                $time_slot = date('H:i', $start_time) . ' - ' . date('H:i', $end_time);
                                
                                if (!in_array($time_slot, $time_slots)) {
                                    $time_slots[] = $time_slot;
                                }
                                
                                $day = $schedule['day'];
                                if (isset($schedules_by_day[$day])) {
                                    $schedules_by_day[$day][] = [
                                        'start_time' => $start_time,
                                        'end_time' => $end_time,
                                        'time_slot' => $time_slot,
                                        'subject' => $schedule['subject_code'],
                                        'name' => $schedule['subject_name'],
                                        'room' => $schedule['room']
                                    ];
                                }
                            }
                            
                            // Sort time slots
                            usort($time_slots, function($a, $b) {
                                $a_time = strtotime(explode(' - ', $a)[0]);
                                $b_time = strtotime(explode(' - ', $b)[0]);
                                return $a_time - $b_time;
                            });
                            
                            // Output calendar rows
                            foreach ($time_slots as $slot) {
                                echo "<tr>";
                                echo "<td>" . date('h:i A', strtotime(explode(' - ', $slot)[0])) . " - " . 
                                           date('h:i A', strtotime(explode(' - ', $slot)[1])) . "</td>";
                                
                                foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
                                    echo "<td>";
                                    foreach ($schedules_by_day[$day] as $schedule) {
                                        if ($schedule['time_slot'] === $slot) {
                                            echo "<div class='p-2 bg-light border rounded mb-1'>";
                                            echo "<strong>" . htmlspecialchars($schedule['subject']) . "</strong><br>";
                                            echo "<small>" . htmlspecialchars($schedule['name']) . "</small><br>";
                                            echo "<span class='badge badge-secondary'>" . htmlspecialchars($schedule['room']) . "</span>";
                                            echo "</div>";
                                        }
                                    }
                                    echo "</td>";
                                }
                                
                                echo "</tr>";
                            }
                            
                            $cal_stmt->close();
                        } else {
                            echo "<tr><td colspan='7'>No schedules available.</td></tr>";
                        }
                        mysqli_close($conn);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable with export buttons
    var table = $('#scheduleTable').DataTable({
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
                    columns: [0, 1, 2, 3]
                }
            }
        ]
    });
    
    // Export button click handler
    $('#exportBtn').on('click', function() {
        $('.buttons-csv').click();
    });
});
</script>

<?php
include('includes/footer.php');
?>
