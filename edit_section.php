<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب بيانات القسم المحدد
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_sections.php');
    exit;
}

$section_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
$stmt->execute([$section_id]);
$section = $stmt->fetch();

if (!$section) {
    $_SESSION['msg'] = "❌ القسم غير موجود.";
    header('Location: manage_sections.php');
    exit;
}

// جلب المستويات لاختيار المستوى الجديد عند التعديل
$levels = $pdo->query("SELECT * FROM levels ORDER BY id ASC")->fetchAll();

// تحديث القسم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $level_id = $_POST['level_id'];

    if ($name !== "" && $level_id !== "") {
        $updateStmt = $pdo->prepare("UPDATE sections SET name = ?, level_id = ? WHERE id = ?");
        $updateStmt->execute([$name, $level_id, $section_id]);

        // تسجيل في السجلات
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], 'تعديل', 'sections', $section_id]);

        $_SESSION['msg'] = "✅ تم تحديث القسم بنجاح.";
        header("Location: manage_sections.php");
        exit;
    } else {
        $msg = "❌ يرجى ملء جميع الحقول.";
    }
}

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل القسم</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>✏️ تعديل القسم</h1>

    <?php if (isset($msg)) : ?>
        <p><?= $msg; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="اسم القسم" value="<?= htmlspecialchars($section['name']) ?>" required>
        <select name="level_id" required>
            <option value="">اختر المستوى</option>
            <?php foreach ($levels as $level): ?>
                <option value="<?= $level['id'] ?>" <?= ($level['id'] == $section['level_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($level['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">💾 حفظ التغييرات</button>
    </form>

    <a href="manage_sections.php"><button>↩️ رجوع</button></a>
</div>
</body>
</html>
