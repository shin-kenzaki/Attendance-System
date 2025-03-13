<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>
        <?php
        if ($_SESSION['usertype'] === 'admin') {
            echo "Admin Dashboard - Attendance System";
        } elseif ($_SESSION['usertype'] === 'faculty') {
            echo "Faculty Portal - Attendance System";
        } elseif ($_SESSION['usertype'] === 'student') {
            echo "Student Dashboard - Attendance System";
        } else {
            echo "Attendance System";
        }
        ?>
    </title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .bg-gradient-faculty {
            background-color: #343a40;
            background-image: linear-gradient(180deg, #343a40 10%, #1a1d20 100%);
            background-size: cover;
        }
        .sidebar-faculty .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .sidebar-faculty .nav-item .nav-link:hover {
            color: #fff !important;
        }
    </style>
    
    <?php if ($_SESSION['usertype'] === 'student'): ?>
    <style>
        /* Keeping student-specific styles if needed for other elements */
        .bg-gradient-student {
            background-color: #2c7be5;
            background-image: linear-gradient(180deg, #2c7be5 10%, #1657af 100%);
            background-size: cover;
        }
    </style>
    <?php endif; ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-faculty sidebar-faculty sidebar sidebar-dark accordion" id="accordionSidebar">
            
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="
                <?php
                if ($_SESSION['usertype'] === 'admin') {
                    echo 'dashboard.php';
                } elseif ($_SESSION['usertype'] === 'faculty') {
                    echo 'FacultyDashboard.php';
                } elseif ($_SESSION['usertype'] === 'student') {
                    echo 'StudentDashboard.php';
                } else {
                    echo 'index.php';
                }
                ?>">
                <div class="sidebar-brand-icon rotate-n-15">
                    <?php if ($_SESSION['usertype'] === 'admin'): ?>
                        <i class="fas fa-user-shield"></i>
                    <?php elseif ($_SESSION['usertype'] === 'faculty'): ?>
                        <i class="fas fa-chalkboard-teacher"></i>
                    <?php elseif ($_SESSION['usertype'] === 'student'): ?>
                        <i class="fas fa-user-graduate"></i>
                    <?php else: ?>
                        <i class="fas fa-user-check"></i>
                    <?php endif; ?>
                </div>
                <div class="sidebar-brand-text mx-3">
                    <?php
                    if ($_SESSION['usertype'] === 'admin') {
                        echo 'Admin Portal';
                    } elseif ($_SESSION['usertype'] === 'faculty') {
                        echo 'Faculty Portal';
                    } elseif ($_SESSION['usertype'] === 'student') {
                        echo 'Student Portal';
                    } else {
                        echo 'Attendance System';
                    }
                    ?>
                </div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Dashboard Link -->
            <li class="nav-item active">
                <a class="nav-link" href="<?php 
                    if ($_SESSION['usertype'] === 'admin') {
                        echo 'dashboard.php';
                    } elseif ($_SESSION['usertype'] === 'faculty') {
                        echo 'FacultyDashboard.php';
                    } elseif ($_SESSION['usertype'] === 'student') {
                        echo 'StudentDashboard.php';
                    }
                ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>
                        <?php
                        if ($_SESSION['usertype'] === 'admin') {
                            echo 'Admin Dashboard';
                        } elseif ($_SESSION['usertype'] === 'faculty') {
                            echo 'Faculty Dashboard';
                        } elseif ($_SESSION['usertype'] === 'student') {
                            echo 'Student Dashboard';
                        }
                        ?>
                    </span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- ADMIN NAVIGATION -->
            <?php if ($_SESSION['usertype'] === 'admin'): ?>
                <!-- Heading -->
                <div class="sidebar-heading">
                    Administration
                </div>

                <!-- Admin Nav Items -->
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-fw fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="subjects.php">
                        <i class="fas fa-fw fa-book"></i>
                        <span>Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="attendances.php">
                        <i class="fas fa-fw fa-calendar-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-fw fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="recent_activities_logins.php">
                        <i class="fas fa-fw fa-history"></i>
                        <span>Recent Activities & Logins</span>
                    </a>
                </li>
                
                <!-- Divider -->
                <hr class="sidebar-divider">
                
                <!-- Heading -->
                <div class="sidebar-heading">
                    Interface
                </div>
                
                <!-- Nav Item - Components Collapse Menu -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseComponents"
                        aria-expanded="true" aria-controls="collapseComponents">
                        <i class="fas fa-fw fa-cog"></i>
                        <span>Components</span>
                    </a>
                    <div id="collapseComponents" class="collapse" aria-labelledby="headingComponents" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Custom Components:</h6>
                            <a class="collapse-item" href="buttons.html">Buttons</a>
                            <a class="collapse-item" href="cards.html">Cards</a>
                        </div>
                    </div>
                </li>
                
                <!-- Nav Item - Utilities Collapse Menu -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities"
                        aria-expanded="true" aria-controls="collapseUtilities">
                        <i class="fas fa-fw fa-wrench"></i>
                        <span>Utilities</span>
                    </a>
                    <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Custom Utilities:</h6>
                            <a class="collapse-item" href="utilities-color.html">Colors</a>
                            <a class="collapse-item" href="utilities-border.html">Borders</a>
                            <a class="collapse-item" href="utilities-animation.html">Animations</a>
                            <a class="collapse-item" href="utilities-other.html">Other</a>
                        </div>
                    </div>
                </li>
                
                <!-- Divider -->
                <hr class="sidebar-divider">
                
                <!-- Heading -->
                <div class="sidebar-heading">
                    Addons
                </div>
                
                <!-- Nav Item - Pages Collapse Menu -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages"
                        aria-expanded="true" aria-controls="collapsePages">
                        <i class="fas fa-fw fa-folder"></i>
                        <span>Pages</span>
                    </a>
                    <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Login Screens:</h6>
                            <a class="collapse-item" href="login.html">Login</a>
                            <a class="collapse-item" href="register.html">Register</a>
                            <a class="collapse-item" href="forgot-password.html">Forgot Password</a>
                            <div class="collapse-divider"></div>
                            <h6 class="collapse-header">Other Pages:</h6>
                            <a class="collapse-item" href="blank.html">Blank Page</a>
                        </div>
                    </div>
                </li>
                
                <!-- Nav Item - Charts -->
                <li class="nav-item">
                    <a class="nav-link" href="charts.html">
                        <i class="fas fa-fw fa-chart-area"></i>
                        <span>Charts</span>
                    </a>
                </li>
                
                <!-- Nav Item - Tables -->
                <li class="nav-item">
                    <a class="nav-link" href="tables.html">
                        <i class="fas fa-fw fa-table"></i>
                        <span>Tables</span>
                    </a>
                </li>

            <!-- FACULTY NAVIGATION -->
            <?php elseif ($_SESSION['usertype'] === 'faculty'): ?>
                <!-- Heading -->
                <div class="sidebar-heading">
                    Management
                </div>

                <!-- Faculty Nav Items -->
                <li class="nav-item">
                    <a class="nav-link" href="faculty_subjects.php">
                        <i class="fas fa-fw fa-book"></i>
                        <span>Subjects</span></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="faculty_attendances.php">
                        <i class="fas fa-fw fa-clipboard-list"></i>
                        <span>Attendances</span></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="faculty_students.php">
                        <i class="fas fa-fw fa-user-graduate"></i>
                        <span>Students</span></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="faculty_schedules.php">
                        <i class="fas fa-fw fa-calendar"></i>
                        <span>My Schedule</span></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="faculty_reports.php">
                        <i class="fas fa-fw fa-chart-bar"></i>
                        <span>Reports</span></a>
                </li>

                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Heading -->
                <div class="sidebar-heading">
                    Communication
                </div>

                <li class="nav-item">
                    <a class="nav-link" href="announcements.php">
                        <i class="fas fa-fw fa-bullhorn"></i>
                        <span>Announcements</span></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="messages.php">
                        <i class="fas fa-fw fa-envelope"></i>
                        <span>Messages</span></a>
                </li>

            <!-- STUDENT NAVIGATION -->
            <?php elseif ($_SESSION['usertype'] === 'student'): ?>
                <!-- Heading -->
                <div class="sidebar-heading">
                    Academic
                </div>

                <!-- Student Nav Items -->
                <li class="nav-item">
                    <a class="nav-link" href="student_subjects.php">
                        <i class="fas fa-fw fa-book"></i>
                        <span>My Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="student_attendances.php">
                        <i class="fas fa-fw fa-calendar-check"></i>
                        <span>My Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="student_schedules.php">
                        <i class="fas fa-fw fa-calendar"></i>
                        <span>Class Schedule</span>
                    </a>
                </li>

                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Heading -->
                <div class="sidebar-heading">
                    Information
                </div>

                <li class="nav-item">
                    <a class="nav-link" href="announcements.php">
                        <i class="fas fa-fw fa-bullhorn"></i>
                        <span>Announcements</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-fw fa-chart-line"></i>
                        <span>Progress Reports</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form
                        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn 
                                <?php if ($_SESSION['usertype'] === 'faculty'): ?>
                                    btn-dark
                                <?php elseif ($_SESSION['usertype'] === 'student'): ?>
                                    btn-info
                                <?php else: ?>
                                    btn-primary
                                <?php endif; ?>
                                " type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <!-- Role Indicator Badge -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <span class="nav-link">
                                <span class="badge 
                                <?php if ($_SESSION['usertype'] === 'admin'): ?>
                                    badge-danger
                                <?php elseif ($_SESSION['usertype'] === 'faculty'): ?>
                                    badge-dark
                                <?php elseif ($_SESSION['usertype'] === 'student'): ?>
                                    badge-info
                                <?php endif; ?>
                                "><?php echo ucfirst($_SESSION['usertype']); ?></span>
                            </span>
                        </li>

                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header
                                <?php if ($_SESSION['usertype'] === 'faculty'): ?>
                                    bg-dark
                                <?php elseif ($_SESSION['usertype'] === 'student'): ?>
                                    bg-info
                                <?php else: ?>
                                    bg-primary
                                <?php endif; ?>
                                ">
                                    Alerts Center
                                </h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle 
                                        <?php if ($_SESSION['usertype'] === 'faculty'): ?>
                                            bg-dark
                                        <?php elseif ($_SESSION['usertype'] === 'student'): ?>
                                            bg-info
                                        <?php else: ?>
                                            bg-primary
                                        <?php endif; ?>
                                        ">
                                            <i class="fas fa-file-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">March 8, 2025</div>
                                        <span class="font-weight-bold">New attendance reports are available!</span>
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                            </div>
                        </li>

                        <!-- Nav Item - Messages -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <!-- Counter - Messages -->
                                <span class="badge badge-danger badge-counter">7</span>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="messagesDropdown">
                                <h6 class="dropdown-header
                                <?php if ($_SESSION['usertype'] === 'faculty'): ?>
                                    bg-dark
                                <?php elseif ($_SESSION['usertype'] === 'student'): ?>
                                    bg-info
                                <?php else: ?>
                                    bg-primary
                                <?php endif; ?>
                                ">
                                    Message Center
                                </h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="img/undraw_profile_1.svg"
                                            alt="...">
                                        <div class="status-indicator bg-success"></div>
                                    </div>
                                    <div class="font-weight-bold">
                                        <div class="text-truncate">Hi there! I am wondering if you can help me with attendance issues.</div>
                                        <div class="small text-gray-500">Emily Fowler · 58m</div>
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Read More Messages</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php 
                                    if (isset($_SESSION['firstname']) && isset($_SESSION['middle_init']) && isset($_SESSION['lastname'])) {
                                        echo $_SESSION['firstname'].' '.$_SESSION['middle_init'].' '.$_SESSION['lastname'];
                                    } else {
                                        echo 'User';
                                    }
                                    ?>
                                    <br>
                                    <small><?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?></small>
                                </span>
                                <img class="img-profile rounded-circle"
                                    src="<?php echo empty($_SESSION['image']) ? 
                                        (isset($_SESSION['gender']) && strtolower($_SESSION['gender']) === 'female' ? 
                                            'img/undraw_profile_1.svg' : 'img/undraw_profile.svg') : $_SESSION['image']; ?>"
                                    style="width: 32px; height: 32px; object-fit: cover;">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->