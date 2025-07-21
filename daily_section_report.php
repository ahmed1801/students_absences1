<?php
session_start();
require 'db.php';

// تحقق الصلاحيات
if (!isset($_SESSION['supervisor_id'])) {
    header('Location: index.php');
    exit;
}

$supervisor_id = $_SESSION['supervisor_id'];

// جلب الأقسام الخاصة بالمشرف
$sections_stmt = $pdo->prepare("
    SELECT sections.id, sections.name, levels.name AS level_name
    FROM supervisor_sections
    JOIN sections ON supervisor_sections.section_id = sections.id
    JOIN levels ON sections.level_id = levels.id
    WHERE supervisor_sections.supervisor_id = ?
");
$sections_stmt->execute([$supervisor_id]);
$sections = $sections_stmt->fetchAll();

// التاريخ والقسم
$date = $_GET['date'] ?? date('Y-m-d');
$section_id = $_GET['section_id'] ?? 'all';

// عند إرسال التقرير
if (isset($_GET['send_report']) && ($section_id == 'all' || $section_id != '')) {
    if ($section_id == 'all') {
        $section_ids = array_column($sections, 'id');
        $placeholders = implode(',', array_fill(0, count($section_ids), '?'));
        $query = "
            SELECT student_id, SUM(absence_hours) AS total_hours
            FROM absences
            WHERE absence_date = ? AND section_id IN ($placeholders)
            GROUP BY student_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array_merge([$date], $section_ids));
    } else {
        $stmt = $pdo->prepare("
            SELECT student_id, SUM(absence_hours) AS total_hours
            FROM absences
            WHERE absence_date = ? AND section_id = ?
            GROUP BY student_id
        ");
        $stmt->execute([$date, $section_id]);
    }

    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($report_data) {
        $json_data = json_encode($report_data, JSON_UNESCAPED_UNICODE);
        $insert = $pdo->prepare("INSERT INTO reports (supervisor_id, report_date, report_data, section_id) VALUES (?, ?, ?, ?)");
        $insert->execute([
            $supervisor_id,
            $date,
            $json_data,
            ($section_id == 'all' ? null : $section_id)
        ]);
        $_SESSION['msg'] = "<p style='color:green;text-align:center;'>✅ تم إرسال التقرير بنجاح إلى المدير.</p>";
    } else {
        $_SESSION['msg'] = "<p style='color:red;text-align:center;'>⚠️ لا توجد غيابات لإرسال التقرير.</p>";
    }

    header("refresh:3;url=daily_section_report.php?date=" . urlencode($date) . "&section_id=" . urlencode($section_id));
    exit;
}

// عرض الغيابات
$absences = [];
if ($section_id !== '') {
    if ($section_id == 'all') {
        $section_ids = array_column($sections, 'id');
        $in_placeholders = implode(',', array_fill(0, count($section_ids), '?'));
        $query = "
            SELECT students.name AS student_name, sections.name AS section_name, levels.name AS level_name,
                   SUM(absences.absence_hours) AS total_hours
            FROM absences
            JOIN students ON absences.student_id = students.id
            JOIN sections ON students.section_id = sections.id
            JOIN levels ON sections.level_id = levels.id
            WHERE absences.absence_date = ? AND absences.section_id IN ($in_placeholders)
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
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>📋 تقرير الغياب اليومي حسب القسم</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: 'Cairo', sans-serif; direction: rtl; background: #f0f2f5; }
        .container { max-width: 950px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #007BFF; color: white; }
        .btn { background: #28a745; color: white; padding: 7px 15px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h2>📋 تقرير الغياب اليومي - <?= htmlspecialchars($date) ?></h2>

    <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

    <form method="get">
        <label>📅 اختر التاريخ:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">

        <label>🏘️ اختر القسم:</label>
        <select name="section_id" onchange="this.form.submit()" required>
            <option value="all" <?= ($section_id == 'all') ? 'selected' : '' ?>>📌 عرض كل الأقسام</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= $sec['id'] ?>" <?= ($section_id == $sec['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sec['level_name'] . " - " . $sec['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($absences): ?>
    <table>
        <thead>
            <tr>
                <th>👤 اسم الطالب</th>
                <th>🏫 المستوى</th>
                <th>📚 القسم</th>
                <th>⏰ مجموع الساعات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($absences as $absence): ?>
            <tr>
                <td><?= htmlspecialchars($absence['student_name']) ?></td>
                <td><?= htmlspecialchars($absence['level_name']) ?></td>
                <td><?= htmlspecialchars($absence['section_name']) ?></td>
                <td><?= htmlspecialchars($absence['total_hours']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <div style="text-align:center;">
        <a href="export_daily_report_pdf.php?date=<?= urlencode($date) ?>&section_id=<?= urlencode($section_id) ?>" class="btn">🖨️ تصدير PDF</a>
        <a href="daily_section_report.php?send_report=1&date=<?= urlencode($date) ?>&section_id=<?= urlencode($section_id) ?>" class="btn" style="background:#007BFF;">📤 إرسال التقرير إلى المدير</a>
    </div>
    <?php elseif ($section_id !== ''): ?>
        <p style="color:red; text-align:center;">⚠️ لا توجد غيابات مسجلة.</p>
    <?php endif; ?>

    <div style="text-align:center; margin-top:15px;">
        <a href="supervisor_dashboard.php" class="btn" style="background:#007BFF;">🏠 العودة للوحة التحكم</a>
    </div>
</div>
</body>
</html>
