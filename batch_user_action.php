<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'admin') {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userIds = $_POST['user_ids'];
    
    if (!is_array($userIds)) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid user selection']));
    }

    // Convert array to comma-separated string for SQL
    $userIdStr = implode(',', array_map('intval', $userIds));
    
    try {
        switch($action) {
            case 'activate':
                $sql = "UPDATE users SET status = 'active' WHERE id IN ($userIdStr)";
                $message = 'Users activated successfully';
                break;
                
            case 'deactivate':
                $sql = "UPDATE users SET status = 'inactive' WHERE id IN ($userIdStr)";
                $message = 'Users deactivated successfully';
                break;
                
            case 'delete':
                // First check if any of these users are assigned to subjects
                $checkSql = "SELECT COUNT(*) as count FROM usersubjects WHERE user_id IN ($userIdStr)";
                $checkResult = $conn->query($checkSql);
                $row = $checkResult->fetch_assoc();
                
                if ($row['count'] > 0) {
                    die(json_encode([
                        'status' => 'error', 
                        'message' => 'Some users cannot be deleted because they are enrolled in subjects'
                    ]));
                }
                
                $sql = "DELETE FROM users WHERE id IN ($userIdStr)";
                $message = 'Users deleted successfully';
                break;
                
            default:
                die(json_encode(['status' => 'error', 'message' => 'Invalid action']));
        }
        
        if ($conn->query($sql)) {
            // Log the action
            $admin_id = $_SESSION['user_id'];
            $action_desc = ucfirst($action) . "d users: " . $userIdStr;
            $log_sql = "INSERT INTO updates (user_id, title, message, timestamp) 
                       VALUES ($admin_id, 'Batch User Action', '$action_desc', NOW())";
            $conn->query($log_sql);
            
            echo json_encode(['status' => 'success', 'message' => $message]);
        } else {
            throw new Exception($conn->error);
        }
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
