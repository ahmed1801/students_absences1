<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// التأكد من وجود معرف القسم
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_sections.php');
    exit;
}

$section_id = $_GET['id'];

// التأكد من أن القسم موجود
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
$stmt->execute([$section_id]);
$section = $stmt->fetch();

if (!$section) {
    $_SESSION['msg'] = "❌ القسم غير موجود.";
    header('Location: manage_sections.php');
    exit;
}

// حذف القسم
$deleteStmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
$deleteStmt->execute([$section_id]);

// تسجيل في السجلات
$logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
$logStmt->execute([$_SESSION['admin_id'], 'حذف', 'sections', $section_id]);

$_SESSION['msg'] = "✅ تم حذف القسم بنجاح.";
header('Location: manage_sections.php');
exit;
?>
