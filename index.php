<?php
include 'includes/config.php';
include 'includes/header.php';

$posts_query = "SELECT Posts.*, users.username, users.profile_pic 
                FROM Posts 
                INNER JOIN users ON Posts.user_id = users.id 
                WHERE Posts.is_deleted = 0
                ORDER BY Posts.created_at DESC";
$posts_result = $conn->query($posts_query);
?>

<div class="container mt-5">
    <?php if(isset($_SESSION['user_id'])): ?>
    <div class="card post-card mb-4 p-4">
        <form action="process_post.php" method="POST" enctype="multipart/form-data">
            <textarea class="form-control mb-3" name="content" placeholder="ماذا يخطر ببالك؟"></textarea>
            <div class="d-flex justify-content-between align-items-center">
                <div class="media-upload-section w-50">
                    <select name="media_type" id="mediaType" class="form-select mb-2">
                        <option value="image">صورة</option>
                        <option value="video">فيديو</option>
                    </select>
                    <input type="file" name="media" class="form-control" id="mediaInput" accept="image/*,video/*">
                    <small class="text-muted" id="mediaHelp">الحد الأقصى: الصور (5MB)، الفيديو (50MB)</small>
                </div>
                <button type="submit" class="btn btn-primary">نشر</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div id="posts-container">
        <!-- Posts will be loaded here -->
    </div>

    <div id="loading-indicator" class="text-center p-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<script>
let page = 1;
let loading = false;
let noMorePosts = false;

document.addEventListener('DOMContentLoaded', function() {
    loadPosts();
    initializeNotifications();
});

function loadPosts() {
    if (loading || noMorePosts) return;
    loading = true;
    
    document.getElementById('loading-indicator').style.display = 'block';
    
    fetch(`get_posts.php?page=${page}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (!data.posts || data.posts.length === 0) {
            noMorePosts = true;
            if (page === 1) {
                document.getElementById('posts-container').innerHTML = 
                    '<div class="alert alert-info">لا توجد منشورات لعرضها</div>';
            }
            return;
        }
        
        const container = document.getElementById('posts-container');
        data.posts.forEach(post => {
            container.insertAdjacentHTML('beforeend', createPostHTML(post));
        });
        
        page++;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('posts-container').innerHTML = 
            '<div class="alert alert-danger">حدث خطأ أثناء تحميل المنشورات</div>';
    })
    .finally(() => {
        loading = false;
        document.getElementById('loading-indicator').style.display = 'none';
    });
}

function createPostHTML(post) {
    return `
        <div class="card post-card mb-4 p-4" data-post-id="${post.post_id}">
            <!-- Post content -->
            ${createPostContent(post)}
            <!-- Interaction buttons -->
            ${createPostInteractions(post)}
        </div>
    `;
}

function createPostContent(post) {
    let mediaHtml = '';
    if (post.media_url) {
        if (post.media_type === 'image') {
            mediaHtml = `
                <div class="text-center my-3">
                    <img src="uploads/${post.media_url}" 
                         class="img-fluid rounded post-image" 
                         onclick="showMediaModal('${post.media_url}', 'image')"
                         loading="lazy"
                         alt="صورة المنشور">
                </div>`;
        } else if (post.media_type === 'video') {
            mediaHtml = `
                <div class="text-center my-3">
                    <video class="post-video img-fluid rounded" controls>
                        <source src="uploads/${post.media_url}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>`;
        }
    }

    return `
        <div class="d-flex align-items-center mb-3">
            <img src="uploads/${post.profile_pic}" class="rounded-circle me-2" width="40" height="40">
            <div>
                <h6 class="mb-0"><a href="profile.php?user=${post.user_id}" class="text-decoration-none text-dark">${post.username}</a></h6>
                <small class="text-muted">${post.time_ago}</small>
            </div>
        </div>
        <p class="mb-3">${post.content}</p>
        ${mediaHtml}`;
}

