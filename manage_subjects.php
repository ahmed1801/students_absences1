<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب جميع المواد
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY id DESC");
$subjects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>إدارة المواد</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>📚 إدارة المواد</h1>
    <?php if (isset($_SESSION['msg'])) { echo "<p>".$_SESSION['msg']."</p>"; unset($_SESSION['msg']); } ?>
    <a href="add_subject.php"><button>➕ إضافة مادة</button></a>
    <table>
        <tr>
            <th>اسم المادة</th>
            <th>الإجراءات</th>
        </tr>
        <?php foreach ($subjects as $subject): ?>
        <tr>
            <td><?= htmlspecialchars($subject['name']) ?></td>
            <td>
                <a href="edit_subject.php?id=<?= $subject['id'] ?>">✏️</a>
                <a href="delete_subject.php?id=<?= $subject['id'] ?>" onclick="return confirm('هل أنت متأكد من حذف المادة؟');">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="admin_dashboard.php"><button>🏠 رجوع</button></a>
</div>
</body>
</html>
