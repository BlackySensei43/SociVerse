<?php
function check_new_messages() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) as unread_count FROM Messages WHERE receiver_id = ? AND is_read = 0";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['unread_count'] > 0;
    } catch (Exception $e) {
        error_log("Error checking messages: " . $e->getMessage());
        return false;
    }
}

function get_like_count($post_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Likes WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function get_comments($post_id) {
    global $conn;
    $html = '';
    $stmt = $conn->prepare("SELECT * FROM Comments WHERE post_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($comment = $result->fetch_assoc()) {
        $html .= '<div class="card p-2 mb-2 small">';
        $html .= '<strong>'.sanitize($comment['username']).'</strong>: ';
        $html .= sanitize($comment['content']);
        $html .= '</div>';
    }
    
    return $html;
}

function countComments($post_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Comments WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function displayComments($post_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT c.*, u.username, u.profile_pic 
                          FROM Comments c 
                          JOIN users u ON c.user_id = u.id 
                          WHERE c.post_id = ? 
                          ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $comments = $stmt->get_result();
    
    if ($comments->num_rows > 0) {
        while($comment = $comments->fetch_assoc()) {
            echo '<div class="d-flex mb-3">
                    <img src="uploads/'.$comment['profile_pic'].'" 
                         class="rounded-circle me-2" 
                         width="32" height="32">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">'.sanitize($comment['username']).'</h6>
                        <p class="mb-1">'.sanitize($comment['content']).'</p>
                        <small class="text-muted">'.time_elapsed_string($comment['created_at']).'</small>
                    </div>
                  </div>';
        }
    } else {
        echo '<p class="text-muted text-center">لا توجد تعليقات بعد</p>';
    }
}

function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d > 7) {
        return date('Y-m-d', strtotime($datetime));
    } elseif ($diff->d > 0) {
        return $diff->d . ' يوم مضى';
    } elseif ($diff->h > 0) {
        return $diff->h . ' ساعة مضت';
    } elseif ($diff->i > 0) {
        return $diff->i . ' دقيقة مضت';
    } else {
        return 'الآن';
    }
}

function sendNotification($user_id, $sender_id, $type, $content) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO notifications 
                              (user_id, sender_id, type, content) 
                              VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $sender_id, $type, $content);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

?>