<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstnames = $_POST['firstname'];
    $middle_inits = $_POST['middle_init'];
    $lastnames = $_POST['lastname'];
    $emails = $_POST['email'];
    $usertypes = $_POST['usertype'];
    $departments = $_POST['department'];
    $genders = $_POST['gender'];
    
    $stmt = $conn->prepare("INSERT INTO users (firstname, middle_init, lastname, email, password, usertype, department, status, gender, last_update) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $firstname, $middle_init, $lastname, $email, $password_hash, $usertype, $department, $status, $gender, $last_update);
    
    $_SESSION['user_credentials'] = array();
    
    foreach ($firstnames as $index => $firstname) {
        $middle_init = $middle_inits[$index];
        $lastname = $lastnames[$index];
        $email = $emails[$index];
        $usertype = $usertypes[$index];
        $department = $departments[$index];
        $gender = $genders[$index];
        
        // Generate random password
        $auto_password = generateRandomPassword(10);
        $password_hash = password_hash($auto_password, PASSWORD_BCRYPT);
        $status = 'active';
        $last_update = date('Y-m-d H:i:s');
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Log the new user in updates table
            $update_title = "New User Added";
            $update_message = "User $firstname $lastname has been added";
            
            $update_stmt = $conn->prepare("INSERT INTO updates (user_id, title, message, timestamp) VALUES (?, ?, ?, ?)");
            $update_stmt->bind_param("isss", $user_id, $update_title, $update_message, $last_update);
            $update_stmt->execute();
            
            // Store credentials for display
            $_SESSION['user_credentials'][] = array(
                'name' => "$firstname $lastname",
                'email' => $email,
                'password' => $auto_password
            );
        }
    }
    
    $stmt->close();
    $conn->close();
    
    $_SESSION['success'] = true;
    header("Location: users.php");
    exit();
}

function generateRandomPassword($length) {
    $charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    $password = "";
    for ($i = 0; $i < $length; $i++) {
        $password .= $charset[rand(0, strlen($charset) - 1)];
    }
    return $password;
}
?>
