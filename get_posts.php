<?php
include 'includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $posts_query = "SELECT p.*, u.username, u.profile_pic, u.id as user_id,
                    (SELECT COUNT(*) FROM Likes WHERE post_id = p.post_id) as likes_count,
                    (SELECT COUNT(*) FROM Comments WHERE post_id = p.post_id) as comments_count
                    FROM Posts p
                    INNER JOIN users u ON p.user_id = u.id
                    WHERE p.is_deleted = 0
                    ORDER BY p.created_at DESC
                    LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($posts_query);
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($post = $result->fetch_assoc()) {
        
        $post['time_ago'] = time_elapsed_string($post['created_at']);
        $post['is_liked'] = false;
        
        if (isset($_SESSION['user_id'])) {
            $like_check = $conn->prepare("SELECT 1 FROM Likes WHERE post_id = ? AND user_id = ?");
            $like_check->bind_param("ii", $post['post_id'], $_SESSION['user_id']);
            $like_check->execute();
            $post['is_liked'] = $like_check->get_result()->num_rows > 0;
        }
        
        $posts[] = $post;
    }
    
    echo json_encode(['success' => true, 'posts' => $posts]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
