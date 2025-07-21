<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// التحقق من معرف المادة
if (!isset($_GET['id'])) {
    header('Location: manage_subjects.php');
    exit;
}
$id = (int)$_GET['id'];

// جلب بيانات المادة
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$id]);
$subject = $stmt->fetch();

if (!$subject) {
    $_SESSION['msg'] = "❌ المادة غير موجودة.";
    header("Location: manage_subjects.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if ($name != "") {
        $updateStmt = $pdo->prepare("UPDATE subjects SET name = ? WHERE id = ?");
        $updateStmt->execute([$name, $id]);

        // تسجيل في السجلات
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, 'تعديل', 'subjects', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $id]);

        $_SESSION['msg'] = "✅ تم تعديل المادة بنجاح.";
        header("Location: manage_subjects.php");
        exit;
    } else {
        $msg = "❌ يرجى إدخال اسم المادة.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>✏️ تعديل مادة</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>✏️ تعديل مادة</h1>
    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>
    <form method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($subject['name']) ?>" required>
        <button type="submit">💾 حفظ التعديلات</button>
    </form>
    <a href="manage_subjects.php"><button>🔙 رجوع</button></a>
</div>
</body>
</html>
