<?php
ob_start();
include 'includes/config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

ob_end_flush();

$current_user_id = $_SESSION['user_id'];
$other_user_id = isset($_GET['with']) ? intval($_GET['with']) : 0;

$conversations = getUserConversations($current_user_id);

// التحقق من صحة المحادثة
if ($other_user_id > 0 && $other_user_id != $current_user_id) {
    // إنشاء/جلب المحادثة
    $conversation = getOrCreateConversation($current_user_id, $other_user_id);
    
    // معالجة إرسال الرسائل
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        // تسجيل بيانات الإرسال للتصحيح
        error_log("POST Data: " . print_r($_POST, true));
        error_log("FILES Data: " . print_r($_FILES, true));

        if (!isset($conversation)) {
            $_SESSION['error'] = 'المحادثة غير موجودة';
            echo json_encode(['status' => 'error', 'message' => 'المحادثة غير موجودة']);
            exit();
        }

        // التحقق من نوع المحتوى المرسل
        if (!empty($_FILES['media']['name'])) {
            // معالجة الوسائط (صورة أو فيديو)
            $file_info = pathinfo($_FILES['media']['name']);
            $file_ext = strtolower($file_info['extension']);
            
            // تحديد نوع الملف
            $content_type = 'image'; // افتراضي للصور
            $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowed_video_types = ['mp4', 'webm', 'ogg', 'mov'];
            
            if (in_array($file_ext, $allowed_video_types)) {
                $content_type = 'video';
                $max_size = 52428800; // 50MB for videos
            } elseif (in_array($file_ext, $allowed_image_types)) {
                $content_type = 'image';
                $max_size = 5242880; // 5MB for images
            } else {
                echo json_encode(['status' => 'error', 'message' => 'نوع الملف غير مدعوم']);
                exit();
            }
            
            if ($_FILES['media']['size'] > $max_size) {
                echo json_encode(['status' => 'error', 
                    'message' => 'حجم الملف كبير جداً. الحد الأقصى: ' . 
                    ($content_type == 'video' ? '50MB' : '5MB')]);
                exit();
            }

            // إنشاء مجلد للتخزين
            $upload_dir = 'uploads/messages/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
        
            $file_name = time() . '_' . basename($_FILES['media']['name']);
            $target_path = $upload_dir . $file_name;
        
            if (move_uploaded_file($_FILES['media']['tmp_name'], $target_path)) {
                if (sendMessage($conversation, $current_user_id, $file_name, $content_type)) {
                    // جلب الرسائل المحدثة لإرجاعها في الرد
                    $messages = getMessages($conversation['id']);
                    $html = generateMessagesHTML($messages, $current_user_id);
                    echo json_encode(['status' => 'success', 'html' => $html]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'فشل إرسال الوسائط']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'خطأ في رفع الملف']);
            }
        } 
        elseif (!empty($_POST['message'])) {
            if (sendMessage($conversation, $current_user_id, sanitize($_POST['message']), 'text')) {
                // جلب الرسائل المحدثة لإرجاعها في الرد
                $messages = getMessages($conversation['id']);
                $html = generateMessagesHTML($messages, $current_user_id);
                echo json_encode(['status' => 'success', 'html' => $html]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'فشل إرسال الرسالة']);
            }
        }
        else {
            echo json_encode(['status' => 'error', 'message' => 'الرسالة فارغة']);
        }
        exit();
    }
    
    // جلب الرسائل
    $messages = getMessages($conversation['id']);
}

