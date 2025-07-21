<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$supervisor_id = $_GET['id'] ?? null;

if ($supervisor_id) {
    // التحقق من وجود المشرف
    $stmt = $pdo->prepare("SELECT * FROM supervisors WHERE id = ?");
    $stmt->execute([$supervisor_id]);
    $supervisor = $stmt->fetch();

    if ($supervisor) {
        // حذف الأقسام المرتبطة
        $pdo->prepare("DELETE FROM supervisor_section WHERE supervisor_id = ?")->execute([$supervisor_id]);

        // حذف المشرف
        $pdo->prepare("DELETE FROM supervisors WHERE id = ?")->execute([$supervisor_id]);

        // تسجيل العملية في logs
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], 'حذف', 'supervisors', $supervisor_id]);

        $_SESSION['msg'] = "✅ تم حذف المشرف بنجاح.";
    } else {
        $_SESSION['msg'] = "❌ المشرف غير موجود.";
    }
} else {
    $_SESSION['msg'] = "❌ معرف المشرف غير محدد.";
}

header('Location: manage_supervisors.php');
exit;
?>