function createPostInteractions(post) {
    return `
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-3">
                <button class="btn btn-outline-primary btn-sm" onclick="likePost(${post.post_id})">
                    <i class="fas fa-thumbs-up"></i>
                    <span id="like-count-${post.post_id}">${post.likes_count}</span>
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="toggleComments(${post.post_id})">
                    <i class="fas fa-comment"></i>
                    <span class="badge bg-secondary">${post.comments_count}</span>
                </button>
            </div>
            ${post.user_id === (window.currentUser || {}).id ? `
                <div class="dropdown">
                    <button class="btn btn-link text-secondary" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="edit_post.php?id=${post.post_id}">تعديل</a></li>
                        <li><a class="dropdown-item text-danger" href="delete_post.php?id=${post.post_id}" 
                            onclick="return confirm('هل أنت متأكد من حذف هذا المنشور؟')">حذف</a></li>
                    </ul>
                </div>
            ` : ''}
        </div>
        <div id="comments-container-${post.post_id}" class="comments-section mt-3" style="display: none;">
            <div id="comments-list-${post.post_id}"></div>
            <form onsubmit="submitComment(event, ${post.post_id})" class="mt-3">
                <div class="input-group">
                    <input type="text" id="comment-input-${post.post_id}" class="form-control" placeholder="اكتب تعليقك...">
                    <button type="submit" class="btn btn-primary">إرسال</button>
                </div>
            </form>
        </div>`;
}


// وظيفة الإعجاب
function likePost(postId) {
    fetch('like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('like-count-'+postId).innerText = data.new_count;
        }
    });
}

// دالة تحميل التعليقات
function loadComments(postId) {
    console.log('جاري تحميل التعليقات لبوست:', postId);
    fetch('get_comments.php?post_id=' + postId)
    .then(response => {
        if(!response.ok) throw new Error('Network response was not ok');
        return response.text();
    })
    .then(html => {
        document.getElementById('comments-list-'+postId).innerHTML = html;
    })
    .catch(error => {
        console.error('Error loading comments:', error);
    });
}

// دالة تبديل عرض/إخفاء التعليقات
function toggleComments(postId) {
    console.log('تم استدعاء toggleComments لبوست:', postId); // للتتبع
    const commentsDiv = document.getElementById('comments-container-'+postId);
    if (commentsDiv) {
        commentsDiv.style.display = commentsDiv.style.display === 'none' ? 'block' : 'none';
        
        if(commentsDiv.style.display === 'block') {
            loadComments(postId);
        }
    } else {
        console.error('لم يتم العثور على العنصر:', 'comments-container-'+postId);
    }
}

// دالة إرسال التعليق
function submitComment(event, postId) {
    event.preventDefault();
    const input = document.getElementById('comment-input-'+postId);
    const comment = input.value.trim();
    
    if(!comment) return;
    
    console.log('جاري إرسال تعليق:', comment);
    
    fetch('add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&content=${encodeURIComponent(comment)}`
    })
    .then(response => {
        if(!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if(data.success) {
            console.log('تم إضافة التعليق بنجاح');
            input.value = '';
            loadComments(postId);
            updateCommentCount(postId, data.new_count);
        } else {
            console.error('Error from server:', data.error);
        }
    })
    .catch(error => {
        console.error('Error submitting comment:', error);
    });
}

