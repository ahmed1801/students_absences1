<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب بيانات الطالب
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("❌ الطالب غير موجود.");
}

// جلب الأقسام
$sections_stmt = $pdo->query("
    SELECT sections.id, sections.name AS section_name, levels.name AS level_name
    FROM sections
    JOIN levels ON sections.level_id = levels.id
    ORDER BY levels.id, sections.name
");
$sections = $sections_stmt->fetchAll();

// عند إرسال التعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $section_id = intval($_POST['section_id']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if ($name !== "" && $section_id > 0) {
        $stmt = $pdo->prepare("UPDATE students SET name = ?, section_id = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $section_id, $phone ?: null, $email ?: null, $student_id]);

        // تسجيل في logs
        $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, admin_id, created_at) VALUES (?, ?, ?, NOW())");
        $log_stmt->execute(['تعديل طالب', "تعديل الطالب: $name (ID: $student_id)", $_SESSION['admin_id']]);

        $_SESSION['msg'] = "✅ تم تحديث بيانات الطالب بنجاح.";
        header("Location: manage_students.php");
        exit;
    } else {
        $msg = "❌ يرجى ملء الاسم واختيار القسم.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>✏️ تعديل بيانات الطالب</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>✏️ تعديل بيانات الطالب</h1>

    <?php if (isset($msg)): ?>
        <p><?= $msg; ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" placeholder="📛 اسم الطالب" required>

        <select name="section_id" required>
            <option value="">🗂️ اختر القسم</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?= $section['id'] ?>" <?= $section['id'] == $student['section_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($section['level_name'] . " - " . $section['section_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" placeholder="📞 رقم الهاتف (اختياري)">
        <input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" placeholder="📧 البريد الإلكتروني (اختياري)">

        <button type="submit">💾 تحديث الطالب</button>
    </form>

    <br>
    <a href="manage_students.php"><button>↩️ الرجوع لقائمة الطلاب</button></a>
</div>
</body>
</html>
