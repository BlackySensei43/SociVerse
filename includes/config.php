<?php
// التحقق من عدم بدء الجلسة مسبقاً
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات قاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "root";
$db = "SociVerse";

$conn = new mysqli($host, $user, $pass, $db, 3306);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'فشل الاتصال بقاعدة البيانات']));
}

// تضمين ملف الدوال
require_once 'functions.php';

// دالة لتنظيف المدخلات
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// إعدادات أخرى
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);
?>