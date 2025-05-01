<?php
include 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $conversation_id = intval($_POST['conversation_id']);
    $is_typing = intval($_POST['is_typing']);
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE conversations_status 
                           SET is_typing = ?, last_activity = NOW() 
                           WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("iii", $is_typing, $conversation_id, $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false]);