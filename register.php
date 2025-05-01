<?php include 'includes/header.php'; ?>
<div class="container mt-5" style="max-width: 500px;">
    <div class="card post-card p-4">
        <h3 class="mb-4">إنشاء حساب جديد</h3>
        <form action="process_register.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="fullname" class="form-label">الاسم الكامل</label>
                <input type="text" class="form-control" name="fullname" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">اسم المستخدم</label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-3">
                <label for="profile_pic" class="form-label">صورة الملف الشخصي</label>
                <input type="file" class="form-control" name="profile_pic">
            </div>
            <button type="submit" class="btn btn-primary w-100">تسجيل</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>