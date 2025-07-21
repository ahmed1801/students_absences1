<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب المستويات لربط القسم بالمستوى
$levels = $pdo->query("SELECT * FROM levels ORDER BY id ASC")->fetchAll();

// إضافة قسم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    $name = trim($_POST['name']);
    $level_id = $_POST['level_id'];

    if ($name !== "" && $level_id !== "") {
        $stmt = $pdo->prepare("INSERT INTO sections (name, level_id) VALUES (?, ?)");
        $stmt->execute([$name, $level_id]);

        // تسجيل في السجلات
        $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $logStmt->execute([$_SESSION['admin_id'], 'إضافة', 'sections', $pdo->lastInsertId()]);

        $_SESSION['msg'] = "✅ تم إضافة القسم بنجاح.";
        header("Location: manage_sections.php");
        exit;
    } else {
        $msg = "❌ يرجى ملء جميع الحقول.";
    }
}

// جلب الأقسام مع المستويات
$stmt = $pdo->query("
    SELECT sections.*, levels.name AS level_name 
    FROM sections 
    JOIN levels ON sections.level_id = levels.id 
    ORDER BY levels.id ASC, sections.name ASC
");
$sections = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<title>إدارة الأقسام</title>
</head>
<body>
<div class="container">
    <h1>📚 إدارة الأقسام</h1>

    <?php if (isset($_SESSION['msg'])): ?>
        <p><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
    <?php elseif (isset($msg)): ?>
        <p><?= $msg; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="اسم القسم" required>
        <select name="level_id" required>
            <option value="">اختر المستوى</option>
            <?php foreach ($levels as $level): ?>
                <option value="<?= $level['id'] ?>"><?= htmlspecialchars($level['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_section">➕ إضافة قسم</button>
    </form>

    <h2>📋 قائمة الأقسام</h2>
    <?php if (count($sections) > 0): ?>
        <table>
            <tr>
                <th>اسم القسم</th>
                <th>المستوى</th>
                <th>الإجراءات</th>
            </tr>
            <?php foreach ($sections as $section): ?>
                <tr>
                    <td><?= htmlspecialchars($section['name']) ?></td>
                    <td><?= htmlspecialchars($section['level_name']) ?></td>
                    <td>
                        <a href="edit_section.php?id=<?= $section['id'] ?>">✏️</a>
                        <a href="delete_section.php?id=<?= $section['id'] ?>" onclick="return confirm('هل أنت متأكد من حذف القسم؟');">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>لا يوجد أقسام مضافة حالياً.</p>
    <?php endif; ?>

    <a href="admin_dashboard.php"><button>🏠 رجوع</button></a>
</div>
</body>
</html>
