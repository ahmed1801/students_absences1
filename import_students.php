<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$msg = "";

// عند رفع الملف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // تخطي الصف الأول (العناوين)

            $name = trim($row[0]); // اسم الطالب
            $level_name = trim($row[1]); // المستوى
            $section_name = trim($row[2]); // القسم

            if ($name == "" || $level_name == "" || $section_name == "") {
                $skipped++;
                continue;
            }

            // الحصول على level_id
            $levelStmt = $pdo->prepare("SELECT id FROM levels WHERE name = ?");
            $levelStmt->execute([$level_name]);
            $level = $levelStmt->fetch();
            if (!$level) {
                $skipped++;
                continue;
            }

            $level_id = $level['id'];

            // الحصول على section_id
            $sectionStmt = $pdo->prepare("SELECT id FROM sections WHERE name = ? AND level_id = ?");
            $sectionStmt->execute([$section_name, $level_id]);
            $section = $sectionStmt->fetch();
            if (!$section) {
                $skipped++;
                continue;
            }

            $section_id = $section['id'];

            // التحقق من التكرار
            $checkStmt = $pdo->prepare("SELECT id FROM students WHERE name = ? AND section_id = ?");
            $checkStmt->execute([$name, $section_id]);
            if ($checkStmt->fetch()) {
                $skipped++;
                continue;
            }

            // إضافة الطالب بدون هاتف وبريد
            $insertStmt = $pdo->prepare("INSERT INTO students (name, section_id) VALUES (?, ?)");
            $insertStmt->execute([$name, $section_id]);
            $student_id = $pdo->lastInsertId();

            // تسجيل في logs
            $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$_SESSION['admin_id'], "استيراد طالب: {$name}", 'students', $student_id]);

            $imported++;
        }

        $msg = "✅ تم استيراد {$imported} طالب، تم تخطي {$skipped} بسبب نقص البيانات أو التكرار.";
    } catch (Exception $e) {
        $msg = "❌ خطأ أثناء قراءة الملف: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>📥 استيراد الطلاب</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>📥 استيراد الطلاب</h1>

    <?php if ($msg): ?>
        <p><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>📄 اختر ملف CSV أو Excel يحتوي على الأعمدة التالية فقط:
            <br>اسم الطالب | المستوى | القسم</label><br><br>
        <input type="file" name="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
        <br><br>
        <button type="submit">⬆️ استيراد</button>
    </form>
    <br>
    <a href="manage_students.php"><button>⬅️ رجوع إلى إدارة الطلاب</button></a>
</div>
</body>
</html>
