<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// إضافة مستوى
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_level'])) {
    $name = trim($_POST['name']);

    if ($name != "") {
        $stmt = $pdo->prepare("INSERT INTO levels (name) VALUES (?)");
        $stmt->execute([$name]);

        // تسجيل في السجلات
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, 'إضافة', 'levels', ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], $pdo->lastInsertId()]);

        $_SESSION['msg'] = "✅ تم إضافة المستوى بنجاح.";
        header("Location: manage_levels.php");
        exit;
    } else {
        $msg = "❌ يرجى إدخال اسم المستوى.";
    }
}

// حذف كل المستويات
if (isset($_POST['delete_all'])) {
    $pdo->query("DELETE FROM levels");

    $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, created_at) VALUES (?, 'حذف الكل', 'levels', NOW())");
    $logStmt->execute([$_SESSION['admin_id']]);

    $_SESSION['msg'] = "✅ تم حذف جميع المستويات بنجاح.";
    header("Location: manage_levels.php");
    exit;
}

// جلب المستويات
$stmt = $pdo->query("SELECT * FROM levels ORDER BY id DESC");
$levels = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستويات</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>📚 إدارة المستويات</h1>

    <?php if (isset($_SESSION['msg'])): ?>
        <p><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
    <?php elseif (isset($msg)): ?>
        <p><?= $msg; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="اسم المستوى" required>
        <button type="submit" name="add_level">➕ إضافة مستوى</button>
        <br>
    </form>
    
    <?php if (count($levels) > 0): ?>
        <form method="POST" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف جميع المستويات؟');">
            <button type="submit" name="delete_all">🗑️ حذف جميع المستويات</button>
        </form>
        <table>
            <tr>
                <th>اسم المستوى</th>
                <th>الإجراءات</th>
            </tr>
            <?php foreach ($levels as $level): ?>
                <tr>
                    <td><?= htmlspecialchars($level['name']) ?></td>
                    <td>
                        <a href="edit_level.php?id=<?= $level['id'] ?>" title="تعديل">✏️</a>
                        <a href="delete_level.php?id=<?= $level['id'] ?>" onclick="return confirm('⚠️ هل أنت متأكد من حذف المستوى؟');" title="حذف">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>لا توجد مستويات مضافة حالياً.</p>
    <?php endif; ?>

    <a href="admin_dashboard.php"><button>🏠 رجوع</button></a>
</div>
</body>
</html>
