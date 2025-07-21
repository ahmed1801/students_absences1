<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// تحديد الشهر الحالي افتراضيًا
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// بداية ونهاية الشهر
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

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

// عدد أيام الدوام التقديري للشهر = 22 يوم
$days_in_month = 22;
$section_percentages = [];
foreach ($section_hours as $section => $hours) {
    $student_count = $section_student_counts[$section] ?? 0;
    $possible_hours = $student_count * 7 * $days_in_month;
    $percentage = $possible_hours > 0 ? ($hours / $possible_hours) * 100 : 0;
    $section_percentages[$section] = round($percentage, 2);
}

// النسبة الإجمالية
$total_hours = array_sum($section_hours);
$total_students = array_sum($section_student_counts);
$total_possible_hours = $total_students * 7 * $days_in_month;
$total_percentage = $total_possible_hours > 0 ? ($total_hours / $total_possible_hours) * 100 : 0;
$total_percentage = round($total_percentage, 2);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>🗓️ تقرير الغياب الشهري</title>
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
    <h2>🗓️ تقرير الغياب الشهري</h2>
    <h3>📅 شهر: <?= htmlspecialchars($month) ?></h3>

    <form method="get" style="text-align:center;">
        <label>🗓️ اختر الشهر:</label>
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
        
    </form>
    <div style="text-align:center; margin-top:15px;">
        <button type="submit" class="btn">📊 عرض التقرير</button>
        <a href="supervisor_dashboard.php" class="btn" style="background:#007BFF;">🏠 العودة للوحة التحكم</a>
        <a href="yearly_report.php" class="btn" style="background: #91ee3bff;">📅 تقرير السنوي </a>
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
            <a href="export_monthly_report_pdf.php?month=<?= urlencode($month) ?>" class="btn">🖨️ تصدير PDF</a>
        </div>
    <?php else: ?>
        <p style="text-align:center; color:red;">⚠️ لا توجد غيابات مسجلة لهذا الشهر.</p>
    <?php endif; ?>
    <div style="text-align:center; margin-top:15px;">
        <a href="supervisor_dashboard.php" class="btn" style="background:#007BFF;">🏠 العودة للوحة التحكم</a>
        <a href="yearly_report.php" class="btn" style="background: #91ee3bff;">📅 تقرير السنوي </a>
    </div>
</div>
</body>
</html>
