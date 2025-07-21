<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

// التحقق من وجود المعرف
$supervisor_id = $_GET['id'] ?? null;
if (!$supervisor_id) { header('Location: manage_supervisors.php'); exit; }

// جلب بيانات المشرف
$stmt = $pdo->prepare("SELECT * FROM supervisors WHERE id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();
if (!$supervisor) { header('Location: manage_supervisors.php'); exit; }

// جلب الأقسام
$sectionsStmt = $pdo->query("SELECT sections.id, sections.name, levels.name AS level_name FROM sections JOIN levels ON sections.level_id = levels.id ORDER BY levels.id, sections.name");
$sections = $sectionsStmt->fetchAll();

// جلب الأقسام المرتبطة بالمشرف
$linkedStmt = $pdo->prepare("SELECT section_id FROM supervisor_section WHERE supervisor_id = ?");
$linkedStmt->execute([$supervisor_id]);
$linked_sections = $linkedStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $section_ids = $_POST['section_ids'] ?? [];

    if ($name !== "" && $username !== "" && !empty($section_ids)) {
        // تحقق من عدم تكرار اسم المستخدم لمستخدم آخر
        $checkStmt = $pdo->prepare("SELECT id FROM supervisors WHERE username = ? AND id != ?");
        $checkStmt->execute([$username, $supervisor_id]);
        if ($checkStmt->fetch()) {
            $msg = "❌ اسم المستخدم مستخدم بالفعل لمستخدم آخر.";
        } else {
            if ($password !== "") {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE supervisors SET name = ?, username = ?, password = ? WHERE id = ?");
                $updateStmt->execute([$name, $username, $hashedPassword, $supervisor_id]);
            } else {
                $updateStmt = $pdo->prepare("UPDATE supervisors SET name = ?, username = ? WHERE id = ?");
                $updateStmt->execute([$name, $username, $supervisor_id]);
            }

            // تحديث الأقسام المرتبطة
            $pdo->prepare("DELETE FROM supervisor_section WHERE supervisor_id = ?")->execute([$supervisor_id]);
            foreach ($section_ids as $section_id) {
                $pdo->prepare("INSERT INTO supervisor_section (supervisor_id, section_id) VALUES (?, ?)")->execute([$supervisor_id, $section_id]);
            }

            // تسجيل العملية
            $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $logStmt->execute([$_SESSION['admin_id'], "تعديل", "supervisors", $supervisor_id]);

            $_SESSION['msg'] = "✅ تم تحديث بيانات المشرف بنجاح.";
            header("Location: manage_supervisors.php");
            exit;
        }
    } else {
        $msg = "❌ يرجى ملء جميع الحقول واختيار الأقسام.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<title>تعديل المشرف</title>
</head>
<body>
<div class="container">
    <h1>✏️ تعديل بيانات المشرف</h1>

    <?php if (isset($msg)): ?>
        <p class="error"><?= $msg; ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" placeholder="اسم المشرف" value="<?= htmlspecialchars($supervisor['name']) ?>" required>
        <input type="text" name="username" placeholder="اسم المستخدم" value="<?= htmlspecialchars($supervisor['username']) ?>" required>
        <input type="password" name="password" placeholder="كلمة المرور (اتركه فارغًا للإبقاء عليها)">

        <label>🗂️ اختر الأقسام:</label><br>
        <div class="multiselect-container">
            <?php foreach ($sections as $section): ?>
                <label>
                    <input type="checkbox" name="section_ids[]" value="<?= $section['id'] ?>"
                        <?= in_array($section['id'], $linked_sections) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($section['level_name']) ?> - <?= htmlspecialchars($section['name']) ?>
                </label><br>
            <?php endforeach; ?>
        </div>

        <button type="submit">💾 حفظ التعديلات</button>
    </form>

    <a href="manage_supervisors.php"><button>🏠 رجوع</button></a>
</div>
</body>
</html>
