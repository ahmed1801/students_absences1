<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

// تحديد الشهر
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// إعداد mPDF
$mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans', 'orientation' => 'P']);
$mpdf->SetDirectionality('rtl');

// جلب البيانات
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

// حساب عدد التلاميذ في كل قسم
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

// حساب النسبة المئوية
$days_in_month = 22;
$section_hours = [];
foreach ($absences as $row) {
    $section = $row['section_name'];
    $section_hours[$section] = ($section_hours[$section] ?? 0) + $row['total_hours'];
}

$section_percentages = [];
foreach ($section_hours as $section => $hours) {
    $student_count = $section_student_counts[$section] ?? 0;
    $possible_hours = $student_count * 7 * $days_in_month;
    $percentage = $possible_hours > 0 ? ($hours / $possible_hours) * 100 : 0;
    $section_percentages[$section] = round($percentage, 2);
}

$total_hours = array_sum($section_hours);
$total_students = array_sum($section_student_counts);
$total_possible_hours = $total_students * 7 * $days_in_month;
$total_percentage = $total_possible_hours > 0 ? ($total_hours / $total_possible_hours) * 100 : 0;
$total_percentage = round($total_percentage, 2);

// إعداد المحتوى
$html = "
<h2 style='text-align:center;'>🗓️ تقرير الغياب الشهري</h2>
<h3 style='text-align:center;'>📅 شهر: {$month}</h3>
<table border='1' cellpadding='8' cellspacing='0' width='100%'>
<thead>
<tr style='background:#007BFF; color:white;'>
    <th>📚 القسم</th>
    <th>👥 عدد التلاميذ</th>
    <th>⏰ مجموع ساعات الغياب</th>
    <th>📈 النسبة المئوية</th>
</tr>
</thead>
<tbody>";
foreach ($section_percentages as $section => $percentage) {
    $html .= "<tr>
        <td>{$section}</td>
        <td>" . ($section_student_counts[$section] ?? 0) . "</td>
        <td>{$section_hours[$section]}</td>
        <td>{$percentage}%</td>
    </tr>";
}
$html .= "
<tr style='background:#f0f0f0; font-weight:bold;'>
    <td colspan='2'>📊 الإجمالي لجميع الأقسام</td>
    <td>{$total_hours}</td>
    <td>{$total_percentage}%</td>
</tr>
</tbody>
</table>
<br><br>
<p style='text-align:center;'>📌 اسم المؤسسة: <strong>مدرسة الإبداع</strong></p>
<p style='text-align:center;'>✍️ توقيع المدير: _______________</p>
";

// تصدير
$mpdf->WriteHTML($html);
$mpdf->Output("monthly_report_{$month}.pdf", 'I');
exit;
?>
