<?php
session_start();
require 'db.php';

// التحقق من صلاحية المدير
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$report_id = $_GET['id'] ?? null;
if (!$report_id) {
    die("❌ معرف التقرير غير محدد.");
}

// جلب اسم المؤسسة من الإعدادات
$settings_stmt = $pdo->query("SELECT institution_name FROM settings LIMIT 1");
$settings = $settings_stmt->fetch();
$institution_name = $settings['institution_name'] ?? 'اسم المؤسسة';

// جلب التقرير مع اسم المشرف
$stmt = $pdo->prepare("SELECT reports.*, supervisors.name AS supervisor_name FROM reports LEFT JOIN supervisors ON reports.supervisor_id = supervisors.id WHERE reports.id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    die("❌ التقرير غير موجود.");
}

// فك تشفير بيانات التقرير
$report_data = json_decode($report['report_data'], true);

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>📄 تفاصيل التقرير</title>
    <link rel="stylesheet" href="styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Cairo', sans-serif; direction: rtl; background: #f0f2f5; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #007BFF; color: white; }
        .btn { background: #28a745; color: white; padding: 7px 12px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h2>🏫 <?= htmlspecialchars($institution_name) ?></h2>
    <h3>📄 تفاصيل التقرير المرسل بتاريخ <?= htmlspecialchars($report['report_date']) ?></h3>

    <?php if ($report_data && count($report_data) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>👤 اسم الطالب</th>
                    <th>🏫 المستوى</th>
                    <th>📚 القسم</th>
                    <th>🧑‍💼 المشرف</th>
                    <th>⏰ مجموع ساعات الغياب</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                    <?php
                    $student_id = $row['student_id'] ?? null;
                    $total_hours = $row['total_hours'] ?? 0;

                    $student_stmt = $pdo->prepare("
                        SELECT students.name AS student_name, sections.name AS section_name, levels.name AS level_name
                        FROM students
                        JOIN sections ON students.section_id = sections.id
                        JOIN levels ON sections.level_id = levels.id
                        WHERE students.id = ?
                    ");
                    $student_stmt->execute([$student_id]);
                    $student = $student_stmt->fetch();
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($student['student_name'] ?? 'غير محدد') ?></td>
                        <td><?= htmlspecialchars($student['level_name'] ?? 'غير محدد') ?></td>
                        <td><?= htmlspecialchars($student['section_name'] ?? 'غير محدد') ?></td>
                        <td><?= htmlspecialchars($report['supervisor_name'] ?? 'غير محدد') ?></td>
                        <td><?= htmlspecialchars($total_hours) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:red; text-align:center;">⚠️ لا توجد بيانات متاحة في هذا التقرير.</p>
    <?php endif; ?>

    <div style="text-align:center; margin-top:15px;">
        <a href="view_reports.php" class="btn" style="background:#007BFF;">🔙 العودة للتقارير</a>
    </div>
</div>
</body>
</html>
