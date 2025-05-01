<?php
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // معالجة صورة الملف الشخصي
    $profile_pic = 'default-profile.jpg';
    if (isset($_FILES['profile_pic'])) {
        $upload_dir = 'uploads/';
        $file_name = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {
            $profile_pic = $file_name;
        }
    }
    
    // إدخال المستخدم في قاعدة البيانات
    $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, profile_pic) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fullname, $username, $email, $password, $profile_pic);
    
    if ($stmt->execute()) {
        header("Location: login.php?success=1");
    } else {
        header("Location: register.php?error=exists");
    }
    exit();
} else {
    header("Location: register.php");
    exit();
}
?>