<?php
session_start();

// If user is admin, redirect to dashboard instead of showing 404
if (isset($_SESSION['user_id']) && $_SESSION['usertype'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

// If user is logged in, direct them to their respective dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['usertype'] == 'admin') {
        $dashboard_link = "dashboard.php";
        $link_text = "Dashboard";
    } elseif ($_SESSION['usertype'] == 'faculty') {
        $dashboard_link = "FacultyDashboard.php";
        $link_text = "Faculty Dashboard";
    } elseif ($_SESSION['usertype'] == 'student') {
        $dashboard_link = "StudentDashboard.php";
        $link_text = "Student Dashboard";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>404 - Page Not Found</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid mt-5">

                    <!-- 404 Error Text -->
                    <div class="text-center">
                        <div class="error mx-auto" data-text="404">404</div>
                        <p class="lead text-gray-800 mb-5">Page Not Found</p>
                        <p class="text-gray-500 mb-0">It looks like you found a glitch in the matrix...</p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['usertype'] === 'student'): ?>
                                <a href="StudentDashboard.php">&larr; Back to Dashboard</a>
                            <?php elseif ($_SESSION['usertype'] === 'faculty'): ?>
                                <a href="FacultyDashboard.php">&larr; Back to Dashboard</a>
                            <?php else: ?>
                                <a href="index.php">&larr; Back to Home</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="index.php">&larr; Back to Home</a>
                        <?php endif; ?>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2023</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
</body>

</html>