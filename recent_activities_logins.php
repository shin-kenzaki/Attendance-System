<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'admin') {
    // User not logged in or not an admin - redirect to login page
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
require 'db.php';

// Get recent updates from updates table
$recentUpdatesQuery = "SELECT u.id, u.title, u.message, u.timestamp, 
                       usr.firstname, usr.lastname, usr.email 
                       FROM updates u 
                       JOIN users usr ON u.user_id = usr.id 
                       ORDER BY u.timestamp DESC"; // Removed LIMIT
$recentUpdatesResult = $conn->query($recentUpdatesQuery);
$recentUpdates = [];
if ($recentUpdatesResult && $recentUpdatesResult->num_rows > 0) {
    while ($row = $recentUpdatesResult->fetch_assoc()) {
        $recentUpdates[] = $row;
    }
}

// Get statistics for recent logins and activities
$totalLoginsQuery = "SELECT COUNT(*) as total FROM updates WHERE title = 'Login'";
$totalLoginsResult = $conn->query($totalLoginsQuery);
$totalLogins = $totalLoginsResult->fetch_assoc()['total'];

$totalActivitiesQuery = "SELECT COUNT(*) as total FROM updates WHERE title != 'Login'";
$totalActivitiesResult = $conn->query($totalActivitiesQuery);
$totalActivities = $totalActivitiesResult->fetch_assoc()['total'];

// Get statistics for profile updates
$totalProfileUpdatesQuery = "SELECT COUNT(*) as total FROM updates WHERE title = 'Profile Updated'";
$totalProfileUpdatesResult = $conn->query($totalProfileUpdatesQuery);
$totalProfileUpdates = $totalProfileUpdatesResult->fetch_assoc()['total'];

// Get statistics for password resets
$totalPasswordResetsQuery = "SELECT COUNT(*) as total FROM updates WHERE title = 'Password Reset'";
$totalPasswordResetsResult = $conn->query($totalPasswordResetsQuery);
$totalPasswordResets = $totalPasswordResetsResult->fetch_assoc()['total'];
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Screen Description -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Recent Activities and Logins</h1>
            <p class="mb-4">Displaying recent activities and login records with detailed statistics.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <!-- Total Logins Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Logins</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalLogins ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Activities Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Activities</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalActivities ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Profile Updates Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Profile Updates</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalProfileUpdates ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Password Resets Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Password Resets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalPasswordResets ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-key fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Updates -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Updates</h6>
        </div>
        <div class="card-body">
            <?php if (empty($recentUpdates)): ?>
                <p class="text-center">No recent updates found</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead class="text-center">
                            <tr>
                                <th style="width: 5%;">Action</th>
                                <th style="width: 20%;">User</th>
                                <th style="width: 20%;">Email</th>
                                <th style="width: 30%;">Message</th>
                                <th style="width: 10%;">Date</th>
                                <th style="width: 10%;">Time</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUpdates as $update): ?>
                            <tr>
                                <td class="text-center"><?php echo htmlspecialchars($update['title']); ?></td>
                                <td><?php echo htmlspecialchars($update['firstname'] . ' ' . $update['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($update['email']); ?></td>
                                <td><?php echo htmlspecialchars($update['message']); ?></td>
                                <td class="text-center"><?php echo date('M j, Y', strtotime($update['timestamp'])); ?></td>
                                <td class="text-center"><?php echo date('g:i A', strtotime($update['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<?php include 'includes/footer.php'; ?>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script>
$(document).ready(function() {
    $('.table').DataTable({
        order: [[4, 'desc']],
        pageLength: 10,
        responsive: true,
        dom: '<"d-flex align-items-center justify-content-between"<"mr-3"l><"ml-auto"f>>rt<"bottom d-flex align-items-center justify-content-between"ip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search...",
            lengthMenu: "Entries per page: _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        }
    });
});
</script>
