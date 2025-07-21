<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_levels.php');
    exit;
}

$id = (int)$_GET['id'];

// حذف المستوى
$stmt = $pdo->prepare("DELETE FROM levels WHERE id = ?");
$stmt->execute([$id]);

// تسجيل في السجلات
$logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, 'حذف', 'levels', ?, NOW())");
$logStmt->execute([$_SESSION['admin_id'], $id]);

$_SESSION['msg'] = "✅ تم حذف المستوى بنجاح.";
header("Location: manage_levels.php");
exit;
?>