function deleteComment(commentId) {
    if (confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
        fetch('delete_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `comment_id=${commentId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the comment element
                document.getElementById(`comment-${commentId}`).remove();
                // Update comment count if needed
                if (data.new_count !== undefined) {
                    updateCommentCount(currentPostId, data.new_count);
                }
            } else {
                alert('فشل حذف التعليق: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function editComment(commentId) {
    const contentElement = document.getElementById(`comment-content-${commentId}`);
    const currentContent = contentElement.textContent;
    
    // Replace content with input field
    contentElement.innerHTML = `
        <div class="input-group">
            <input type="text" class="form-control" id="edit-input-${commentId}" value="${currentContent}">
            <button class="btn btn-primary" onclick="saveComment(${commentId})">حفظ</button>
            <button class="btn btn-secondary" onclick="cancelEdit(${commentId}, '${currentContent}')">إلغاء</button>
        </div>
    `;
}

function saveComment(commentId) {
    const inputElement = document.getElementById(`edit-input-${commentId}`);
    const newContent = inputElement.value.trim();
    
    if (!newContent) {
        alert('لا يمكن حفظ تعليق فارغ');
        return;
    }
    
    fetch('edit_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `comment_id=${commentId}&content=${encodeURIComponent(newContent)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const contentElement = document.getElementById(`comment-content-${commentId}`);
            contentElement.textContent = data.new_content;
            if (data.message) {
                // Optional: Show success message
                console.log(data.message);
            }
        } else {
            alert(data.error || 'حدث خطأ أثناء تحديث التعليق');
            cancelEdit(commentId, newContent);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تحديث التعليق');
        cancelEdit(commentId, newContent);
    });
}

function cancelEdit(commentId, originalContent) {
    document.getElementById(`comment-content-${commentId}`).textContent = originalContent;
}

// تغيير نوع الملف المقبول عند تغيير نوع الوسائط
document.addEventListener('DOMContentLoaded', function() {
    const mediaTypeSelect = document.getElementById('mediaType');
    const mediaInput = document.getElementById('mediaInput');
    
    if (mediaTypeSelect && mediaInput) {
        mediaTypeSelect.addEventListener('change', function() {
            if (this.value === 'image') {
                mediaInput.setAttribute('accept', 'image/*');
            } else if (this.value === 'video') {
                mediaInput.setAttribute('accept', 'video/*');
            } else {
                mediaInput.setAttribute('accept', 'image/*,video/*');
            }
        });
    }

    // بقية كود DOMContentLoaded
    updateNotifications();
    setInterval(updateNotifications, 30000);
    
    if (!("Notification" in window)) {
        console.log("هذا المتصفح لا يدعم الإشعارات");
    } else if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }
});

// دالة تحديث العداد
function updateCommentCount(postId, count) {
    const badge = document.querySelector(`button[onclick="toggleComments(${postId})"] .badge`);
    if(badge) {
        badge.textContent = count;
    }
}

// دالة تحديث الإشعارات
async function updateNotifications() {
    try {
        const response = await fetch('check_notifications.php?_=' + Date.now());
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            console.error('Error in notifications:', data.error);
            return;
        }
        
        const badge = document.getElementById('notificationBadge');
        const list = document.getElementById('notificationList');
        
        if (data.unread > 0) {
            badge.style.display = 'block';
            badge.textContent = data.unread;
            
            let notificationsHtml = data.notifications.map(notif => `
                <li class="dropdown-item">
                    <a href="messages.php?with=${notif.sender_id}" class="d-flex align-items-center text-decoration-none text-dark">
                        <img src="uploads/${notif.sender_pic}" width="40" height="40" class="rounded-circle me-2">
                        <div>
                            <strong>${notif.sender_name}</strong>
                            <div class="small">${notif.content}</div>
                            <small class="text-muted">${notif.time_ago}</small>
                        </div>
                    </a>
                </li>
            `).join('');
            
            list.innerHTML = notificationsHtml;
            
            if (data.unread > 0 && Notification.permission === "granted") {
                new Notification(`لديك ${data.unread} إشعارات جديدة`, {
                    body: `آخر إشعار من ${data.notifications[0].sender_name}`,
                    icon: 'uploads/' + data.notifications[0].sender_pic
                });
            }
        } else {
            badge.style.display = 'none';
            list.innerHTML = '<li class="dropdown-item-text text-center text-muted">لا توجد إشعارات جديدة</li>';
        }
    } catch (error) {
        console.error('فشل في جلب الإشعارات:', error);
        // يمكنك إضافة عرض رسالة خطأ للمستخدم هنا إذا لزم الأمر
    }
}

// بدء التحقق من الإشعارات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تحديث الإشعارات
    updateNotifications();
    setInterval(updateNotifications, 30000);
    
    // طلب إذن إشعارات المتصفح
    if (!("Notification" in window)) {
        console.log("هذا المتصفح لا يدعم الإشعارات");
    } else if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }
});

let notificationInterval;

function showNotificationUI(data) {
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    
    if (!badge || !list) {
        console.error('عناصر الإشعارات غير موجودة في DOM');
        return;
    }

    badge.textContent = data.unread > 0 ? data.unread : '';
    badge.style.display = data.unread > 0 ? 'block' : 'none';

    list.innerHTML = data.notifications.length > 0 
        ? data.notifications.map(notif => `
            <li class="dropdown-item">
                <a href="messages.php?with=${notif.sender_id}" class="d-flex align-items-center">
                    <img src="uploads/${notif.sender_pic || 'default.png'}" 
                         class="rounded-circle me-2" width="40" height="40">
                    <div>
                        <strong>${notif.sender_name}</strong>
                        <p class="small mb-0">${notif.content}</p>
                        <small class="text-muted">${notif.time_ago}</small>
                    </div>
                </a>
            </li>
        `).join('') 
        : '<li class="dropdown-item text-center text-muted">لا توجد إشعارات</li>';
}

