<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// الحصول على معرف الطالب
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
    echo "❌ الطالب غير موجود.";
    exit;
}

// جلب الغيابات الخاصة بالطالب
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
    // لحساب المدة نحسب فرق الوقت بين البداية والنهاية
    $times = explode('-', str_replace(' ', '', $absence['hours_time']));
    if (count($times) == 2) {
        $start = strtotime($times[0]);
        $end = strtotime($times[1]);
        if ($start !== false && $end !== false && $end > $start) {
            $total_hours += ($end - $start) / 3600; // تحويل الثواني إلى ساعات
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<title>تفاصيل الطالب</title>
</head>
<body>
<div class="container">
    <h1>👁️ تفاصيل الطالب</h1>

    <p><strong>👤 الاسم:</strong> <?= htmlspecialchars($student['name']) ?></p>
    <p><strong>🏫 المستوى:</strong> <?= htmlspecialchars($student['level_name']) ?></p>
    <p><strong>📚 القسم:</strong> <?= htmlspecialchars($student['section_name']) ?></p>
    <p><strong>📱 الهاتف:</strong> <?= htmlspecialchars($student['phone'] ?? '-') ?></p>
    <p><strong>✉️ البريد:</strong> <?= htmlspecialchars($student['email'] ?? '-') ?></p>

    <h2>📊 تفاصيل الغيابات</h2>
    <?php if (count($absences) > 0): ?>
    <table>
        <tr>
            <th>📅 التاريخ</th>
            <th>⏱️ الساعات (المواعيد)</th>
        </tr>
        <?php foreach ($absences as $absence): ?>
        <tr>
            <td><?= htmlspecialchars($absence['absence_date']) ?></td>
            <td><?= htmlspecialchars($absence['hours_time']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><strong>🔢 مجموع الساعات المقدرة:</strong> <?= number_format($total_hours, 2) ?> ساعة</p>
    <?php else: ?>
    <p>✅ لا يوجد غيابات لهذا الطالب.</p>
    <?php endif; ?>

    <button onclick="window.print()">🖨️ طباعة</button>
    <a href="export_student_pdf.php?id=<?= $student_id ?>"><button>📄 تصدير PDF</button></a>
    <a href="manage_students.php"><button>🔙 رجوع</button></a>
</div>
</body>
</html>
