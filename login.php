<?php include 'includes/header.php'; ?>
<div class="container mt-5" style="max-width: 500px;">
    <div class="card post-card p-4">
        <h3 class="mb-4">تسجيل الدخول</h3>
        <form action="process_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">اسم المستخدم</label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">دخول</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>