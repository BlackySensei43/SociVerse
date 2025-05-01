<?php
include 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $comment_id = intval($_POST['comment_id']);
    $user_id = $_SESSION['user_id'];
    $content = trim(htmlspecialchars($_POST['content']));
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'التعليق لا يمكن أن يكون فارغاً']);
        exit();
    }
    
    // Check if user owns the comment
    $stmt = $conn->prepare("SELECT comment_id FROM Comments WHERE comment_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // Update the comment
        $update_stmt = $conn->prepare("UPDATE Comments SET content = ? WHERE comment_id = ?");
        $update_stmt->bind_param("si", $content, $comment_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'new_content' => $content,
                'message' => 'تم تحديث التعليق بنجاح'
            ]);
            exit();
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'حدث خطأ أثناء تحديث التعليق'
            ]);
            exit();
        }
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'غير مصرح لك بتعديل هذا التعليق'
        ]);
        exit();
    }
}

echo json_encode([
    'success' => false, 
    'error' => 'طلب غير صالح'
]);