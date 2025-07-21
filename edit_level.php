<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// تحقق من وجود معرف المستوى
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_levels.php');
    exit;
}

$id = (int)$_GET['id'];

// جلب بيانات المستوى
$stmt = $pdo->prepare("SELECT * FROM levels WHERE id = ?");
$stmt->execute([$id]);
$level = $stmt->fetch();

if (!$level) {
    $_SESSION['msg'] = "❌ المستوى غير موجود.";
    header('Location: manage_levels.php');
    exit;
}

// التعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if ($name != "") {
        $updateStmt = $pdo->prepare("UPDATE levels SET name = ? WHERE id = ?");
        $updateStmt->execute([$name, $id]);

        // سجل العملية
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, 'تعديل', 'levels', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $id]);

        $_SESSION['msg'] = "✅ تم تعديل المستوى بنجاح.";
        header("Location: manage_levels.php");
        exit;
    } else {
        $msg = "❌ يرجى إدخال اسم صحيح للمستوى.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تعديل المستوى</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>✏️ تعديل المستوى</h1>
    <?php if (isset($msg)): ?><p><?= $msg ?></p><?php endif; ?>
    <form method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($level['name']) ?>" required>
        <button type="submit">💾 حفظ التعديلات</button>
    </form>
    <a href="manage_levels.php"><button>🔙 رجوع</button></a>
</div>
</body>
</html>
