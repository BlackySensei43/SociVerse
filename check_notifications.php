<?php
session_start();
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'notifications' => []];

try {
    $stmt = $conn->prepare("SELECT n.*, u.username as sender_name, u.profile_pic as sender_pic 
                           FROM notifications n 
                           JOIN users u ON n.sender_id = u.id 
                           WHERE n.user_id = ? AND n.is_read = 0 
                           ORDER BY n.created_at DESC 
                           LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    $unread = 0;
    
    while ($row = $result->fetch_assoc()) {
        $row['time_ago'] = timeAgo($row['created_at']);
        $notifications[] = $row;
        $unread++;
    }
    
    $response = [
        'success' => true,
        'unread' => $unread,
        'notifications' => $notifications
    ];
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "الآن";
    if ($diff < 3600) return floor($diff/60) . " دقيقة";
    if ($diff < 86400) return floor($diff/3600) . " ساعة";
    return floor($diff/86400) . " يوم";
}
?>