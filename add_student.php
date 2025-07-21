<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب الأقسام والمستويات
$stmt = $pdo->query("
    SELECT sections.id, sections.name AS section_name, levels.name AS level_name
    FROM sections
    JOIN levels ON sections.level_id = levels.id
    ORDER BY levels.id, sections.name
");
$sections = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $section_id = intval($_POST['section_id']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if ($name !== "" && $section_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO students (name, section_id, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $section_id, $phone ?: null, $email ?: null]);

        // تسجيل في logs
        $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, admin_id, created_at) VALUES (?, ?, ?, NOW())");
        $log_stmt->execute(['إضافة طالب', "تم إضافة الطالب: $name", $_SESSION['admin_id']]);

        $_SESSION['msg'] = "✅ تم إضافة الطالب بنجاح.";
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
<title>إضافة طالب</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>➕ إضافة طالب جديد</h1>

    <?php if (isset($msg)): ?>
        <p><?= $msg; ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" placeholder="📛 اسم الطالب" required>
        <select name="section_id" required>
            <option value="">🗂️ اختر القسم</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?= $section['id'] ?>">
                    <?= htmlspecialchars($section['level_name'] . " - " . $section['section_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="phone" placeholder="📞 رقم الهاتف (اختياري)">
        <input type="email" name="email" placeholder="📧 البريد الإلكتروني (اختياري)">
        <button type="submit">💾 حفظ الطالب</button>
    </form>

    <br>
    <a href="manage_students.php"><button>↩️ الرجوع لقائمة الطلاب</button></a>
</div>
</body>
</html>
