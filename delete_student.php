<?php
session_start();
require 'db.php';

// حماية الوصول
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب معرف الطالب
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// التحقق من وجود الطالب قبل الحذف
$stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if ($student) {
    // حذف الطالب
    $del_stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $del_stmt->execute([$student_id]);

    // تسجيل في logs
    $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, admin_id, created_at) VALUES (?, ?, ?, NOW())");
    $log_stmt->execute(['حذف طالب', "حذف الطالب: {$student['name']} (ID: $student_id)", $_SESSION['admin_id']]);

    $_SESSION['msg'] = "✅ تم حذف الطالب بنجاح.";
} else {
    $_SESSION['msg'] = "❌ الطالب غير موجود أو تم حذفه سابقًا.";
}

// إعادة التوجيه
header("Location: manage_students.php");
exit;
?>
