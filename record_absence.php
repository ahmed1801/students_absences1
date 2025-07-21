<?php
session_start();
require 'db.php';

// التحقق من صلاحية المشرف
if (!isset($_SESSION['supervisor_id'])) {
    header('Location: index.php');
    exit;
}

$supervisor_id = $_SESSION['supervisor_id'];

// جلب الأقسام المرتبطة بالمشرف
$stmt = $pdo->prepare("
    SELECT sections.id, sections.name, levels.name AS level_name
    FROM supervisor_sections
    JOIN sections ON supervisor_sections.section_id = sections.id
    JOIN levels ON sections.level_id = levels.id
    WHERE supervisor_sections.supervisor_id = ?
");
$stmt->execute([$supervisor_id]);
$sections = $stmt->fetchAll();

// تسجيل الغياب
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $absence_hours = intval($_POST['absence_hours'] ?? 0);
    $absence_date = date('Y-m-d');

    if (!$student_id || $absence_hours <= 0) {
        $msg = "<p style='color:red;'>⚠️ يجب تحديد الطالب وساعات الغياب أكبر من 0.</p>";
    } else {
        // الحصول على section_id تلقائيًا من الطالب
        $sectionStmt = $pdo->prepare("SELECT section_id FROM students WHERE id = ?");
        $sectionStmt->execute([$student_id]);
        $student_section = $sectionStmt->fetchColumn();

        if (!$student_section) {
            $msg = "<p style='color:red;'>⚠️ تعذر العثور على القسم الخاص بالطالب.</p>";
        } else {
            // تسجيل الغياب
            $stmt = $pdo->prepare("INSERT INTO absences (student_id, section_id, absence_hours, absence_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $student_section, $absence_hours, $absence_date]);

            $msg = "<p style='color:green;'>✅ تم تسجيل الغياب بنجاح.</p>";
        }
    }
}

// عند اختيار القسم يتم عرض الطلاب مباشرة
$students = [];
if (isset($_GET['section_id'])) {
    $selected_section_id = intval($_GET['section_id']);
    $stmt = $pdo->prepare("SELECT id, name FROM students WHERE section_id = ?");
    $stmt->execute([$selected_section_id]);
    $students = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>📌 تسجيل غياب طالب</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<style>
body { font-family: 'Cairo', sans-serif; direction: rtl; background: #f0f2f5; }
.container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
label { display: block; margin-top: 10px; }
button { background: #007BFF; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background: #0056b3; }
select, input[type=number] { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; }
</style>
</head>
<body>
<div class="container">
    <h2>📌 تسجيل غياب طالب</h2>

    <?= $msg ?>

    <form method="get" style="margin-bottom:15px;">
        <label>📚 اختر القسم:</label>
        <select name="section_id" onchange="this.form.submit()" required>
            <option value="">-- اختر القسم --</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?= $section['id'] ?>" <?= (isset($selected_section_id) && $selected_section_id == $section['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($section['level_name'] . " - " . $section['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($students): ?>
    <form method="post">
        <label>👤 اختر الطالب:</label>
        <select name="student_id" required>
            <option value="">-- اختر الطالب --</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>⏰ عدد ساعات الغياب:</label>
        <input type="number" name="absence_hours" min="1" max="7" required>

        <button type="submit">💾 تسجيل الغياب</button>
    </form>
    <?php elseif (isset($selected_section_id)): ?>
        <p style="color:red;">⚠️ لا يوجد طلاب في هذا القسم.</p>
    <?php endif; ?>

    <div style="margin-top:15px; text-align:center;">
        <a href="supervisor_dashboard.php" style="text-decoration:none;"><button style="background:#28a745;">🏠 العودة للوحة التحكم</button></a>
    </div>
</div>
</body>
</html>
