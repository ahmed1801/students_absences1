<?php
session_start();
require 'db.php';

if (!isset($_SESSION['supervisor_id'])) {
    header('Location: index.php');
    exit;
}

$supervisor_id = $_SESSION['supervisor_id'];
$supervisor_name = $_SESSION['supervisor_name'] ?? 'مشرف';

// جلب الأقسام
$stmt = $pdo->prepare("
    SELECT sections.id, sections.name AS section_name, levels.name AS level_name
    FROM supervisor_sections
    JOIN sections ON supervisor_sections.section_id = sections.id
    JOIN levels ON sections.level_id = levels.id
    WHERE supervisor_sections.supervisor_id = ?
    ORDER BY levels.id, sections.name
");
$stmt->execute([$supervisor_id]);
$sections = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>لوحة تحكم المشرف</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<style>
body { font-family: 'Cairo', sans-serif; background: #f0f2f5; direction: rtl; }
.container { max-width: 950px; margin: auto; padding: 20px; }
.header { text-align: center; margin-bottom: 20px; }
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
.card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 8px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.2s;
}
.card:hover { transform: translateY(-5px); }
.card h3 { margin: 10px 0; color: #333; }
.card p { margin: 0; color: #555; }
.btn {
    display: inline-block;
    margin: 5px 5px;
    padding: 8px 12px;
    background: #007BFF;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
}
.btn:hover { background: #0056b3; }
.report-buttons { text-align: center; margin-bottom: 20px; }
.report-buttons .btn { background: #28a745; }
.report-buttons .btn:hover { background: #218838; }
.logout-btn { background: #dc3545; }
.logout-btn:hover { background: #c82333; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>👤 مرحبًا <?= htmlspecialchars($supervisor_name) ?> في لوحة التحكم</h1>
        <p>⚙️ يمكنك إدارة الأقسام المسندة إليك وتسجيل الغياب وعرض التقارير بسهولة</p>
    </div>

    <!-- أزرار التقارير العامة -->
    <div class="report-buttons">
        <a href="weekly_report.php" class="btn">📅 تقرير أسبوعي</a>
        <a href="monthly_report.php" class="btn">🗓️ تقرير شهري</a>
        <a href="yearly_report.php" class="btn">📆 تقرير سنوي</a>
    </div>

    <?php if ($sections): ?>
    <div class="cards">
        <?php foreach ($sections as $section): ?>
            <div class="card">
                <h3><?= htmlspecialchars($section['level_name']) ?> - <?= htmlspecialchars($section['section_name']) ?></h3>
                <a href="record_absence.php?section_id=<?= $section['id'] ?>" class="btn">📋 تسجيل غياب</a>
                <a href="daily_section_report.php?section_id=<?= $section['id'] ?>&date=<?= date('Y-m-d') ?>" class="btn" style="background:#28a745;">📊 تقرير اليوم</a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="color:red; text-align:center;">⚠️ لا توجد أقسام مسندة لك حاليًا.</p>
    <?php endif; ?>

    <div style="text-align:center; margin-top:20px;">
        <a href="logout.php" class="btn logout-btn">🚪 تسجيل الخروج</a>
    </div>
</div>
</body>
</html>
