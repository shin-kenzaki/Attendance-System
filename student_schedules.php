<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'student') {
    $_SESSION['error'] = "Please login as a student to access this page";
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get current day of the week
$today = date('l'); // e.g., "Monday"
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Fetch the student's schedule
$query = "SELECT s.id, s.code, s.name, u.firstname, u.lastname as faculty_name, 
          sch.day, sch.start_time, sch.end_time, sch.room 
          FROM subjects s
          INNER JOIN usersubjects us ON s.id = us.subject_id
          INNER JOIN schedules sch ON s.id = sch.subject_id
          LEFT JOIN users u ON s.faculty_id = u.id
          WHERE us.user_id = ?
          ORDER BY sch.day, sch.start_time";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Organize schedules by day for calendar view
$schedule_by_day = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => []
];

// Today's classes
$todays_classes = [];

// Store time range for visualization
$earliest_time = '23:59:59';
$latest_time = '00:00:00';

// Days ordered for correct display
$days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

while ($row = $result->fetch_assoc()) {
    // Add to day-wise schedule
    $schedule_by_day[$row['day']][] = $row;
    
    // Track earliest and latest class times for the calendar
    if ($row['start_time'] < $earliest_time) {
        $earliest_time = $row['start_time'];
    }
    if ($row['end_time'] > $latest_time) {
        $latest_time = $row['end_time'];
    }
    
    // Check if this class is today
    if ($row['day'] == $today) {
        $row['is_current'] = ($current_time >= $row['start_time'] && $current_time <= $row['end_time']);
        $row['is_upcoming'] = ($current_time < $row['start_time']);
        $row['is_past'] = ($current_time > $row['end_time']);
        $todays_classes[] = $row;
    }
}

// Sort today's classes by start time
usort($todays_classes, function($a, $b) {
    return strtotime($a['start_time']) - strtotime($b['start_time']);
});

// Generate time slots for calendar view with 15-minute intervals for better accuracy
$earliest_minutes = (int)date('i', strtotime($earliest_time));
$earliest_hour = (int)date('H', strtotime($earliest_time));
$latest_minutes = (int)date('i', strtotime($latest_time));
$latest_hour = (int)date('H', strtotime($latest_time));

// Round down to nearest 15-minute slot for start time
$earliest_minutes = floor($earliest_minutes / 15) * 15;
// Round up to nearest 15-minute slot for end time
$latest_minutes = ceil($latest_minutes / 15) * 15;
if ($latest_minutes >= 60) {
    $latest_hour++;
    $latest_minutes = 0;
}

