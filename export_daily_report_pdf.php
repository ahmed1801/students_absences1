<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

// الحصول على اسم المؤسسة
$settings_stmt = $pdo->query("SELECT institution_name FROM settings LIMIT 1");
$settings = $settings_stmt->fetch();
$institution_name = $settings['institution_name'] ?? 'اسم المؤسسة';

// استقبال البيانات
$date = $_GET['date'] ?? date('Y-m-d');
$section_id = $_GET['section_id'] ?? 'all';

// إعداد البيانات
if ($section_id == 'all') {
    // الأقسام التابعة للمشرف
    session_start();
    $supervisor_id = $_SESSION['supervisor_id'] ?? null;
    if (!$supervisor_id) {
        die("⚠️ لا يوجد صلاحية للوصول.");
    }
    $sections_stmt = $pdo->prepare("SELECT section_id FROM supervisor_sections WHERE supervisor_id = ?");
    $sections_stmt->execute([$supervisor_id]);
    $section_ids = array_column($sections_stmt->fetchAll(), 'section_id');
    $placeholders = implode(',', array_fill(0, count($section_ids), '?'));

    $query = "
        SELECT students.name AS student_name, sections.name AS section_name, levels.name AS level_name,
               SUM(absences.absence_hours) AS total_hours
        FROM absences
        JOIN students ON absences.student_id = students.id
        JOIN sections ON students.section_id = sections.id
        JOIN levels ON sections.level_id = levels.id
        WHERE absences.absence_date = ? AND absences.section_id IN ($placeholders)
        GROUP BY absences.student_id
        ORDER BY students.name
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$date], $section_ids));
} else {
    $stmt = $pdo->prepare("
        SELECT students.name AS student_name, sections.name AS section_name, levels.name AS level_name,
               SUM(absences.absence_hours) AS total_hours
        FROM absences
        JOIN students ON absences.student_id = students.id
        JOIN sections ON students.section_id = sections.id
        JOIN levels ON sections.level_id = levels.id
        WHERE absences.absence_date = ? AND sections.id = ?
        GROUP BY absences.student_id
        ORDER BY students.name
    ");
    $stmt->execute([$date, $section_id]);
}
$absences = $stmt->fetchAll();

// إعداد mPDF
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'default_font' => 'dejavusans'
]);

// إنشاء HTML للطباعة
$html = '
<html lang="ar" dir="rtl">
<head>
<style>
body { font-family: "dejavusans"; direction: rtl; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #000; padding: 6px; text-align: center; }
th { background: #007BFF; color: white; }
.header { text-align: center; margin-bottom: 10px; }
.footer { text-align: center; margin-top: 30px; font-size: 14px; }
</style>
</head>
<body>
<div class="header">
    <h2>' . htmlspecialchars($institution_name) . '</h2>
    <h3>📋 تقرير الغياب اليومي - ' . htmlspecialchars($date) . '</h3>
</div>';

if ($absences && count($absences) > 0) {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>👤 اسم الطالب</th>
                <th>🏫 المستوى</th>
                <th>📚 القسم</th>
                <th>⏰ مجموع الساعات</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($absences as $absence) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($absence['student_name']) . '</td>
                <td>' . htmlspecialchars($absence['level_name']) . '</td>
                <td>' . htmlspecialchars($absence['section_name']) . '</td>
                <td>' . htmlspecialchars($absence['total_hours']) . '</td>
            </tr>';
    }
    $html .= '
        </tbody>
    </table>';
} else {
    $html .= '<p style="text-align:center; color:red;">⚠️ لا توجد غيابات مسجلة.</p>';
}

$html .= '
<div class="footer">
    <p>🖊️ توقيع المدير: ________________</p>
    <p>🔖 ختم المؤسسة</p>
</div>
</body>
</html>';

// طباعة PDF
$mpdf->WriteHTML($html);
$mpdf->Output('تقرير الغياب اليومي.pdf', 'I');
exit;
