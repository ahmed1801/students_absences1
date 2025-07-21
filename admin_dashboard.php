<?php
session_start();
require 'db.php';

// التحقق من دخول المدير
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// حذف جميع التقارير عند الطلب
if (isset($_GET['delete_reports']) && $_GET['delete_reports'] == 1) {
    $pdo->exec("DELETE FROM reports");
    $_SESSION['msg'] = "<p style='color:green; text-align:center;'>✅ تم حذف جميع التقارير بنجاح.</p>";
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>🏠 لوحة تحكم المدير</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<style>
body { font-family: 'Cairo', sans-serif; direction: rtl; background: #f0f2f5; }
.container { max-width: 700px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
h2 { text-align: center; }
.cards { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin-top: 20px; }
.card { background: #007BFF; color: white; padding: 15px; border-radius: 8px; text-align: center; flex: 1 1 200px; text-decoration: none; }
.card:hover { background: #0056b3; }
.delete-btn { background: #dc3545; }
.delete-btn:hover { background: #a71d2a; }
</style>
</head>
<body>
<div class="container">
    <h2>🏠 لوحة تحكم المدير</h2>

    <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

    <div class="cards">
        <a href="manage_students.php" class="card">👨‍🎓 إدارة الطلاب</a>
        <a href="manage_teachers.php" class="card">👨‍🏫 إدارة الأساتذة</a>
        <a href="manage_supervisors.php" class="card">🧑‍💼 إدارة المشرفين</a>
        <a href="manage_levels.php" class="card">📚 إدارة المستويات</a>
        <a href="manage_sections.php" class="card">🏘️ إدارة الأقسام</a>
        <a href="manage_subjects.php" class="card">📖 إدارة المواد</a>
        <a href="daily_report.php" class="card">📄 التقرير اليومي </a>
        <a href="view_reports.php" class="card">📄 التقارير المستلمة</a>
        <a href="logs.php" class="card">📝 السجل</a>
        <a href="logout.php" class="card" style="background:#6c757d;">🚪 تسجيل الخروج</a>
        <a href="admin_dashboard.php?delete_reports=1" class="card delete-btn" onclick="return confirm('⚠️ هل أنت متأكد من حذف جميع التقارير؟ هذا الإجراء لا يمكن التراجع عنه.');">🗑️ حذف جميع التقارير</a>
    </div>
</div>
</body>
</html>
    