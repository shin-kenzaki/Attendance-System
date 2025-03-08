<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';

require '../db.php';

if (!isset($_GET['id'])) {
    header("Location: subjects.php");
    exit();
}

$subjectId = $_GET['id'];
$sql = "SELECT * FROM subjects WHERE id = $subjectId";
$result = $conn->query($sql);
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <?php
    if ($result && $result->num_rows > 0) {
        $subject = $result->fetch_assoc();
        echo "<h1>{$subject['name']} ({$subject['code']})</h1>";
        echo "<p>Status: {$subject['status']}</p>";
        echo "<p>Join Code: {$subject['joincode']}</p>";
    } else {
        echo "Subject not found.";
    }
    ?>

    <!-- Schedules Section (Using tables.html layout) -->
    <div class="mt-4">
        <h2>Schedules</h2>
        <?php
        $sqlSchedules = "SELECT * FROM schedules WHERE subject_id = $subjectId";
        $resultSchedules = $conn->query($sqlSchedules);
        if ($resultSchedules && $resultSchedules->num_rows > 0) {
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-hover" id="schedulesTable" width="100%" cellspacing="0">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Room</th>';
            echo '<th>Day</th>';
            echo '<th>Start Time</th>';
            echo '<th>End Time</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            while ($rowSch = $resultSchedules->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $rowSch['room'] . '</td>';
                echo '<td>' . $rowSch['day'] . '</td>';
                echo '<td>' . $rowSch['start_time'] . '</td>';
                echo '<td>' . $rowSch['end_time'] . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        } else {
            echo "<p>No schedules found for this subject.</p>";
        }
        ?>
    </div>

    <!-- Enrolled Users Section (Using tables.html layout) -->
    <div class="mt-4">
        <h2>Enrolled Users</h2>
        <?php
        $sqlUsers = "SELECT u.* FROM users u JOIN usersubjects us ON u.id = us.user_id WHERE us.subject_id = $subjectId";
        $resultUsers = $conn->query($sqlUsers);
        if ($resultUsers && $resultUsers->num_rows > 0) {
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Name</th>';
            echo '<th>Email</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            while ($rowUser = $resultUsers->fetch_assoc()) {
                $name = $rowUser['firstname'] . ' ' . $rowUser['middle_init'] . ' ' . $rowUser['lastname'];
                echo '<tr>';
                echo '<td>' . $name . '</td>';
                echo '<td>' . $rowUser['email'] . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        } else {
            echo "<p>No users enrolled in this subject.</p>";
        }
        ?>
    </div>
    
</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php
$conn->close();
?>