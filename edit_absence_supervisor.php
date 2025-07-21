<?php
session_start();
require 'db.php';

// تحقق من تسجيل الدخول كمشرف
if (!isset($_SESSION['supervisor_id'])) {
    die("⚠️ الوصول مرفوض: هذه الصفحة مخصصة للمشرفين فقط.");
}
$supervisor_id = $_SESSION['supervisor_id'];

// التحقق من وجود المعرف
if (!isset($_GET['id'])) {
    die("⚠️ لم يتم تحديد سجل الغياب.");
}
$absence_id = $_GET['id'];

// التحقق من ملكية الغياب للمشرف
$stmt = $pdo->prepare("
    SELECT absences.*, students.name AS student_name
    FROM absences
    JOIN students ON absences.student_id = students.id
    JOIN supervisor_sections ON students.section_id = supervisor_sections.section_id
    WHERE absences.id = ? AND supervisor_sections.supervisor_id = ?
");
$stmt->execute([$absence_id, $supervisor_id]);
$absence = $stmt->fetch();

if (!$absence) {
    die("❌ لا تملك صلاحية تعديل هذا السجل.");
}

// تحديث السجل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hours_time = trim($_POST['hours_time']);
    $absence_date = trim($_POST['absence_date']);

    if ($hours_time != "" && $absence_date != "") {
        $update = $pdo->prepare("UPDATE absences SET hours_time = ?, absence_date = ? WHERE id = ?");
        $update->execute([$hours_time, $absence_date, $absence_id]);

        $_SESSION['msg'] = "✅ تم تعديل السجل بنجاح.";
        header("Location: daily_section_report.php");
        exit;
    } else {
        $msg = "❌ يرجى اختيار جميع الحقول.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>✏️ تعديل غياب الطالب</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>✏️ تعديل غياب الطالب</h1>

    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

    <form method="post">
        <label>👤 اسم الطالب:</label>
        <input type="text" value="<?= htmlspecialchars($absence['student_name']) ?>" disabled>

        <label>⏰ اختر ساعات الغياب:</label>
        <select name="hours_time" required>
            <option value="">اختر ساعات الغياب</option>
            <option value="08:00 - 09:00" <?= ($absence['hours_time'] == '08:00 - 09:00') ? 'selected' : '' ?>>08:00 - 09:00</option>
            <option value="09:00 - 10:00" <?= ($absence['hours_time'] == '09:00 - 10:00') ? 'selected' : '' ?>>09:00 - 10:00</option>
            <option value="10:00 - 11:00" <?= ($absence['hours_time'] == '10:00 - 11:00') ? 'selected' : '' ?>>10:00 - 11:00</option>
            <option value="11:00 - 12:00" <?= ($absence['hours_time'] == '11:00 - 12:00') ? 'selected' : '' ?>>11:00 - 12:00</option>
            <option value="13:00 - 14:00" <?= ($absence['hours_time'] == '13:00 - 14:00') ? 'selected' : '' ?>>13:00 - 14:00</option>
            <option value="14:00 - 15:00" <?= ($absence['hours_time'] == '14:00 - 15:00') ? 'selected' : '' ?>>14:00 - 15:00</option>
        </select>

        <label>📅 التاريخ:</label>
        <input type="date" name="absence_date" value="<?= htmlspecialchars($absence['absence_date']) ?>" required>

        <button type="submit">💾 حفظ التعديلات</button>
    </form>

    <a href="daily_section_report.php"><button>↩️ رجوع للتقرير</button></a>
</div>
</body>
</html>
