<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // حذف المواد المرتبطة بالأستاذ
    $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?")->execute([$id]);

    // حذف الأستاذ
    $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->execute([$id]);

    // تسجيل في السجل
    $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    $logStmt->execute([$_SESSION['admin_id'], "حذف", "teachers", $id]);

    $_SESSION['msg'] = "✅ تم حذف الأستاذ بنجاح.";
}

header("Location: manage_teachers.php");
exit;
