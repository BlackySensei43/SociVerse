<?php
include 'includes/config.php';
include 'includes/header.php';

$profile_user_id = 0;
$is_own_profile = false;
$is_following = false;

// تحديد المستخدم المعروض
$profile_user_id = isset($_GET['user']) ? intval($_GET['user']) : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

if ($profile_user_id == 0) {
    header("Location: login.php");
    exit();
}

// جلب بيانات المستخدم
$user = $conn->query("SELECT * FROM users WHERE id = $profile_user_id")->fetch_assoc();
if (!$user) {
    header("Location: index.php");
    exit();
}

// تعريف متغير حالة الملف الشخصي
$is_own_profile = (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $profile_user_id));


// التحقق من حالة المتابعة
$is_following = false;
if (isset($_SESSION['user_id']) && !$is_own_profile) {
    $check_follow = $conn->query("SELECT * FROM Followers 
                                WHERE user_id = $profile_user_id 
                                AND follower_id = {$_SESSION['user_id']}");
    $is_following = ($check_follow->num_rows > 0);
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

// إحصائيات عدد المنشورات والمتابعين
$post_count = $conn->query("SELECT COUNT(*) FROM Posts 
                          WHERE user_id = $profile_user_id AND is_deleted = 0")->fetch_row()[0];
$follower_count = $conn->query("SELECT COUNT(*) FROM Followers 
                              WHERE user_id = $profile_user_id")->fetch_row()[0];
$following_count = $conn->query("SELECT COUNT(*) FROM Followers 
                               WHERE follower_id = $profile_user_id")->fetch_row()[0];
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card profile-card">
                <div class="card-body text-center">
                    <div class="profile-pic-container">
                        <img src="uploads/<?= $user['profile_pic'] ?>" 
                             class="profile-pic rounded-circle mb-3"
                             alt="صورة الملف الشخصي">
                    </div>
                    <h3 class="mb-1"><?= sanitize($user['fullname']) ?></h3>
                    <p class="text-muted mb-3">@<?= sanitize($user['username']) ?></p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <div class="text-center mx-3">
                            <h5 class="mb-0"><?= $post_count ?></h5>
                            <small class="text-muted">منشورات</small>
                        </div>
                        <div class="text-center mx-3">
                            <h5 class="mb-0"><?= $follower_count ?></h5>
                            <small class="text-muted">متابعين</small>
                        </div>
                        <div class="text-center mx-3">
                            <h5 class="mb-0"><?= $following_count ?></h5>
                            <small class="text-muted">يتبع</small>
                        </div>
                    </div>
                    
                    <?php if($is_own_profile): ?>
                        <!-- أزرار الملف الشخصي الخاص -->
                        <a href="edit_profile.php" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-edit"></i> تعديل الملف
                        </a>

                        <button class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-lock"></i> تغيير كلمة المرور
                        </button>
                    <?php else: ?>
                        <!-- زر المتابعة -->
                        <button class="btn btn-<?= $is_following ? 'secondary' : 'primary' ?> w-100" 
                                onclick="toggleFollow(<?= $profile_user_id ?>)" 
                                id="followButton"
                                data-user-id="<?= $profile_user_id ?>">
                            <?= $is_following ? 'الغاء المتابعة' : 'متابعة' ?>
                        </button>

                        <a href="messages.php?with=<?= $profile_user_id ?>" class="btn btn-success w-100">
                        <i class="fas fa-envelope"></i> مراسلة
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">منشورات <?= $is_own_profile ? 'الخاصة بي' : sanitize($user['username']) ?></h5>
                </div>
                <div class="card-body">
                    <?php
                    // استعلام معدل باستخدام Prepared Statement
                    $stmt = $conn->prepare("SELECT * FROM Posts WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
                    $stmt->bind_param("i", $profile_user_id);
                    $stmt->execute();
                    $posts = $stmt->get_result();

                    if ($posts->num_rows > 0): ?>
                        <?php while($post = $posts->fetch_assoc()): ?>
                            <div class="post-item mb-4">
                                <div class="post-content p-3">
                                    <p class="mb-2"><?= sanitize($post['content']) ?></p>
                                    <?php if(!empty($post['media_url'])): ?>
                                        <div class="text-center my-3 post-media-container">
                                            <?php if($post['media_type'] == 'image'): ?>
                                                <img src="uploads/<?= $post['media_url'] ?>" 
                                                    class="img-fluid rounded"
                                                    loading="lazy"
                                                    onclick="showMediaModal('<?= $post['media_url'] ?>', 'image')"
                                                    alt="صورة المنشور">
                                            <?php elseif($post['media_type'] == 'video'): ?>
                                                <video class="img-fluid rounded" controls>
                                                    <source src="uploads/<?= $post['media_url'] ?>" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-meta mt-2 d-flex justify-content-between">
                                        <small class="text-muted">
                                            <?= date('j F Y - H:i', strtotime($post['created_at'])) ?>
                                        </small>
                                        <?php if($is_own_profile): ?>
                                        <div class="post-actions">
                                            <a href="edit_post.php?id=<?= $post['post_id'] ?>" class="text-primary me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_post.php?id=<?= $post['post_id'] ?>" class="text-danger" 
                                                onclick="return confirm('هل أنت متأكد من حذف هذا المنشور؟')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد منشورات لعرضها</p>
                            <?php if($is_own_profile): ?>
                                <a href="index.php" class="btn btn-primary">إنشاء أول منشور</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تغيير كلمة المرور -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تغيير كلمة المرور</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="change_password.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الحالية</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Media Preview -->
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
.profile-card {
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: none;
}

.profile-pic-container {
    position: relative;
    display: inline-block;
}

.profile-pic {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.edit-pic-btn {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    padding: 0;
}

.post-item {
    border-radius: 10px;
    border: 1px solid #eee;
    transition: all 0.3s ease;
}

.post-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.post-media img {
    max-height: 400px;
    width: auto;
    max-width: 100%;
}
</style>

<script>
// دالة المتابعة/الغاء المتابعة
function toggleFollow(userId) {
    fetch('toggle_follow.php?user_id=' + userId)
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if(data.success) {
            const btn = document.getElementById('followButton');
            btn.classList.toggle('btn-primary');
            btn.classList.toggle('btn-secondary');
            btn.textContent = data.is_following ? 'الغاء المتابعة' : 'متابعة';
            window.location.reload();
            
            // تحديث عداد المتابعين إذا كان موجوداً
            const followerCount = document.querySelector('[data-follower-count]');
            if (followerCount) {
                followerCount.textContent = data.new_count;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء عملية المتابعة');
    });
}

function showMediaModal(url, type) {
    const modalContent = document.getElementById('mediaModalContent');
    if(type === 'image') {
        modalContent.innerHTML = `<img src="uploads/${url}" class="img-fluid">`;
    } else if(type === 'video') {
        modalContent.innerHTML = `
            <video class="img-fluid" controls autoplay>
                <source src="uploads/${url}" type="video/mp4">
                Your browser does not support the video tag.
            </video>`;
    }
    const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
    modal.show();
}

</script>

<?php include 'includes/footer.php'; ?>