<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // التحقق من كلمة المرور الحالية
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $_SESSION['success'] = "تم تغيير كلمة المرور بنجاح";
                        header("Location: profile.php");
                        exit();
                    } else {
                        $error = "حدث خطأ أثناء تحديث كلمة المرور";
                    }
                } else {
                    $error = "كلمة المرور يجب أن تكون 8 أحرف على الأقل";
                }
            } else {
                $error = "كلمة المرور الجديدة غير متطابقة";
            }
        } else {
            $error = "كلمة المرور الحالية غير صحيحة";
        }
    } else {
        $error = "المستخدم غير موجود";
    }
}

// إذا لم يكن الطلب POST أو حدث خطأ
$_SESSION['error'] = $error;
header("Location: profile.php");
exit();
?>