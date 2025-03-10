<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $middle_init = $_POST['middle_init'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $auto_password = $_POST['auto_password'];
    $password = password_hash($auto_password, PASSWORD_BCRYPT);
    $usertype = $_POST['usertype'];
    $department = $_POST['department'];
    $gender = $_POST['gender'];
    $status = 'Active';
    $last_update = date('Y-m-d H:i:s');

    $sql = "INSERT INTO users (firstname, middle_init, lastname, email, password, usertype, department, status, gender, last_update) 
            VALUES ('$firstname', '$middle_init', '$lastname', '$email', '$password', '$usertype', '$department', '$status', '$gender', '$last_update')";

    if ($conn->query($sql) === TRUE) {
        $user_id = $conn->insert_id;
        $update_title = "New User Added";
        $update_message = "User $firstname $lastname has been added with auto-generated password.";
        $update_sql = "INSERT INTO updates (user_id, title, message, timestamp) 
                       VALUES ('$user_id', '$update_title', '$update_message', '$last_update')";
        $conn->query($update_sql);

        // Store credentials in session for SweetAlert
        $_SESSION['success'] = "User added successfully";
        $_SESSION['email'] = $email;
        $_SESSION['password'] = $auto_password;
    } else {
        $_SESSION['error'] = "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
    header("Location: users.php");
    exit();
}
?>