// دالة لتوليد HTML الرسائل
function generateMessagesHTML($messages, $current_user_id) {
    $html = '';
    while($msg = $messages->fetch_assoc()) {
        $timestamp = date('Y-m-d H:i:s', strtotime($msg['created_at']));
        $html .= '<div class="mb-3 ' . ($msg['sender_id'] == $current_user_id ? 'text-end' : 'text-start') . '" data-timestamp="' . $timestamp . '">';
        
        if($msg['content_type'] == 'image') {
            $html .= '<div class="d-inline-block">
                        <img src="uploads/messages/' . htmlspecialchars($msg['content']) . '" 
                             class="img-thumbnail" 
                             style="max-width: 200px; cursor: pointer;"
                             onclick="showMediaModal(\'' . htmlspecialchars($msg['content']) . '\', \'image\')">
                        <small class="d-block text-muted mt-1">' . date('H:i', strtotime($msg['created_at'])) . '</small>
                    </div>';
        } 
        elseif($msg['content_type'] == 'video') {
            $html .= '<div class="d-inline-block">
                        <video class="img-thumbnail" style="max-width: 200px;" controls>
                            <source src="uploads/messages/' . htmlspecialchars($msg['content']) . '" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <small class="d-block text-muted mt-1">' . date('H:i', strtotime($msg['created_at'])) . '</small>
                    </div>';
        }
        else {
            $html .= '<div class="d-inline-block p-3 rounded ' . 
                    ($msg['sender_id'] == $current_user_id ? 'bg-primary text-white' : 'bg-light') . '">
                        ' . htmlspecialchars($msg['content']) . '
                        <small class="d-block ' . 
                        ($msg['sender_id'] == $current_user_id ? 'text-white-50' : 'text-muted') . ' mt-1">
                            ' . date('H:i', strtotime($msg['created_at'])) . '
                        </small>
                    </div>';
        }
        
        $html .= '</div>';
    }
    return $html;
}

