<?php
include 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $post_id = intval($_POST['post_id']);
    $user_id = $_SESSION['user_id'];
    $content = sanitize($_POST['content']);
    
    $stmt = $conn->prepare("INSERT INTO Comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $content);
    
    if($stmt->execute()) {
        // إرجاع عدد التعليقات الجديد
        $count = $conn->query("SELECT COUNT(*) FROM Comments WHERE post_id = $post_id")->fetch_row()[0];
        echo json_encode(['success' => true, 'new_count' => $count]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'طلب غير صالح']);
?>