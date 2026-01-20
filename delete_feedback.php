<?php
require "db.php";
require "auth.php";
require_role("admin");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only accept POST requests for delete operations (CSRF protection)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid request method.'
    ];
    header('Location: admin_dashboard.php');
    exit;
}

// CSRF token validation (optional but recommended)
// if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
//     $_SESSION['flash_message'] = [
//         'type' => 'error',
//         'message' => 'Security token invalid or expired.'
//     ];
//     header('Location: admin_dashboard.php');
//     exit;
// }

$id = (int) ($_POST['id'] ?? 0);

// Validate input
if ($id <= 0) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid feedback ID provided.'
    ];
    header('Location: admin_dashboard.php');
    exit;
}

try {
    // Begin transaction for data integrity
    $conn->begin_transaction();
    
    // Optional: Log the deletion for audit trail
    if (true) { // Set to false if you don't want logging
        $log_stmt = $conn->prepare("
            INSERT INTO deletion_logs (user_id, feedback_id, admin_name, deleted_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $admin_name = $_SESSION['user']['full_name'] ?? 'Unknown';
        $feedback_owner_id = 0; // You might want to fetch this first
        
        // Get the user_id from the feedback being deleted
        $fetch_stmt = $conn->prepare("SELECT user_id FROM feedback WHERE id = ?");
        $fetch_stmt->bind_param("i", $id);
        $fetch_stmt->execute();
        $fetch_stmt->bind_result($feedback_owner_id);
        $fetch_stmt->fetch();
        $fetch_stmt->close();
        
        if ($feedback_owner_id) {
            $log_stmt->bind_param("iis", $feedback_owner_id, $id, $admin_name);
            $log_stmt->execute();
        }
        $log_stmt->close();
    }
    
    // Delete the feedback
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Feedback deleted successfully.'
        ];
    } else {
        $conn->rollback();
        
        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'message' => 'No feedback found with the provided ID.'
        ];
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Rollback on any error
    if (isset($conn) && $conn) {
        $conn->rollback();
    }
    
    error_log('Delete feedback error: ' . $e->getMessage());
    
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'An error occurred while deleting the feedback. Please try again.'
    ];
}

// Redirect back to dashboard
header('Location: admin_dashboard.php');
exit;