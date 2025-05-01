<?php
include 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'يجب تسجيل الدخول أولاً']);
    exit();
}

$user_id = intval($_GET['user_id']);
$follower_id = $_SESSION['user_id'];

// منع متابعة النفس
if ($user_id == $follower_id) {
    echo json_encode(['success' => false, 'error' => 'لا يمكن متابعة نفسك']);
    exit();
}

// التحقق من وجود المتابعة
$check = $conn->query("SELECT * FROM Followers 
                      WHERE user_id = $user_id 
                      AND follower_id = $follower_id");

if ($check->num_rows > 0) {
    // إلغاء المتابعة
    $conn->query("DELETE FROM Followers 
                WHERE user_id = $user_id 
                AND follower_id = $follower_id");
    $is_following = false;
} else {
    // متابعة
    $conn->query("INSERT INTO Followers (user_id, follower_id) 
                VALUES ($user_id, $follower_id)");
    $is_following = true;
}

// الحصول على العدد الجديد
$new_count = $conn->query("SELECT COUNT(*) FROM Followers 
                          WHERE user_id = $user_id")->fetch_row()[0];

echo json_encode([
    'success' => true,
    'is_following' => $is_following,
    'new_count' => $new_count
]);
?>