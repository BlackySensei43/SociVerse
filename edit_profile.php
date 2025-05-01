<?php
include 'includes/config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    
    // معالجة صورة الملف الشخصي
    $profile_pic = $user['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $upload_dir = 'uploads/';
        $file_name = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {
            // حذف الصورة القديمة إذا لم تكن الصورة الافتراضية
            if ($profile_pic != 'default-profile.jpg') {
                @unlink($upload_dir . $profile_pic);
            }
            $profile_pic = $file_name;
        }
    }
    
    $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, profile_pic=? WHERE id=?");
    $stmt->bind_param("ssssi", $fullname, $username, $email, $profile_pic, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "تم تحديث الملف الشخصي بنجاح";
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
            <h4 class="mb-0">تعديل الملف الشخصي</h4>
        </div>
        <div class="card-body">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="fullname" class="form-control" value="<?= $user['fullname'] ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control" value="<?= $user['username'] ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">صورة الملف الشخصي</label>
                    <input type="file" name="profile_pic" class="form-control" accept="image/*">
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