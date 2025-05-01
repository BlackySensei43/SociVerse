<?php
include 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $post_id = intval($_POST['post_id']);
    $user_id = $_SESSION['user_id'];
    
    // التحقق من عدم وجود إعجاب سابق
    $check = $conn->prepare("SELECT * FROM Likes WHERE user_id = ? AND post_id = ?");
    $check->bind_param("ii", $user_id, $post_id);
    $check->execute();
    
    if($check->get_result()->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO Likes (user_id, post_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $post_id);
        $insert->execute();
    } else {
        $delete = $conn->prepare("DELETE FROM Likes WHERE user_id = ? AND post_id = ?");
        $delete->bind_param("ii", $user_id, $post_id);
        $delete->execute();
    }
    
    // الحصول على العدد الجديد
    $count = $conn->query("SELECT COUNT(*) FROM Likes WHERE post_id = $post_id")->fetch_row()[0];
    
    echo json_encode(['success' => true, 'new_count' => $count]);
    exit();
}

echo json_encode(['success' => false]);
?>