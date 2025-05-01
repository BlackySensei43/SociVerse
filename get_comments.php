<?php
include 'includes/config.php';

if(isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
    
    // تسجيل للتصحيح
    error_log("Fetching comments for post: $post_id");
    
    $stmt = $conn->prepare("SELECT 
                               c.comment_id as id, 
                               c.content,
                               c.created_at,
                               c.user_id,
                               c.post_id,
                               u.username, 
                               u.profile_pic 
                           FROM Comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.post_id = ? 
                           ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while($comment = $result->fetch_assoc()) {
            $isCommentOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id'];
            echo '<div class="d-flex mb-3" id="comment-'.$comment['id'].'">
                    <img src="uploads/'.$comment['profile_pic'].'" 
                         class="rounded-circle me-2" 
                         width="32" height="32">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">'.htmlspecialchars($comment['username']).'</h6>
                        <p class="mb-1" id="comment-content-'.$comment['id'].'">'.htmlspecialchars($comment['content']).'</p>
                        <small class="text-muted">'.date('Y-m-d H:i', strtotime($comment['created_at'])).'</small>';
            
            if ($isCommentOwner) {
                echo '<div class="mt-1">
                        <button onclick="editComment('.$comment['id'].')" class="btn btn-sm btn-outline-primary">تعديل</button>
                        <button onclick="deleteComment('.$comment['id'].')" class="btn btn-sm btn-outline-danger">حذف</button>
                      </div>';
            }
            
            echo '</div></div>';
        }
    } else {
        echo '<p class="text-muted text-center">لا توجد تعليقات بعد</p>';
    }
} else {
    echo '<p class="text-danger text-center">خطأ في طلب التعليقات</p>';
}
?>