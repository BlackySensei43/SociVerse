<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// التحقق من وجود محتوى
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $user_id = $_SESSION['user_id'];
    $content = trim($_POST['content'] ?? '');
    $media_type = $_POST['media_type'] ?? 'image'; // نوع الوسائط (صورة أو فيديو)
    $media_url = '';
    $error = '';

    // التحقق من وجود محتوى أو وسائط
    if (empty($content) && empty($_FILES['media']['name'])) {
        $_SESSION['error'] = "يجب إدخال نص أو إرفاق وسائط للمنشور.";
        header("Location: index.php");
        exit;
    }

    // معالجة الملف المرفق إذا وجد
    if (!empty($_FILES['media']['name'])) {
        // تحديد الحد الأقصى للحجم بناءً على نوع الملف
        $max_size = ($media_type == 'video') ? 52428800 : 5242880; // 50MB للفيديو، 5MB للصور
        
        // التحقق من حجم الملف
        if ($_FILES['media']['size'] > $max_size) {
            $_SESSION['error'] = "حجم الملف كبير جداً. الحد الأقصى هو " . 
                                ($media_type == 'video' ? "50MB للفيديو" : "5MB للصور");
            header("Location: index.php");
            exit;
        }

        // التحقق من نوع الملف
        $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
        
        $file_type = $_FILES['media']['type'];
        
        if ($media_type == 'image' && !in_array($file_type, $allowed_image_types)) {
            $_SESSION['error'] = "نوع الملف غير مسموح به. الأنواع المسموح بها للصور هي: JPEG, PNG, GIF, WEBP";
            header("Location: index.php");
            exit;
        } elseif ($media_type == 'video' && !in_array($file_type, $allowed_video_types)) {
            $_SESSION['error'] = "نوع الملف غير مسموح به. الأنواع المسموح بها للفيديو هي: MP4, WebM, OGG";
            header("Location: index.php");
            exit;
        }

        // إنشاء اسم فريد للملف
        $file_ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('post_') . '.' . $file_ext;
        $upload_path = __DIR__ . '/uploads/' . $unique_filename;

        // نقل الملف إلى مجلد التحميلات
        if (move_uploaded_file($_FILES['media']['tmp_name'], $upload_path)) {
            $media_url = $unique_filename;
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء رفع الملف. يرجى المحاولة مرة أخرى.";
            header("Location: index.php");
            exit;
        }
    }

    // إضافة المنشور إلى قاعدة البيانات
    try {
        $stmt = $conn->prepare("INSERT INTO Posts (user_id, content, media_url, media_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $content, $media_url, $media_type);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم نشر المنشور بنجاح.";
            header("Location: index.php");
            exit(); // Make sure to exit after redirect
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء نشر المنشور. يرجى المحاولة مرة أخرى.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}

header("Location: index.php");