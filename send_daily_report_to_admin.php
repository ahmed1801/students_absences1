<?php
session_start();
require 'db.php';

if (!isset($_SESSION['supervisor_id'])) {
    echo "⚠️ هذه الصفحة للمشرفين فقط.";
    exit;
}

$supervisor_id = $_SESSION['supervisor_id'];
$section_id = $_GET['section_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');



$stmt = $pdo->prepare("
    SELECT absences.*, students.name AS student_name
    FROM absences
    JOIN students ON absences.student_id = students.id
    WHERE absences.absence_date = ? AND absences.section_id = ?
");
$stmt->execute([$date, $section_id]);
$absences = $stmt->fetchAll();



// تجهيز المحتوى:
$content = "";
foreach ($absences as $row) {
    $content .= "الطالب: {$row['student_name']} - عدد الساعات: {$row['absence_hours']}\n";
}

$stmt = $pdo->prepare("INSERT INTO reports (supervisor_id, content, report_date) VALUES (?, ?, ?)");
$stmt->execute([$supervisor_id, $content, $date]);

echo "✅ تم إرسال التقرير إلى المدير بنجاح.";


