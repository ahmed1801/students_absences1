<?php
session_start();
require 'db.php';

// التحقق من تسجيل الدخول كمشرف
if (!isset($_SESSION['supervisor_id'])) {
    die("⚠️ الوصول مرفوض: هذه الصفحة للمشرفين فقط.");
}

$supervisor_id = $_SESSION['supervisor_id'];

// التحقق من وصول المعرف
if (!isset($_GET['id'])) {
    die("⚠️ لم يتم تحديد السجل المراد حذفه.");
}

$absence_id = $_GET['id'];

// التحقق من ملكية الغياب للمشرف
$stmt = $pdo->prepare("
    SELECT absences.*
    FROM absences
    JOIN students ON absences.student_id = students.id
    JOIN supervisor_sections ON students.section_id = supervisor_sections.section_id
    WHERE absences.id = ? AND supervisor_sections.supervisor_id = ?
");
$stmt->execute([$absence_id, $supervisor_id]);
$absence = $stmt->fetch();

if (!$absence) {
    die("❌ لا تملك صلاحية حذف هذا السجل.");
}

// حذف السجل
$stmt = $pdo->prepare("DELETE FROM absences WHERE id = ?");
$stmt->execute([$absence_id]);

// تسجيل العملية في سجل النظام
$log_stmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, timestamp, details) VALUES (?, ?, ?, ?, NOW(), ?)");
$log_stmt->execute([
    null, // المشرف ليس لديه admin_id
    'حذف',
    'absences',
    $absence_id,
    'تم حذف الغياب بواسطة مشرف ID: ' . $supervisor_id
]);

// إعادة التوجيه بعد الحذف
$_SESSION['msg'] = "✅ تم حذف سجل الغياب بنجاح.";
header("Location: daily_section_report.php");
exit;
?>
