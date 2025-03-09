<?php
session_start();
require('db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle profile data update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reset_profile']) && !isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check if this is just an image upload
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === 0 && !isset($_POST['firstName'])) {
        $file = $_FILES['profileImage'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file['type'], $allowed) && $file['size'] <= $max_size) {
            $upload_dir = 'profile_img/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $target = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $image_path = 'profile_img/' . $filename;
                
                // Update only the image field
                $sql = "UPDATE users SET image=?, last_update=CURRENT_TIMESTAMP WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $image_path, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['image'] = $image_path;
                    $_SESSION['success_msg'] = "Profile picture updated successfully!";
                    
                    // Create update record
                    $update_title = "Profile Picture Updated";
                    $update_message = "User profile picture was updated";
                    $insert_update_sql = "INSERT INTO updates (user_id, title, message, timestamp) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
                    $insert_update_stmt = $conn->prepare($insert_update_sql);
                    $insert_update_stmt->bind_param("iss", $user_id, $update_title, $update_message);
                    $insert_update_stmt->execute();
                } else {
                    $_SESSION['error_msg'] = "Error updating profile picture!";
                }
            }
        } else {
            $_SESSION['error_msg'] = "Invalid file type or size!";
        }
        
        header('Location: profile.php');
        exit();
    }
    
    // If not just an image upload, handle full profile update
    $firstname = $_POST['firstName'];
    $middle_init = $_POST['middleInit'];
    $lastname = $_POST['lastName'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    
    // Handle image upload if present
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === 0) {
        $file = $_FILES['profileImage'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file['type'], $allowed) && $file['size'] <= $max_size) {
            $upload_dir = 'profile_img/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $target = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $image_path = 'profile_img/' . $filename;
            }
        }
    }
    
    // Update user data
    if (isset($image_path)) {
        $sql = "UPDATE users SET firstname=?, middle_init=?, lastname=?, email=?, department=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $firstname, $middle_init, $lastname, $email, $department, $image_path, $user_id);
    } else {
        $sql = "UPDATE users SET firstname=?, middle_init=?, lastname=?, email=?, department=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $firstname, $middle_init, $lastname, $email, $department, $user_id);
    }
    
    if ($stmt->execute()) {
        // Update last_update timestamp
        $update_time_sql = "UPDATE users SET last_update = CURRENT_TIMESTAMP WHERE id = ?";
        $update_time_stmt = $conn->prepare($update_time_sql);
        $update_time_stmt->bind_param("i", $user_id);
        $update_time_stmt->execute();

        // Create update record
        $update_title = "Profile Updated";
        $update_message = "User profile information was updated";
        $insert_update_sql = "INSERT INTO updates (user_id, title, message, timestamp) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $insert_update_stmt = $conn->prepare($insert_update_sql);
        $insert_update_stmt->bind_param("iss", $user_id, $update_title, $update_message);
        $insert_update_stmt->execute();

        $_SESSION['success_msg'] = "Profile updated successfully!";
        if (isset($image_path)) {
            $_SESSION['image'] = $image_path;
        }
    } else {
        $_SESSION['error_msg'] = "Error updating profile!";
    }
    
    header('Location: profile.php');
    exit();
}

// Add reset profile picture functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_profile'])) {
    $user_id = $_SESSION['user_id'];
    
    // Choose default image based on gender
    if (strtolower($_SESSION['gender']) === 'female') {
        $default_image = rand(0, 1) ? "profile_img/undraw_profile_1.svg" : "profile_img/undraw_profile_3.svg";
    } else {
        $default_image = rand(0, 1) ? "profile_img/undraw_profile.svg" : "profile_img/undraw_profile_2.svg";
    }
    
    $sql = "UPDATE users SET image=?, last_update=CURRENT_TIMESTAMP WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $default_image, $user_id);
    
    if ($stmt->execute()) {
        // Create update record
        $update_title = "Profile Picture Reset";
        $update_message = "User profile picture was reset to default";
        $insert_update_sql = "INSERT INTO updates (user_id, title, message, timestamp) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $insert_update_stmt = $conn->prepare($insert_update_sql);
        $insert_update_stmt->bind_param("iss", $user_id, $update_title, $update_message);
        $insert_update_stmt->execute();

        $_SESSION['image'] = $default_image;
        $_SESSION['success_msg'] = "Profile picture reset to default!";
    } else {
        $_SESSION['error_msg'] = "Error resetting profile picture!";
    }
    
    header('Location: profile.php');
    exit();
}

