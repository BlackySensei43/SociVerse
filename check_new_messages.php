<?php
include 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;

if (!$conversation_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid conversation']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT m.*, u.username 
                           FROM messages m
                           JOIN users u ON m.sender_id = u.id
                           WHERE m.conversation_id = ? AND m.id > ?
                           ORDER BY m.created_at ASC");
    
    $stmt->bind_param("ii", $conversation_id, $last_message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'content' => $row['content_type'] === 'text' ? htmlspecialchars($row['content']) : $row['image_path'],
            'content_type' => $row['content_type'],
            'time' => date('H:i', strtotime($row['created_at'])),
            'username' => $row['username']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
