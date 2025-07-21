<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب المواد
$subjectsStmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
$subjects = $subjectsStmt->fetchAll();

// إضافة أستاذ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $subject_ids = $_POST['subject_ids'] ?? [];

    if ($name != "" && !empty($subject_ids)) {
        $stmt = $pdo->prepare("INSERT INTO teachers (name) VALUES (?)");
        $stmt->execute([$name]);
        $teacher_id = $pdo->lastInsertId();

        foreach ($subject_ids as $subject_id) {
            $linkStmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
            $linkStmt->execute([$teacher_id, $subject_id]);
        }

        // تسجيل في السجل
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], "إضافة", "teachers", $teacher_id]);

        $_SESSION['msg'] = "✅ تم إضافة الأستاذ بنجاح.";
        header("Location: manage_teachers.php");
        exit;
    } else {
        $msg = "❌ يرجى إدخال اسم الأستاذ واختيار المواد.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>➕ إضافة أستاذ</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>➕ إضافة أستاذ</h1>

    <?php if (isset($msg)): ?>
        <p><?= $msg; ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" placeholder="👤 اسم الأستاذ" required>

        <label>📚 اختر المواد التي يدرسها:</label>
        <div class="multiselect-container">
            <?php foreach ($subjects as $subject): ?>
                <label>
                    <input type="checkbox" name="subject_ids[]" value="<?= $subject['id'] ?>">
                    <?= htmlspecialchars($subject['name']) ?>
                </label><br>
            <?php endforeach; ?>
        </div>

        <button type="submit">💾 حفظ</button>
        <a href="manage_teachers.php"><button type="button">↩️ رجوع</button></a>
    </form>
</div>
</body>
</html>