// دالة لإنشاء/جلب المحادثة
function getOrCreateConversation($user1_id, $user2_id) {
    global $conn;
    
    $min_id = min($user1_id, $user2_id);
    $max_id = max($user1_id, $user2_id);
    
    $stmt = $conn->prepare("SELECT * FROM conversations 
                          WHERE user1_id = ? AND user2_id = ?");
    $stmt->bind_param("ii", $min_id, $max_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO conversations (user1_id, user2_id) 
                                VALUES (?, ?)");
        $insert->bind_param("ii", $min_id, $max_id);
        $insert->execute();
        
        return [
            'id' => $conn->insert_id,
            'user1_id' => $min_id,
            'user2_id' => $max_id
        ];
    } else {
        return $result->fetch_assoc();
    }
}

// دالة إرسال الرسائل
function sendMessage($conversation, $sender_id, $content, $content_type = 'text') {
    global $conn;

    if (!$conn || $conn->connect_error) {
        error_log("Database connection failed");
        return false;
    }
    
    try {
        if ($content_type == 'image' || $content_type == 'video') {
            $stmt = $conn->prepare("INSERT INTO messages 
                                  (conversation_id, sender_id, content_type, content) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $conversation['id'], $sender_id, $content_type, $content);
        } else {
            $stmt = $conn->prepare("INSERT INTO messages 
                                  (conversation_id, sender_id, content_type, content) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $conversation['id'], $sender_id, $content_type, $content);
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            // إرسال إشعار للمستقبل
            $receiver_id = ($sender_id == $conversation['user1_id']) 
                         ? $conversation['user2_id'] 
                         : $conversation['user1_id'];
            
            $notif_content = '';
            if ($content_type == 'image') {
                $notif_content = 'أرسل صورة جديدة';
            } elseif ($content_type == 'video') {
                $notif_content = 'أرسل فيديو جديد';
            } else {
                $notif_content = substr($content, 0, 30);
            }
            
            $stmt = $conn->prepare("INSERT INTO notifications 
                                  (user_id, sender_id, type, content) 
                                  VALUES (?, ?, 'message', ?)");
            $stmt->bind_param("iis", $receiver_id, $sender_id, $notif_content);
            $stmt->execute();
            
            return true;
        }
    } catch (Exception $e) {
        error_log("Message Error: " . $e->getMessage());
    }
    
    return false;
}

// دالة جلب الرسائل
function getMessages($conversation_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT m.*, u.username, u.profile_pic 
                          FROM messages m
                          JOIN users u ON m.sender_id = u.id
                          WHERE m.conversation_id = ?
                          ORDER BY m.created_at ASC");
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    return $stmt->get_result();
}

// دالة جلب الرسائل الجديدة بعد تاريخ محدد
function getNewMessages($conversation_id, $last_message_time) {
    global $conn;
    
    if (empty($last_message_time)) {
        // If no timestamp provided, return empty result
        $empty = $conn->query("SELECT 1 FROM messages WHERE 1=0");
        return $empty;
    }
    
    $stmt = $conn->prepare("SELECT m.*, u.username, u.profile_pic 
                          FROM messages m
                          JOIN users u ON m.sender_id = u.id
                          WHERE m.conversation_id = ? AND m.created_at > ?
                          ORDER BY m.created_at ASC");
    $stmt->bind_param("is", $conversation_id, $last_message_time);
    $stmt->execute();
    return $stmt->get_result();
}

// دالة جلب محادثات المستخدم
function getUserConversations($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT c.*, 
                          CASE 
                            WHEN c.user1_id = ? THEN u2.id
                            ELSE u1.id
                          END as other_user_id,
                          CASE 
                            WHEN c.user1_id = ? THEN u2.username
                            ELSE u1.username
                          END as other_username,
                          CASE 
                            WHEN c.user1_id = ? THEN u2.profile_pic
                            ELSE u1.profile_pic
                          END as other_profile_pic,
                          (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                          (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_activity
                          FROM conversations c
                          LEFT JOIN users u1 ON c.user1_id = u1.id
                          LEFT JOIN users u2 ON c.user2_id = u2.id
                          WHERE c.user1_id = ? OR c.user2_id = ?
                          ORDER BY last_activity IS NULL, last_activity DESC"); 
    $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

if (isset($_GET['action']) && $_GET['action'] == 'get_new_messages') {
    ob_clean();
    $conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
    $last_timestamp = isset($_GET['last_timestamp']) ? $_GET['last_timestamp'] : '';
    
    if ($conversation_id > 0) {
        // Check if the current user is part of this conversation
        $conversation = $conn->prepare("SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
        $conversation->bind_param("iii", $conversation_id, $current_user_id, $current_user_id);
        $conversation->execute();
        $conv_result = $conversation->get_result();
        
        if ($conv_result->num_rows > 0) {
            $messages = getNewMessages($conversation_id, $last_timestamp);
            $html = generateMessagesHTML($messages, $current_user_id);
            echo json_encode(['status' => 'success', 'html' => $html]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول لهذه المحادثة']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'معلومات غير كافية']);
    }
    exit();
}

function getMediaType($file_extension) {
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $video_extensions = ['mp4', 'webm', 'ogg', 'mov'];
    
    $ext = strtolower($file_extension);
    
    if(in_array($ext, $image_extensions)) {
        return 'image';
    } elseif(in_array($ext, $video_extensions)) {
        return 'video';
    }
    return null;
}

?>

<?php if(isset($_SESSION['error'])): ?>
    <script>alert('<?= $_SESSION['error'] ?>');</script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">المحادثات</h5>
                </div>
                <div class="list-group list-group-flush" id="conversations-list">
                    <?php if($conversations->num_rows > 0): ?>
                        <?php while($conv = $conversations->fetch_assoc()): ?>
                            <a href="messages.php?with=<?= $conv['other_user_id'] ?>" 
                               class="list-group-item list-group-item-action d-flex align-items-center">
                                <img src="uploads/<?= $conv['other_profile_pic'] ?>" 
                                     class="rounded-circle me-3" width="40" height="40">
                                <div>
                                    <h6 class="mb-0"><?= $conv['other_username'] ?></h6>
                                    <small class="text-muted">
                                        <?= 
                                            !empty($conv['last_message']) 
                                            ? (strlen($conv['last_message']) > 30 
                                                ? substr($conv['last_message'], 0, 30) . '...' 
                                                : $conv['last_message'])
                                            : 'بدون رسائل'
                                        ?>
                                    </small>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-3 text-center text-muted">
                            لا توجد محادثات
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if(isset($other_user_id) && $other_user_id > 0): ?>
                <?php 
                $other_user = $conn->query("SELECT * FROM users WHERE id = $other_user_id")->fetch_assoc();
                ?>
                <div class="card">
                    <div class="card-header bg-white d-flex align-items-center">
                        <img src="uploads/<?= $other_user['profile_pic'] ?>" 
                             class="rounded-circle me-3" width="40" height="40">
                        <h5 class="mb-0"><?= $other_user['username'] ?></h5>
                    </div>
                    <div class="card-body message-box" style="height: 400px; overflow-y: auto;" id="messages-container">
                        <?php if(isset($messages) && $messages->num_rows > 0): ?>
                            <?php while($msg = $messages->fetch_assoc()): ?>
                                <div class="mb-3 <?= $msg['sender_id'] == $current_user_id ? 'text-end' : 'text-start' ?>">
                                    <?php if($msg['content_type'] == 'image'): ?>
                                        <!-- عرض الصورة -->
                                        <div class="d-inline-block">
                                            <img src="uploads/messages/<?= $msg['content'] ?>" 
                                                class="img-thumbnail rounded" 
                                                style="max-width: 300px; cursor: pointer;"
                                                onclick="showImageModal('<?= $msg['content'] ?>')">
                                            <small class="d-block text-muted mt-1">
                                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                                            </small>
                                        </div>
                                    <?php elseif($msg['content_type'] == 'video'): ?>
                                        <!-- عرض الفيديو -->
                                        <div class="d-inline-block">
                                        <video class="img-thumbnail" style="max-width: 200px;" controls>
                                            <source src="uploads/messages/<?= $msg['content'] ?>" type="video/mp4">
                                                متصفحك لا يدعم تشغيل الفيديو
                                            </video>
                                            <small class="d-block text-muted mt-1">
                                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <!-- عرض النص -->
                                        <div class="d-inline-block p-3 rounded <?= $msg['sender_id'] == $current_user_id ? 'bg-primary text-white' : 'bg-light' ?>">
                                            <?= htmlspecialchars($msg['content'] ?? '') ?>
                                            <small class="d-block <?= $msg['sender_id'] == $current_user_id ? 'text-white-50' : 'text-muted' ?> mt-1">
                                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                لا توجد رسائل بعد، ابدأ المحادثة الآن
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <form id="message-form" method="POST" enctype="multipart/form-data" class="d-flex">
                            <div class="input-group">
                                <input type="text" name="message" id="message-input" class="form-control" placeholder="اكتب رسالتك...">
                                <button type="button" class="btn btn-outline-secondary" id="media-button">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <input type="file" name="media" id="media-upload" accept="image/*,video/mp4,video/webm,video/ogg" style="display:none;">
                                <button type="submit" class="btn btn-primary">إرسال</button>
                            </div>
                        </form>
                        <div id="media-preview" class="mt-2" style="display: none;">
                            <div class="d-flex align-items-center">
                                <div id="preview-content" class="me-2"></div>
                                <button type="button" class="btn btn-sm btn-danger" id="remove-media">إلغاء</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h4>اختر محادثة لبدء المراسلة</h4>
                        <p class="text-muted">أو ابحث عن مستخدم لبدء محادثة جديدة</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- مودال معاينة الوسائط -->
<div class="modal fade" id="mediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0" id="mediaModalContent">
            </div>
        </div>
    </div>
</div>

<style>
.message-box {
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,0.2) transparent;
}

.message-box::-webkit-scrollbar {
    width: 6px;
}

.message-box::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}

.scroll-bottom-btn {
    opacity: 0.8;
    transition: opacity 0.3s;
    z-index: 1000;
}

.scroll-bottom-btn:hover {
    opacity: 1;
}

#media-upload {
    display: none;
}

.typing-indicator {
    padding: 0.5rem;
    background: rgba(0,0,0,0.05);
    border-radius: 1rem;
    margin: 0.5rem 0;
    display: inline-block;
}

.typing-indicator span {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #999;
    border-radius: 50%;
    animation: typing-animation 1s infinite ease-in-out;
    margin: 0 2px;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing-animation {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.img-thumbnail {
    max-height: 300px;
    cursor: pointer;
    transition: transform 0.2s;
}

.img-thumbnail:hover {
    transform: scale(1.05);
}

video.img-thumbnail {
    max-width: 200px;
    max-height: 300px;
    cursor: pointer;
}

.message-unread {
    position: relative;
}

.message-unread::after {
    content: '';
    position: absolute;
    right: -10px;
    top: 50%;
    width: 8px;
    height: 8px;
    background: var(--primary-color);
    border-radius: 50%;
    transform: translateY(-50%);
}

.submitting {
    pointer-events: none;
    opacity: 0.7;
}

</style>

<script>
// Clean up and simplify form submission handling
document.addEventListener('DOMContentLoaded', function() {
    // Media handling elements
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const mediaButton = document.getElementById('media-button');
    const mediaUpload = document.getElementById('media-upload');
    const mediaPreview = document.getElementById('media-preview');
    const previewContent = document.getElementById('preview-content');
    const removeMedia = document.getElementById('remove-media');
    const messagesContainer = document.getElementById('messages-container');
    
    // Current conversation ID and last message timestamp for polling
    const currentConversationId = <?= isset($conversation['id']) ? $conversation['id'] : 0 ?>;
    let lastTimestamp = '';
    
    // Function to get current timestamp from the latest message
    function updateLastTimestamp() {
        if (messagesContainer) {
            const messages = messagesContainer.querySelectorAll('[data-timestamp]');
            if (messages.length > 0) {
                lastTimestamp = messages[messages.length - 1].getAttribute('data-timestamp');
            }
        }
    }
    
    // Initialize last timestamp
    updateLastTimestamp();
    
    // Scroll message box to bottom on load
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Media button click handler
    if (mediaButton && mediaUpload) {
        mediaButton.addEventListener('click', function() {
            mediaUpload.click();
        });
    }
    
    // Media upload change handler
    if (mediaUpload && previewContent) {
        mediaUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    mediaPreview.style.display = 'block';
                    
                    if (file.type.startsWith('image/')) {
                        previewContent.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px">`;
                    } else if (file.type.startsWith('video/')) {
                        previewContent.innerHTML = `
                            <video class="img-thumbnail" style="max-height: 100px" controls>
                                <source src="${e.target.result}" type="${file.type}">
                                متصفحك لا يدعم تشغيل الفيديو
                            </video>`;
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Remove media handler
    if (removeMedia && mediaUpload && mediaPreview) {
        removeMedia.addEventListener('click', function() {
            mediaUpload.value = '';
            mediaPreview.style.display = 'none';
            previewContent.innerHTML = '';
        });
    }
    
    // Function to check for new messages
    function checkNewMessages() {
        if (!currentConversationId) return;
        
        fetch(`messages.php?action=get_new_messages&conversation_id=${currentConversationId}&last_timestamp=${encodeURIComponent(lastTimestamp)}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.html) {
                    // Add new messages to container
                    messagesContainer.insertAdjacentHTML('beforeend', data.html);
                    
                    // Auto scroll to bottom if already near bottom
                    const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.clientHeight <= messagesContainer.scrollTop + 100;
                    if (isScrolledToBottom) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                    
                    // Update last timestamp
                    updateLastTimestamp();
                }
            })
            .catch(error => console.error('Error checking new messages:', error));
    }
    
    // Start polling for new messages every 3 seconds if in a conversation
    if (currentConversationId) {
        setInterval(checkNewMessages, 3000);
    }
    
    // Message form submission
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!messageInput.value.trim() && (!mediaUpload || !mediaUpload.files.length)) {
                alert('يرجى كتابة رسالة أو إرفاق وسائط');
                return;
            }
            
            // Disable form during submission
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            this.classList.add('submitting');
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Clear form
                    messageInput.value = '';
                    if (mediaUpload) mediaUpload.value = '';
                    if (mediaPreview) {
                        mediaPreview.style.display = 'none';
                        previewContent.innerHTML = '';
                    }
                    
                    // Update messages container
                    if (messagesContainer && data.html) {
                        messagesContainer.insertAdjacentHTML('beforeend', data.html);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        
                        // Update last timestamp after adding new message
                        updateLastTimestamp();
                    }
                } else {
                    alert(data.message || 'حدث خطأ أثناء إرسال الرسالة');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إرسال الرسالة');
            })
            .finally(() => {
                submitButton.disabled = false;
                this.classList.remove('submitting');
            });
        });
    }
});

// عرض الصور في نافذة مودال
function showImageModal(imageUrl) {
    const modalContent = document.getElementById('mediaModalContent');
    if (modalContent) {
        modalContent.innerHTML = `<img src="uploads/messages/${imageUrl}" class="img-fluid">`;
        
        const modalElement = document.getElementById('mediaModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }
}

// عرض الفيديو في نافذة مودال
function showMediaModal(url, type) {
    const modalContent = document.getElementById('mediaModalContent');
    if(type === 'image') {
        modalContent.innerHTML = `<img src="uploads/messages/${url}" class="img-fluid">`;
    } else if(type === 'video') {
        modalContent.innerHTML = `
            <video class="img-fluid" controls autoplay>
                <source src="uploads/messages/${url}" type="video/mp4">
                Your browser does not support the video tag.
            </video>`;
    }
    const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
    modal.show();
}

// Add style for the submitting class
const style = document.createElement('style');
style.textContent = `
    .submitting {
        pointer-events: none;
        opacity: 0.7;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>