$time_slots = [];
for ($h = $earliest_hour; $h <= $latest_hour; $h++) {
    for ($m = 0; $m < 60; $m += 15) {
        // Skip times before the earliest time
        if ($h == $earliest_hour && $m < $earliest_minutes) continue;
        // Skip times after the latest time
        if ($h == $latest_hour && $m > $latest_minutes) continue;
        
        $time_slots[] = sprintf('%02d:%02d', $h, $m);
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Class Schedule</h1>
        <div>
            <a href="javascript:void(0)" onclick="printSchedule()" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                <i class="fas fa-print fa-sm text-white-50"></i> Print Schedule
            </a>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm ml-1" data-toggle="modal" data-target="#exportModal">
                <i class="fas fa-download fa-sm text-white-50"></i> Export
            </a>
        </div>
    </div>

    <?php if (count($todays_classes) > 0): ?>
    <!-- Today's Classes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-info">
                        Today's Classes (<?php echo date('l, F j'); ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($todays_classes as $class): 
                            $status_class = 'secondary';
                            $status_text = 'Upcoming';
                            
                            if ($class['is_current']) {
                                $status_class = 'success';
                                $status_text = 'In Progress';
                            } elseif ($class['is_past']) {
                                $status_class = 'light';
                                $status_text = 'Completed';
                            }
                        ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-<?php echo $status_class; ?> shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-<?php echo $status_class; ?> text-uppercase mb-1">
                                                    <?php echo htmlspecialchars($class['code']); ?>
                                                </div>
                                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo htmlspecialchars($class['name']); ?>
                                                </div>
                                                <div class="small mt-2">
                                                    <i class="fas fa-clock mr-1"></i> 
                                                    <?php echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time'])); ?>
                                                    <br>
                                                    <i class="fas fa-door-open mr-1"></i>
                                                    Room <?php echo htmlspecialchars($class['room']); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <span class="badge badge-<?php echo $status_class; ?> p-2">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Weekly Schedule View -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">Weekly Calendar</h6>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-info active" id="viewCalendar">Calendar View</button>
                        <button type="button" class="btn btn-sm btn-outline-info" id="viewTable">Table View</button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Calendar View -->
                    <div id="calendarView" class="schedule-calendar">
                        <div class="table-responsive">
                            <table class="table table-bordered schedule-table">
                                <thead>
                                    <tr>
                                        <th class="time-header">Time</th>
                                        <?php foreach ($days_order as $day): ?>
                                        <th class="<?php echo $day == $today ? 'bg-light' : ''; ?>">
                                            <?php echo $day; ?>
                                            <?php echo $day == $today ? '<span class="badge badge-info ml-1">Today</span>' : ''; ?>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($time_slots as $index => $time): 
                                        // Parse the time slot
                                        list($hour, $minute) = explode(':', $time);
                                        $formatted_time = date('g:i A', strtotime($time));
                                        $time_obj = strtotime($time);
                                        $next_time = date('H:i', strtotime($time) + 15 * 60); // Add 15 minutes
                                    ?>
                                    <tr>
                                        <td class="time-slot">
                                            <?php echo $formatted_time; ?>
                                        </td>
                                        <?php foreach ($days_order as $day): ?>
                                        <td class="schedule-cell <?php echo $day == $today ? 'bg-light' : ''; ?>">
                                            <?php 
                                            foreach ($schedule_by_day[$day] as $class) {
                                                $class_start = strtotime($class['start_time']);
                                                $class_end = strtotime($class['end_time']);
                                                
                                                // Class is in this time slot if:
                                                // 1. The current time is exactly the class start time OR
                                                // 2. The current time is after start and before end OR
                                                // 3. The current time slot starts before class end time and the next time slot is after class start time
                                                if ($time_obj == $class_start || 
                                                    ($time_obj > $class_start && $time_obj < $class_end) ||
                                                    (strtotime($next_time) > $class_start && $time_obj < $class_end)) {
                                                    
                                                    $color_seed = crc32($class['code']);
                                                    $hue = $color_seed % 360;
                                                    
                                                    echo '<div class="class-block" style="background-color: hsl('.$hue.', 50%, 80%);" 
                                                         data-toggle="tooltip" title="'.htmlspecialchars($class['name']).'">';
                                                    
                                                    // Only show full details for the starting time slot
                                                    if ($time_obj == $class_start) {
                                                        echo '<div class="class-code">'.htmlspecialchars($class['code']).'</div>';
                                                        echo '<div class="class-time">'.date('g:i', $class_start).' - '.
                                                             date('g:i A', $class_end).'</div>';
                                                        echo '<div class="class-room">Room '.htmlspecialchars($class['room']).'</div>';
                                                    } else {
                                                        echo '<div class="class-continuing">'.htmlspecialchars($class['code']).' (cont.)</div>';
                                                    }
                                                    echo '</div>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Table View -->
                    <div id="tableView" class="schedule-table-view" style="display:none;">
                        <div class="filters mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-info active filter-day" data-day="all">All Days</button>
                                <?php foreach ($days_order as $day): 
                                    if (!empty($schedule_by_day[$day])): ?>
                                <button type="button" class="btn btn-sm btn-outline-info filter-day" data-day="<?php echo $day; ?>">
                                    <?php echo $day; ?>
                                </button>
                                <?php 
                                    endif;
                                endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="scheduleTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Time</th>
                                        <th>Room</th>
                                        <th>Instructor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $all_classes = [];
                                    foreach ($days_order as $day) {
                                        foreach ($schedule_by_day[$day] as $class) {
                                            $all_classes[] = $class;
                                        }
                                    }
                                    
                                    // Sort by day and time
                                    usort($all_classes, function($a, $b) use ($days_order) {
                                        $day_diff = array_search($a['day'], $days_order) - array_search($b['day'], $days_order);
                                        if ($day_diff == 0) {
                                            return strtotime($a['start_time']) - strtotime($b['start_time']);
                                        }
                                        return $day_diff;
                                    });
                                    
                                    if (count($all_classes) > 0) {
                                        foreach ($all_classes as $class) {
                                            echo '<tr class="day-'.htmlspecialchars($class['day']).'">';
                                            echo '<td>' . htmlspecialchars($class['day']) . '</td>';
                                            echo '<td>' . htmlspecialchars($class['code']) . '</td>';
                                            echo '<td>' . htmlspecialchars($class['name']) . '</td>';
                                            echo '<td>' . date('h:i A', strtotime($class['start_time'])) . ' - ' . 
                                                     date('h:i A', strtotime($class['end_time'])) . '</td>';
                                            echo '<td>' . htmlspecialchars($class['room']) . '</td>';
                                            echo '<td>' . (!empty($class['faculty_name']) ? htmlspecialchars($class['firstname'] . ' ' . $class['faculty_name']) : 'Not assigned') . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center">No classes scheduled.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Schedule</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <a href="exports/schedule_pdf.php" target="_blank" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="far fa-file-pdf mr-2 text-danger"></i> PDF Format</h5>
                        </div>
                        <p class="mb-1">Download your schedule as a PDF document</p>
                    </a>
                    <a href="exports/schedule_ical.php" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="far fa-calendar-alt mr-2 text-primary"></i> iCalendar Format</h5>
                        </div>
                        <p class="mb-1">Export to .ics file to import into Google Calendar, Outlook, etc.</p>
                    </a>
                    <a href="exports/schedule_excel.php" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="far fa-file-excel mr-2 text-success"></i> Excel Format</h5>
                        </div>
                        <p class="mb-1">Download your schedule as an Excel spreadsheet</p>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.schedule-calendar {
    width: 100%;
    overflow-x: auto;
}

.schedule-table {
    min-width: 800px;
}

.time-header, .time-slot {
    width: 80px;
    text-align: center;
    vertical-align: middle;
    font-weight: bold;
}

.schedule-cell {
    position: relative;
    height: 40px; /* Reduced height for more compact rows */
    vertical-align: top;
    padding: 0 !important;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

/* Special styling for exact hour lines to make them stand out */
tr:nth-of-type(4n+1) .schedule-cell {
    border-bottom: 1px solid rgba(0,0,0,0.3);
}

/* Time label styling */
.time-slot {
    width: 80px;
    text-align: right;
    vertical-align: middle;
    font-weight: 500;
    font-size: 0.8rem;
    padding-right: 10px !important;
    white-space: nowrap;
}

/* For hour markers, make them bold */
tr:nth-of-type(4n+1) .time-slot {
    font-weight: bold;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(0,0,0,0.3);
}

.class-block {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 5px;
    overflow: hidden;
    color: #343a40;
    border-radius: 4px;
    margin: 2px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.class-code {
    font-weight: bold;
    font-size: 0.9rem;
}

.class-time, .class-room {
    font-size: 0.8rem;
}

.class-continuing {
    font-style: italic;
    font-size: 0.8rem;
    text-align: center;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

@media print {
    #wrapper #content-wrapper {
        overflow-x: initial;
    }
    
    .no-print {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd;
    }
    
    .container-fluid {
        padding: 0 !important;
    }
}
</style>

<script>
    $(document).ready(function() {
        // Initialize DataTable for schedule table
        var scheduleTable = $('#scheduleTable').DataTable({
            "paging": false,
            "ordering": true,
            "info": false,
            "searching": true
        });
        
        // Enable tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Toggle between calendar and table views
        $('#viewCalendar').click(function() {
            $('#calendarView').show();
            $('#tableView').hide();
            $('#viewCalendar').addClass('active');
            $('#viewTable').removeClass('active');
        });
        
        $('#viewTable').click(function() {
            $('#tableView').show();
            $('#calendarView').hide();
            $('#viewTable').addClass('active');
            $('#viewCalendar').removeClass('active');
        });
        
        // Day filter for table view
        $('.filter-day').click(function() {
            $('.filter-day').removeClass('active');
            $(this).addClass('active');
            
            var day = $(this).data('day');
            if (day === 'all') {
                scheduleTable.columns(0).search('').draw();
            } else {
                scheduleTable.columns(0).search(day).draw();
            }
        });
    });
    
    // Print schedule function
    function printSchedule() {
        // Show calendar view for printing
        $('#calendarView').show();
        $('#tableView').hide();
        
        // Add extra styling for print
        $('body').addClass('printing');
        $('.no-print').hide();
        
        window.print();
        
        // Restore original state
        setTimeout(function() {
            $('body').removeClass('printing');
            $('.no-print').show();
            
            // Restore active view
            if ($('#viewTable').hasClass('active')) {
                $('#tableView').show();
                $('#calendarView').hide();
            }
        }, 100);
    }
</script>