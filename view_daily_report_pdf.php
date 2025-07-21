<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

// التحقق من الصلاحيات
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['supervisor_id'])) {
    header('Location: index.php');
    exit;
}

// تحديد التاريخ
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// جلب اسم المؤسسة من الإعدادات
$settings = $pdo->query("SELECT institution_name FROM settings LIMIT 1")->fetch();
$institution_name = $settings ? $settings['institution_name'] : "اسم المؤسسة";

// جلب بيانات الغيابات
$stmt = $pdo->prepare("
    SELECT 
        students.name AS student_name,
        sections.name AS section_name,
        SUM(absences.absence_hours) AS total_hours
    FROM absences
    JOIN students ON absences.student_id = students.id
    JOIN sections ON students.section_id = sections.id
    WHERE absences.absence_date = :date
    GROUP BY students.id, sections.id
    ORDER BY sections.name ASC, students.name ASC
");
$stmt->execute(['date' => $date]);
$absences = $stmt->fetchAll();

// تجهيز mpdf
$mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'P']);
$mpdf->SetDirectionality('rtl');

// محتوى التقرير
$html = '
<style>
    body { font-family: "dejavusans"; direction: rtl; }
    h2, h3 { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #444; padding: 6px; text-align: center; }
    th { background-color: #007BFF; color: white; }
    .signature { margin-top: 50px; text-align: left; }
</style>

<h2>'.$institution_name.'</h2>
<h3>📅 تقرير الغياب اليومي - '.htmlspecialchars($date).'</h3>
';

if (count($absences) > 0) {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>👤 اسم الطالب</th>
                <th>🏫 القسم</th>
                <th>⏰ عدد ساعات الغياب</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($absences as $absence) {
        $html .= '
            <tr>
                <td>'.htmlspecialchars($absence['student_name']).'</td>
                <td>'.htmlspecialchars($absence['section_name']).'</td>
                <td>'.htmlspecialchars($absence['total_hours'] ?? 0).'</td>
            </tr>';
    }
    $html .= '
        </tbody>
    </table>';
} else {
    $html .= '<p style="color:red; text-align:center;">⚠️ لا توجد غيابات مسجلة لهذا اليوم.</p>';
}

// توقيع المدير
$html .= '
<div class="signature">
    <p>توقيع المدير: ________________</p>
</div>
';

// إخراج التقرير مباشرة داخل الصفحة
$mpdf->WriteHTML($html);
$mpdf->Output();
exit;
?>
