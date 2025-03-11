<?php
session_start();
require 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First log the deletion in updates table
        $user_query = "SELECT firstname, lastname FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            $update_title = "User Deleted";
            $update_message = "User {$user['firstname']} {$user['lastname']} has been deleted";
            $timestamp = date('Y-m-d H:i:s');
            
            $update_stmt = $conn->prepare("INSERT INTO updates (user_id, title, message, timestamp) VALUES (?, ?, ?, ?)");
            $update_stmt->bind_param("isss", $_SESSION['user_id'], $update_title, $update_message, $timestamp);
            $update_stmt->execute();
            
            // Then delete the user
            $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->bind_param("i", $user_id);
            
            if ($delete_stmt->execute()) {
                $conn->commit();
                $_SESSION['success_message'] = "User has been successfully deleted.";
            } else {
                throw new Exception("Failed to delete user");
            }
        } else {
            throw new Exception("User not found");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    $conn->close();
    header("Location: users.php");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid request";
    header("Location: users.php");
    exit();
}