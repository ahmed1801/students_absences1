<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب بيانات الأستاذ
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: manage_teachers.php");
    exit;
}

$teacherStmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$teacherStmt->execute([$id]);
$teacher = $teacherStmt->fetch();

if (!$teacher) {
    header("Location: manage_teachers.php");
    exit;
}

// جلب المواد
$subjectsStmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
$subjects = $subjectsStmt->fetchAll();

// جلب المواد المرتبطة بالأستاذ
$linkedSubjectsStmt = $pdo->prepare("SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?");
$linkedSubjectsStmt->execute([$id]);
$linkedSubjects = $linkedSubjectsStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $subject_ids = $_POST['subject_ids'] ?? [];

    if ($name != "" && !empty($subject_ids)) {
        // تعديل اسم الأستاذ
        $updateStmt = $pdo->prepare("UPDATE teachers SET name = ? WHERE id = ?");
        $updateStmt->execute([$name, $id]);

        // تحديث المواد المرتبطة
        $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?")->execute([$id]);
        foreach ($subject_ids as $subject_id) {
            $linkStmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
            $linkStmt->execute([$id, $subject_id]);
        }

        // تسجيل في السجل
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], "تعديل", "teachers", $id]);

        $_SESSION['msg'] = "✅ تم تعديل بيانات الأستاذ بنجاح.";
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
<title>✏️ تعديل أستاذ</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>✏️ تعديل أستاذ</h1>

    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

    <form method="post">
        <input type="text" name="name" value="<?= htmlspecialchars($teacher['name']) ?>" required>

        <label>📚 اختر المواد التي يدرسها:</label>
        <div class="multiselect-container">
            <?php foreach ($subjects as $subject): ?>
                <label>
                    <input type="checkbox" name="subject_ids[]" value="<?= $subject['id'] ?>" 
                        <?= in_array($subject['id'], $linkedSubjects) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($subject['name']) ?>
                </label><br>
            <?php endforeach; ?>
        </div>

        <button type="submit">💾 حفظ التغييرات</button>
        <a href="manage_teachers.php"><button type="button">↩️ رجوع</button></a>
    </form>
</div>
</body>
</html>
