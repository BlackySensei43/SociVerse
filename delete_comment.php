<?php
include 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $comment_id = intval($_POST['comment_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if user owns the comment or is admin
    $stmt = $conn->prepare("SELECT post_id FROM Comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $post_id = $result->fetch_assoc()['post_id'];
        
        // Delete the comment
        $delete_stmt = $conn->prepare("DELETE FROM Comments WHERE id = ?");
        $delete_stmt->bind_param("i", $comment_id);
        
        if ($delete_stmt->execute()) {
            // Get new comment count
            $count = $conn->query("SELECT COUNT(*) FROM Comments WHERE post_id = $post_id")->fetch_row()[0];
            echo json_encode(['success' => true, 'new_count' => $count]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'error' => 'غير مصرح بحذف التعليق']);