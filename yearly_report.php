<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// تحديد السنة الحالية افتراضيًا
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// تحديد بداية ونهاية السنة
$start_date = $year . '-01-01';
$end_date = $year . '-12-31';

// جلب الغيابات
$stmt = $pdo->prepare("
    SELECT students.name AS student_name, sections.name AS section_name, levels.name AS level_name,
           SUM(absences.absence_hours) AS total_hours
    FROM absences
    JOIN students ON absences.student_id = students.id
    JOIN sections ON students.section_id = sections.id
    JOIN levels ON sections.level_id = levels.id
    WHERE absences.absence_date BETWEEN ? AND ?
    GROUP BY absences.student_id
    ORDER BY sections.name, students.name
");
$stmt->execute([$start_date, $end_date]);
$absences = $stmt->fetchAll();

// حساب عدد الطلاب في كل قسم
$section_student_counts_stmt = $pdo->query("
    SELECT sections.name AS section_name, COUNT(students.id) AS student_count
    FROM sections
    LEFT JOIN students ON students.section_id = sections.id
    GROUP BY sections.id
");
$section_student_counts = [];
while ($row = $section_student_counts_stmt->fetch()) {
    $section_student_counts[$row['section_name']] = $row['student_count'];
}

// حساب النسبة المئوية لكل قسم
$section_hours = [];
foreach ($absences as $row) {
    $section = $row['section_name'];
    $section_hours[$section] = ($section_hours[$section] ?? 0) + $row['total_hours'];
}

// السنة الدراسية = تقريبا 9 أشهر × 22 يوم عمل × 7 ساعات = 1386 ساعة للطالب
$days_in_year = 198; // 9 أشهر * 22 يوم
$section_percentages = [];
foreach ($section_hours as $section => $hours) {
    $student_count = $section_student_counts[$section] ?? 0;
    $possible_hours = $student_count * 7 * $days_in_year;
    $percentage = $possible_hours > 0 ? ($hours / $possible_hours) * 100 : 0;
    $section_percentages[$section] = round($percentage, 2);
}

// النسبة الإجمالية
$total_hours = array_sum($section_hours);
$total_students = array_sum($section_student_counts);
$total_possible_hours = $total_students * 7 * $days_in_year;
$total_percentage = $total_possible_hours > 0 ? ($total_hours / $total_possible_hours) * 100 : 0;
$total_percentage = round($total_percentage, 2);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>🗓️ تقرير الغياب السنوي</title>
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
    <h2>🗓️ تقرير الغياب السنوي</h2>
    <h3>📅 السنة: <?= htmlspecialchars($year) ?></h3>

    <form method="get" style="text-align:center;">
        <label>🗓️ اختر السنة:</label>
        <input type="number" name="year" value="<?= htmlspecialchars($year) ?>" min="2020" max="<?= date('Y') ?>">
    </form>
    <div style="text-align:center; margin-top:15px;">
        <a href="supervisor_dashboard.php" class="btn" style="background:#007BFF;">🏠 العودة للوحة التحكم</a>
        <button type="submit" class="btn">📊 عرض التقرير</button>
    </div>
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
                    <th>👥 عدد التلاميذ</th>
                    <th>⏱️ مجموع ساعات الغياب</th>
                    <th>📈 النسبة المئوية</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($section_percentages as $section => $percentage): ?>
                    <tr>
                        <td><?= htmlspecialchars($section) ?></td>
                        <td><?= htmlspecialchars($section_student_counts[$section] ?? 0) ?></td>
                        <td><?= htmlspecialchars($section_hours[$section]) ?></td>
                        <td><?= $percentage ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>📈 النسبة الإجمالية للغياب لجميع الأقسام: <?= $total_percentage ?>%</h3>

        <div style="text-align:center;">
            <a href="export_yearly_report_pdf.php?year=<?= urlencode($year) ?>" class="btn">🖨️ تصدير PDF</a>
        </div>
    <?php else: ?>
        <p style="text-align:center; color:red;">⚠️ لا توجد غيابات مسجلة لهذه السنة.</p>
    <?php endif; ?>
    <div style="text-align:center; margin-top:15px;">
        <a href="supervisor_dashboard.php" class="btn" style="background:#007BFF;">🏠 العودة للوحة التحكم</a>
    </div>
</div>
</body>
</html>
