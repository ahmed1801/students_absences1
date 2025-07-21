<?php
session_start();
require 'db.php';

// حماية الجلسة
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// حذف جميع السجلات
if (isset($_POST['delete_all_logs'])) {
    $pdo->exec("DELETE FROM logs");

    // تسجيل عملية الحذف في السجلات
    $log_stmt = $pdo->prepare("INSERT INTO logs (admin_id, action_type, details) VALUES (?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['admin_id'],
        'حذف الكل',
        'تم حذف جميع سجلات النظام بواسطة المدير'
    ]);

    $_SESSION['msg'] = "✅ تم حذف جميع السجلات بنجاح.";
    header("Location: logs.php");
    exit;
}

// جلب السجلات
$stmt = $pdo->query("
    SELECT logs.*, admins.username AS admin_name
    FROM logs
    LEFT JOIN admins ON logs.admin_id = admins.id
    ORDER BY logs.created_at DESC
");
$logs = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="styles.css">
<title>📋 سجل العمليات</title>
<style>
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
th { background: #f0f0f0; }
button.delete { background: red; color: white; padding: 8px 12px; border: none; cursor: pointer; border-radius: 5px; }
button.delete:hover { background: darkred; }
</style>
</head>
<body>
<div class="container">
    <h1>📋 سجل العمليات</h1>

    <?php if (isset($_SESSION['msg'])): ?>
        <p><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
    <?php endif; ?>

    <?php if (count($logs) > 0): ?>
        <form method="post" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف جميع السجلات؟ هذا الإجراء لا يمكن التراجع عنه.');">
            <button type="submit" name="delete_all_logs" class="delete">🗑️ حذف جميع السجلات</button>
        </form>
        <br>
        <table>
            <tr>
                <th>#</th>
                <th>المستخدم</th>
                <th>نوع العملية</th>
                <th>تفاصيل</th>
                <th>التاريخ</th>
            </tr>
            <?php foreach ($logs as $index => $log): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($log['admin_name'] ?? 'غير محدد') ?></td>
                    <td><?= htmlspecialchars($log['action_type'] ?? '-') ?></td>
                    <td><?= nl2br(htmlspecialchars($log['details'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>⚠️ لا توجد سجلات حاليا.</p>
    <?php endif; ?>

    <br>
    <a href="admin_dashboard.php"><button>🏠 رجوع</button></a>
</div>
</body>
</html>

