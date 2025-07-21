<?php
session_start();
require 'db.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// حذف تقرير محدد عند الطلب
if (isset($_GET['delete_report_id'])) {
    $report_id = intval($_GET['delete_report_id']);
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    if ($stmt->execute([$report_id])) {
        $_SESSION['msg'] = "<p style='color:green; text-align:center;'>✅ تم حذف التقرير بنجاح.</p>";
    } else {
        $_SESSION['msg'] = "<p style='color:red; text-align:center;'>❌ فشل في حذف التقرير، حاول مجددًا.</p>";
    }
    header("Location: view_reports.php");
    exit;
}

// جلب التقارير المرسلة
$stmt = $pdo->prepare("
    SELECT reports.*, supervisors.name AS supervisor_name
    FROM reports
    LEFT JOIN supervisors ON reports.supervisor_id = supervisors.id
    ORDER BY reports.id DESC
");
$stmt->execute();
$reports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>📄 التقارير المرسلة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: 'Cairo', sans-serif; direction: rtl; background: #f0f2f5; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #007BFF; color: white; }
        .btn { background: #28a745; color: white; padding: 7px 12px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #218838; }
        .delete-btn { background: #dc3545; }
        .delete-btn:hover { background: #a71d2a; }
    </style>
</head>
<body>
<div class="container">
    <h2>📄 التقارير المرسلة</h2>

    <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

    <?php if ($reports): ?>
        <table>
            <thead>
                <tr>
                    <th>📅 تاريخ الإرسال</th>
                    <th>👤 اسم المشرف</th>
                    <th>📄 عرض التفاصيل</th>
                    <th>🗑️ حذف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= htmlspecialchars($report['report_date']) ?></td>
                    <td><?= htmlspecialchars($report['supervisor_name'] ?? 'غير محدد') ?></td>
                    <td>
                        <a href="view_report_details.php?id=<?= $report['id'] ?>" class="btn">👁️ عرض</a>
                    </td>
                    <td>
                        <a href="view_reports.php?delete_report_id=<?= $report['id'] ?>" 
                           class="btn delete-btn"
                           onclick="return confirm('⚠️ هل أنت متأكد أنك تريد حذف هذا التقرير؟');">
                            🗑️ حذف
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:red; text-align:center;">⚠️ لا توجد تقارير مرسلة حاليًا.</p>
    <?php endif; ?>

    <div style="text-align:center; margin-top:15px;">
        <a href="admin_dashboard.php" class="btn" style="background:#007BFF;">🏠 العودة للوحة التحكم</a>
    </div>
</div>
</body>
</html>
