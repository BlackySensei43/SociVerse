<?php
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $post_id = intval($_POST['post_id']);
    $user_id = $_SESSION['user_id'];
    
    // التحقق من أن المستخدم هو صاحب المنشور
    $check_query = "SELECT user_id FROM Posts WHERE post_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $post = $result->fetch_assoc();
        if ($post['user_id'] == $user_id) {
            // حذف المنشور (حذف ناعم)
            $delete_stmt = $conn->prepare("UPDATE Posts SET is_deleted = 1 WHERE post_id = ?");
            $delete_stmt->bind_param("i", $post_id);
            $delete_stmt->execute();
        }
    }
    
    header("Location: index.php");
    exit();
}
?>