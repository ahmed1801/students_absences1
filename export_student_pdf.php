<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب بيانات الطالب
$stmt = $pdo->prepare("
    SELECT students.*, sections.name AS section_name, levels.name AS level_name 
    FROM students 
    JOIN sections ON students.section_id = sections.id 
    JOIN levels ON sections.level_id = levels.id 
    WHERE students.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("❌ الطالب غير موجود.");
}

// جلب الغيابات
$absencesStmt = $pdo->prepare("
    SELECT absence_date, hours_time 
    FROM absences 
    WHERE student_id = ?
    ORDER BY absence_date DESC
");
$absencesStmt->execute([$student_id]);
$absences = $absencesStmt->fetchAll();

// حساب مجموع الساعات
$total_hours = 0;
foreach ($absences as $absence) {
    $times = explode('-', str_replace(' ', '', $absence['hours_time']));
    if (count($times) == 2) {
        $start = strtotime($times[0]);
        $end = strtotime($times[1]);
        if ($start !== false && $end !== false && $end > $start) {
            $total_hours += ($end - $start) / 3600;
        }
    }
}

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'default_font' => 'dejavusans',
    'mirrorMargins' => true,
    'directionality' => 'rtl'
]);

$html = '
<style>
body { font-family: dejavusans; direction: rtl; }
h2 { text-align: center; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #444; padding: 6px; text-align: center; }
.footer { margin-top: 30px; text-align: center; }
.signature { margin-top: 50px; text-align: left; }
</style>

<h2>📝 تقرير الغيابات للطالب</h2>
<p><strong>👤 الاسم:</strong> ' . htmlspecialchars($student['name']) . '</p>
<p><strong>🏫 المستوى:</strong> ' . htmlspecialchars($student['level_name']) . '</p>
<p><strong>📚 القسم:</strong> ' . htmlspecialchars($student['section_name']) . '</p>
<p><strong>📱 الهاتف:</strong> ' . htmlspecialchars($student['phone'] ?? '-') . '</p>
<p><strong>✉️ البريد:</strong> ' . htmlspecialchars($student['email'] ?? '-') . '</p>
<h3>📊 تفاصيل الغيابات</h3>
<table>
<tr>
<th>📅 التاريخ</th>
<th>⏱️ المواعيد</th>
</tr>';

if (count($absences) > 0) {
    foreach ($absences as $absence) {
        $html .= '<tr>
            <td>' . htmlspecialchars($absence['absence_date']) . '</td>
            <td>' . htmlspecialchars($absence['hours_time']) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="2">✅ لا يوجد غيابات لهذا الطالب.</td></tr>';
}

$html .= '</table>
<p><strong>🔢 مجموع الساعات المقدرة:</strong> ' . number_format($total_hours, 2) . ' ساعة</p>
<div class="signature">
<p>مدير المؤسسة:</p>
<img src="stamp.png" alt="ختم" width="100">
<p>.............................</p>
</div>
';

$mpdf->WriteHTML($html);
$mpdf->Output("student_report.pdf", "I");