async function fetchNotifications() {
    try {
        const response = await fetch('check_notifications.php?_=' + Date.now());
        const data = await response.json();
        
        if (data.success) {
            showNotificationUI(data);
            showBrowserNotification(data);
        } else {
            console.error('Error:', data.error);
        }
    } catch (error) {
        console.error('فشل جلب الإشعارات:', error);
    }
}

function showBrowserNotification(data) {
    if (data.unread > 0 && Notification.permission === 'granted') {
        const lastNotif = data.notifications[0];
        new Notification(`رسالة جديدة من ${lastNotif.sender_name}`, {
            body: lastNotif.content,
            icon: `uploads/${lastNotif.sender_pic || 'default.png'}`
        });
    }
}

// بدء النظام
document.addEventListener('DOMContentLoaded', () => {
    if (!Notification) {
        console.warn('المتصفح لا يدعم إشعارات الويب');
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
    
    fetchNotifications();
    notificationInterval = setInterval(fetchNotifications, 20000);
});

// تنظيف عند مغادرة الصفحة
window.addEventListener('beforeunload', () => {
    clearInterval(notificationInterval);
});

function initializeNotifications() {
    if (!("Notification" in window)) {
        console.log("This browser does not support notifications");
        return;
    }

    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission().then(function (permission) {
            if (permission === "granted") {
                console.log("Notification permission granted");
            }
        });
    }
}

async function checkNotifications() {
    try {
        const response = await fetch('check_notifications.php?_=' + Date.now());
        const data = await response.json();
        
        if (data.success) {
            updateNotificationUI(data);
            
            // Show browser notification for new messages
            if (data.unread > 0 && Notification.permission === "granted") {
                const lastNotif = data.notifications[0];
                new Notification("رسالة جديدة من " + lastNotif.sender_name, {
                    body: lastNotif.content,
                    icon: 'uploads/' + (lastNotif.sender_pic || 'default.png'),
                    tag: 'notification-' + lastNotif.id // Prevent duplicate notifications
                });
            }
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

function updateNotificationUI(data) {
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    
    if (!badge || !list) return;

    // Update notification count
    if (data.unread > 0) {
        badge.style.display = 'block';
        badge.textContent = data.unread;
    } else {
        badge.style.display = 'none';
    }

    // Update notification list
    if (data.notifications.length > 0) {
        list.innerHTML = data.notifications.map(notif => `
            <li class="dropdown-item notification-item ${notif.is_read ? '' : 'unread'}">
                <a href="${notif.type === 'message' ? 'messages.php?with=' + notif.sender_id : 'profile.php?id=' + notif.sender_id}" 
                   class="d-flex align-items-center text-decoration-none text-dark">
                    <img src="uploads/${notif.sender_pic || 'default.png'}" 
                         class="rounded-circle me-2" 
                         width="40" height="40">
                    <div>
                        <strong>${notif.sender_name}</strong>
                        <p class="small mb-0">${notif.content}</p>
                        <small class="text-muted">${notif.time_ago}</small>
                    </div>
                </a>
            </li>
        `).join('');
    } else {
        list.innerHTML = '<li class="dropdown-item text-center text-muted">لا توجد إشعارات</li>';
    }
}

// Initialize notifications when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeNotifications();
    
    // Check for new notifications every 30 seconds
    checkNotifications();
    const notificationInterval = setInterval(checkNotifications, 30000);
    
    // Add click handler for notification dropdown
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', async function() {
            try {
                await fetch('mark_notifications_read.php', {
                    method: 'POST'
                });
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    badge.style.display = 'none';
                }
            } catch (error) {
                console.error('Error marking notifications as read:', error);
            }
        });
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        clearInterval(notificationInterval);
    });
});

</script>

<?php include 'includes/footer.php'; ?>