<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

// جلب الأقسام لفلتر القائمة
$sectionsStmt = $pdo->query("SELECT sections.id, sections.name, levels.name AS level_name FROM sections JOIN levels ON sections.level_id = levels.id ORDER BY levels.id, sections.name");
$sections = $sectionsStmt->fetchAll();

// استعلام رئيسي
$absences = [];
$total_hours = 0;
$selected_section_id = $_GET['section_id'] ?? '';

if ($selected_section_id) {
    $stmt = $pdo->prepare("
        SELECT absences.*, students.name AS student_name, sections.name AS section_name, levels.name AS level_name 
        FROM absences
        JOIN students ON absences.student_id = students.id
        JOIN sections ON absences.section_id = sections.id
        JOIN levels ON sections.level_id = levels.id
        WHERE sections.id = ?
        ORDER BY absences.absence_date DESC
    ");
    $stmt->execute([$selected_section_id]);
    $absences = $stmt->fetchAll();

    foreach ($absences as $row) {
        $hours = explode(',', $row['absence_hours']);
        $total_hours += count($hours);
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="styles.css">
<title>📊 تقرير الغياب حسب القسم</title>
</head>
<body>
<div class="container">
    <h1>📊 تقرير الغياب حسب القسم</h1>

    <form method="get">
        <label>اختر القسم:</label>
        <select name="section_id" onchange="this.form.submit()">
            <option value="">-- اختر القسم --</option>
            <?php foreach($sections as $section): ?>
                <option value="<?= $section['id'] ?>" <?= ($section['id'] == $selected_section_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($section['level_name']) ?> - <?= htmlspecialchars($section['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_section_id): ?>
        <?php if (count($absences) > 0): ?>
            <table>
                <tr>
                    <th>📅 التاريخ</th>
                    <th>👤 اسم التلميذ</th>
                    <th>📚 المستوى</th>
                    <th>🏫 القسم</th>
                    <th>⏱️ ساعات الغياب</th>
                </tr>
                <?php foreach($absences as $absence): ?>
                <tr>
                    <td><?= htmlspecialchars($absence['absence_date']) ?></td>
                    <td><?= htmlspecialchars($absence['student_name']) ?></td>
                    <td><?= htmlspecialchars($absence['level_name']) ?></td>
                    <td><?= htmlspecialchars($absence['section_name']) ?></td>
                    <td><?= htmlspecialchars($absence['absence_hours']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <p>📌 <strong>إجمالي ساعات الغياب:</strong> <?= $total_hours ?> ساعة</p>

        <?php else: ?>
            <p>❌ لا توجد بيانات غياب لهذا القسم.</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="admin_dashboard.php"><button>🔙 رجوع</button></a>
</div>
</body>
</html>
