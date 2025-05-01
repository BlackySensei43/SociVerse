<?php
include 'includes/config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$post_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// التحقق من ملكية المنشور
$post = $conn->query("SELECT * FROM Posts WHERE post_id = $post_id AND user_id = $user_id")->fetch_assoc();

if (!$post) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = sanitize($_POST['content']);
    
    // معالجة الصورة المرفوعة
    $media_url = $post['media_url'];
    if (!empty($_FILES['media']['name'])) {
        $upload_dir = 'uploads/';
        $file_name = uniqid() . '_' . basename($_FILES['media']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_path)) {
            // حذف الصورة القديمة إذا كانت موجودة
            if (!empty($media_url)) {
                @unlink($upload_dir . $media_url);
            }
            $media_url = $file_name;
        }
    }
    
    $stmt = $conn->prepare("UPDATE Posts SET content = ?, media_url = ? WHERE post_id = ?");
    $stmt->bind_param("ssi", $content, $media_url, $post_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "تم تحديث المنشور بنجاح";
        header("Location: profile.php");
        exit();
    } else {
        $error = "حدث خطأ أثناء التحديث: " . $stmt->error;
    }
}
?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">تعديل المنشور</h4>
        </div>
        <div class="card-body">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form action="edit_post.php?id=<?= $post_id ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">النص</label>
                    <textarea name="content" class="form-control" rows="5" required><?= $post['content'] ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">الصورة المرفقة</label>
                    <?php if(!empty($post['media_url'])): ?>
                        <div class="mb-2">
                            <img src="uploads/<?= $post['media_url'] ?>" class="img-thumbnail" style="max-height: 200px;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="delete_media" id="deleteMedia">
                                <label class="form-check-label" for="deleteMedia">حذف الصورة الحالية</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="media" class="form-control" accept="image/*">
                    <small class="text-muted">اتركه فارغاً للحفاظ على الصورة الحالية</small>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                    <a href="profile.php" class="btn btn-outline-secondary px-4">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>