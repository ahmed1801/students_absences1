<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if ($name != "") {
        $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
        $stmt->execute([$name]);

        // تسجيل في السجلات
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, 'إضافة', 'subjects', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $pdo->lastInsertId()]);

        $_SESSION['msg'] = "✅ تم إضافة المادة بنجاح.";
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
<title>إضافة مادة</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>➕ إضافة مادة</h1>
    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="اسم المادة" required>
        <button type="submit">💾 حفظ</button>
    </form>
    <a href="manage_subjects.php"><button>🔙 رجوع</button></a>
</div>
</body>
</html>