// Add password change handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_msg'] = "Password changed successfully!";
            } else {
                $_SESSION['error_msg'] = "Error changing password!";
            }
        } else {
            $_SESSION['error_msg'] = "New passwords do not match!";
        }
    } else {
        $_SESSION['error_msg'] = "Current password is incorrect!";
    }
    
    header('Location: profile.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Update session with latest user data
    $_SESSION['firstname'] = $user['firstname'];
    $_SESSION['middle_init'] = $user['middle_init'];
    $_SESSION['lastname'] = $user['lastname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['department'] = $user['department'];
    $_SESSION['image'] = $user['image'];
    $_SESSION['gender'] = $user['gender'];
    $_SESSION['last_login'] = $user['last_login'];
    $_SESSION['last_update'] = $user['last_update'];
} else {
    $_SESSION['error_msg'] = "Error fetching user data!";
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Profile</h1>

                    <div class="row">
                        <div class="col-xl-4">
                            <!-- Profile picture card-->
                            <div class="card mb-4 mb-xl-0">
                                <div class="card-header">Profile Picture</div>
                                <div class="card-body text-center">
                                    <img class="img-account-profile rounded-circle mb-2" 
                                         src="<?php echo empty($_SESSION['image']) ? 
                                            (isset($_SESSION['gender']) && strtolower($_SESSION['gender']) === 'female' ? 
                                                'img/undraw_profile_1.svg' : 'img/undraw_profile.svg') : $_SESSION['image']; ?>" 
                                         alt="User Image" 
                                         style="width: 180px; height: 180px; object-fit: cover;">
                                    <div class="small font-italic text-muted mb-4">JPG or PNG no larger than 5 MB</div>
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Reset Profile Picture button -->
                                        <form method="POST" class="mb-3 mr-2" id="resetForm">
                                            <button type="button" name="reset_profile" class="btn btn-secondary btn-sm" id="resetProfileBtn">
                                                Reset Profile Picture
                                            </button>
                                            <input type="hidden" name="reset_profile" value="1">
                                        </form>
                                        <!-- Upload New Image button -->
                                        <form method="post" enctype="multipart/form-data" id="uploadForm" class="mb-3">
                                            <input type="file" name="profileImage" id="profileImageUpload" class="d-none" accept="image/*">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('profileImageUpload').click();">
                                                Upload New Image
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-8">
                            <!-- Account details card-->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <!-- Nav tabs -->
                                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#profile">Profile Details</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#security">Security</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($_SESSION['success_msg'])): ?>
                                        <div class="alert alert-success"><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                                    <?php endif; ?>
                                    <?php if(isset($_SESSION['error_msg'])): ?>
                                        <div class="alert alert-danger"><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                                    <?php endif; ?>

                                    <!-- Tab Content -->
                                    <div class="tab-content">
                                        <!-- Profile Tab -->
                                        <div class="tab-pane fade show active" id="profile">
                                            <h5 class="card-title mb-4">Profile Details</h5>
                                            <form method="post" enctype="multipart/form-data">
                                                <!-- Form Row -->
                                                <div class="row gx-3 mb-3">
                                                    <div class="col-md-4">
                                                        <label class="small mb-1" for="firstName">First name</label>
                                                        <input class="form-control" id="firstName" type="text" name="firstName" value="<?php echo $_SESSION['firstname']; ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="small mb-1" for="middleInit">Middle Initial</label>
                                                        <input class="form-control" id="middleInit" type="text" name="middleInit" value="<?php echo $_SESSION['middle_init']; ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="small mb-1" for="lastName">Last name</label>
                                                        <input class="form-control" id="lastName" type="text" name="lastName" value="<?php echo $_SESSION['lastname']; ?>" required>
                                                    </div>
                                                </div>
                                                <!-- Form Group (email address)-->
                                                <div class="mb-3">
                                                    <label class="small mb-1" for="email">Email address</label>
                                                    <input class="form-control" id="email" type="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>
                                                </div>
                                                <!-- Form Group (department)-->
                                                <div class="mb-3">
                                                    <label class="small mb-1" for="department">Department</label>
                                                    <select class="form-control" id="department" name="department" required>
                                                        <?php
                                                        $departments = ['BSCS', 'BSAIS', 'BSA', 'BSE', 'BSTM'];
                                                        foreach ($departments as $dept) {
                                                            $selected = (strtoupper($_SESSION['department']) === $dept) ? 'selected' : '';
                                                            echo "<option value=\"$dept\" $selected>$dept</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <button class="btn btn-primary" type="submit">Save changes</button>

                                                <hr class="my-4">

                                                <!-- Last Update Information -->
                                                <div class="small text-muted mb-3">
                                                    Last update: <?php echo isset($_SESSION['last_update']) ? date('F j, Y g:i a', strtotime($_SESSION['last_update'])) : 'Never'; ?>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Security Tab -->
                                        <div class="tab-pane fade" id="security">
                                            <h5 class="card-title mb-4">Change Password</h5>
                                            <form method="POST">
                                                <div class="mb-3">
                                                    <label class="small mb-1" for="currentPassword">Current Password</label>
                                                    <input class="form-control" id="currentPassword" type="password" name="current_password" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small mb-1" for="newPassword">New Password</label>
                                                    <input class="form-control" id="newPassword" type="password" name="new_password" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small mb-1" for="confirmPassword">Confirm New Password</label>
                                                    <input class="form-control" id="confirmPassword" type="password" name="confirm_password" required>
                                                </div>
                                                <button class="btn btn-primary" type="submit" name="change_password">Change Password</button>
                                            </form>

                                            <hr class="my-4">

                                            <!-- Last Login Information -->
                                            <div class="small text-muted">
                                                Last login: <?php echo isset($_SESSION['last_login']) ? date('F j, Y g:i a', strtotime($_SESSION['last_login'])) : 'Never'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <script>
    // Auto-submit when image is selected
    document.getElementById('profileImageUpload').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            // Show SweetAlert loading indicator
            Swal.fire({
                title: 'Uploading...',
                html: 'Updating your profile picture',
                didOpen: () => {
                    Swal.showLoading();
                },
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
            
            // Submit the form immediately
            document.getElementById('uploadForm').submit();
        }
    });

    // Handle reset profile picture
    document.getElementById('resetProfileBtn').addEventListener('click', function() {
        // Show SweetAlert loading indicator
        Swal.fire({
            title: 'Resetting...',
            html: 'Restoring default profile picture',
            didOpen: () => {
                Swal.showLoading();
            },
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
        
        // Submit the form immediately
        document.getElementById('resetForm').submit();
    });
    </script>
</body>

</html>