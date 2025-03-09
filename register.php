<?php
// Include database connection
require '../db.php';

// Initialize variables for form fields and error messages
$firstname = $middle_init = $lastname = $email = $usertype = $department = $gender = '';
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $firstname = trim($_POST['firstname']);
    $middle_init = trim($_POST['middle_init']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $usertype = $_POST['usertype'];
    $department = $_POST['department'];
    $gender = $_POST['gender'];
    $password = $_POST['password'];
    $repeat_password = $_POST['repeat_password'];
    
    // Validate input
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($usertype) || empty($department) || empty($gender)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password != $repeat_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set default status to active
            $status = 'active';
            
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (firstname, middle_init, lastname, email, password, usertype, department, status, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $firstname, $middle_init, $lastname, $email, $hashed_password, $usertype, $department, $status, $gender);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Redirecting to login...";
                // Redirect to login page after 2 seconds
                header("Refresh: 2; url=index.php");
            } else {
                $error = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        }
        $check_email->close();
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

    <title>SB Admin 2 - Register</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <!-- Nested Row within Card Body -->
                <div class="row">
                    <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                    <div class="col-lg-7">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Create an Account!</h1>
                            </div>
                            
                            <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                            <?php endif; ?>
                            
                            <form class="user" method="POST" action="">
                                <div class="form-group row">
                                    <div class="col-sm-5 mb-3 mb-sm-0">
                                        <input type="text" class="form-control form-control-user" id="exampleFirstName"
                                            name="firstname" placeholder="First Name" value="<?php echo htmlspecialchars($firstname); ?>">
                                    </div>
                                    <div class="col-sm-2">
                                        <input type="text" class="form-control form-control-user" id="exampleMiddleInitial"
                                            name="middle_init" placeholder="M.I." value="<?php echo htmlspecialchars($middle_init); ?>">
                                    </div>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control form-control-user" id="exampleLastName"
                                            name="lastname" placeholder="Last Name" value="<?php echo htmlspecialchars($lastname); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="email" class="form-control form-control-user" id="exampleInputEmail"
                                        name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-4 mb-3 mb-sm-0">
                                        <select class="form-control form-control-user" id="exampleUsertype" name="usertype">
                                            <option value="" disabled selected>Select User Type</option>
                                            <option value="student" <?php echo ($usertype == 'student') ? 'selected' : ''; ?>>Student</option>
                                            <option value="faculty" <?php echo ($usertype == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                                            <option value="admin" <?php echo ($usertype == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <select class="form-control form-control-user" id="exampleDepartment" name="department">
                                            <option value="" disabled selected>Select Department</option>
                                            <option value="bscs" <?php echo ($department == 'bscs') ? 'selected' : ''; ?>>BSCS</option>
                                            <option value="bsais" <?php echo ($department == 'bsais') ? 'selected' : ''; ?>>BSAIS</option>
                                            <option value="bsa" <?php echo ($department == 'bsa') ? 'selected' : ''; ?>>BSA</option>
                                            <option value="bse" <?php echo ($department == 'bse') ? 'selected' : ''; ?>>BSE</option>
                                            <option value="bstm" <?php echo ($department == 'bstm') ? 'selected' : ''; ?>>BSTM</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <select class="form-control form-control-user" id="exampleGender" name="gender">
                                            <option value="" disabled selected>Select Gender</option>
                                            <option value="male" <?php echo ($gender == 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($gender == 'female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo ($gender == 'other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="password" class="form-control form-control-user"
                                            name="password" id="exampleInputPassword" placeholder="Password">
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="password" class="form-control form-control-user"
                                            name="repeat_password" id="exampleRepeatPassword" placeholder="Repeat Password">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-user btn-block">
                                    Register Account
                                </button>
                                <hr>
                                <a href="index.html" class="btn btn-google btn-user btn-block">
                                    <i class="fab fa-google fa-fw"></i> Register with Google
                                </a>
                                <a href="index.html" class="btn btn-facebook btn-user btn-block">
                                    <i class="fab fa-facebook-f fa-fw"></i> Register with Facebook
                                </a>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a class="small" href="forgot-password.html">Forgot Password?</a>
                            </div>
                            <div class="text-center">
                                <a class="small" href="index.php">Already have an account? Login!</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>