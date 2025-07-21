<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

$filter_date = $_GET['date'] ?? '';
$filter_section_id = $_GET['section_id'] ?? '';

// تجهيز الاستعلام
$query = "
    SELECT reports.*, supervisors.name AS supervisor_name, sections.name AS section_name, levels.name AS level_name
    FROM reports
    LEFT JOIN supervisors ON reports.supervisor_id = supervisors.id
    LEFT JOIN sections ON reports.section_id = sections.id
    LEFT JOIN levels ON sections.level_id = levels.id
    WHERE 1
";
$params = [];

if ($filter_date != '') {
    $query .= " AND reports.report_date = ?";
    $params[] = $filter_date;
}

if ($filter_section_id != '') {
    $query .= " AND reports.section_id = ?";
    $params[] = $filter_section_id;
}

$query .= " ORDER BY reports.report_date DESC, reports.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// إعداد mPDF
$mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
$mpdf->SetDirectionality('rtl');

$html = '
<h2 style="text-align:center;">📄 تقرير التقارير المرسلة</h2>
<table border="1" cellpadding="5" style="border-collapse:collapse;width:100%;text-align:center;">
<tr>
    <th>📅 التاريخ</th>
    <th>📄 النوع</th>
    <th>🗂️ القسم</th>
    <th>👤 المشرف</th>
    <th>📜 المحتوى</th>
    <th>🕒 تاريخ الإرسال</th>
</tr>
';

foreach ($reports as $report) {
    $html .= "<tr>
        <td>{$report['report_date']}</td>
        <td>{$report['report_type']}</td>
        <td>{$report['level_name']} - {$report['section_name']}</td>
        <td>{$report['supervisor_name']}</td>
        <td><pre style='white-space: pre-wrap;text-align:right;'>{$report['report_content']}</pre></td>
        <td>{$report['sent_at']}</td>
    </tr>";
}

$html .= '</table><br><p style="text-align:left;">الختم والتوقيع</p>';

$mpdf->WriteHTML($html);
$mpdf->Output('Exported_Reports.pdf', 'D');
exit;
?>
