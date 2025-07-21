<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// تحديد التاريخ
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// جلب الغيابات لهذا التاريخ
$stmt = $pdo->prepare("
    SELECT students.name AS student_name, sections.name AS section_name, levels.name AS level_name,
           SUM(absences.absence_hours) AS total_hours
    FROM absences
    JOIN students ON absences.student_id = students.id
    JOIN sections ON students.section_id = sections.id
    JOIN levels ON sections.level_id = levels.id
    WHERE absences.absence_date = ?
    GROUP BY absences.student_id
    ORDER BY sections.name, students.name
");
$stmt->execute([$date]);
$absences = $stmt->fetchAll();

// حساب النسبة المئوية لكل قسم
$section_hours = [];
$section_totals = [];
foreach ($absences as $row) {
    $section = $row['section_name'];
    $section_hours[$section] = ($section_hours[$section] ?? 0) + $row['total_hours'];
    $section_totals[$section] = ($section_totals[$section] ?? 0) + 1;
}

// حساب الساعات الممكنة = عدد الطلاب في القسم × 7 ساعات
$section_percentages = [];
foreach ($section_hours as $section => $hours) {
    $possible_hours = $section_totals[$section] * 7;
    $percentage = $possible_hours > 0 ? ($hours / $possible_hours) * 100 : 0;
    $section_percentages[$section] = round($percentage, 2);
}

// حساب النسبة المئوية لجميع الأقسام
$total_hours = array_sum($section_hours);
$total_students = array_sum($section_totals);
$total_possible_hours = $total_students * 7;
$total_percentage = $total_possible_hours > 0 ? ($total_hours / $total_possible_hours) * 100 : 0;
$total_percentage = round($total_percentage, 2);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>📅 تقرير الغياب اليومي</title>
    <link rel="stylesheet" href="styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Cairo', sans-serif; direction: rtl; background: #f0f2f5; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #007BFF; color: white; }
        .btn { background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        .btn:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h2>📅 تقرير الغياب اليومي</h2>
    <h3>📆 تاريخ التقرير: <?= htmlspecialchars($date) ?></h3>

    <form method="get" style="text-align:center;">
        <label>📅 اختر التاريخ:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
    </form>
 
    <?php if ($absences): ?>
        <table>
            <thead>
                <tr>
                    <th>👤 اسم الطالب</th>
                    <th>🏫 المستوى</th>
                    <th>📚 القسم</th>
                    <th>⏰ ساعات الغياب</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($absences as $absence): ?>
                    <tr>
                        <td><?= htmlspecialchars($absence['student_name']) ?></td>
                        <td><?= htmlspecialchars($absence['level_name']) ?></td>
                        <td><?= htmlspecialchars($absence['section_name']) ?></td>
                        <td><?= htmlspecialchars($absence['total_hours']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>📊 النسبة المئوية للغياب لكل قسم:</h3>
        <table>
            <thead>
                <tr>
                    <th>📚 القسم</th>
                    <th>⏱️ مجموع ساعات الغياب</th>
                    <th>📈 النسبة المئوية</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($section_percentages as $section => $percentage): ?>
                    <tr>
                        <td><?= htmlspecialchars($section) ?></td>
                        <td><?= htmlspecialchars($section_hours[$section]) ?></td>
                        <td><?= $percentage ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>📈 النسبة الإجمالية للغياب في جميع الأقسام: <?= $total_percentage ?>%</h3>

        <div style="text-align:center;">
            <a href="export_daily_report_pdf.php?date=<?= urlencode($date) ?>" class="btn">🖨️ تصدير PDF</a>
        </div>
    <?php else: ?>
        <p style="text-align:center; color:red;">⚠️ لا توجد غيابات مسجلة لهذا اليوم.</p>
    <?php endif; ?>
    <div style="text-align:center; margin-top:15px;">
        <a href="supervisor_dashboard.php" class="btn" style="background:#007BFF;">🏠 العودة للوحة التحكم</a>
         <a href="weekly_report.php" class="btn" style="background: #91ee3bff;">📅 تقرير الاسبوعي</a>
    </div>
</div>
</body>
</html>
