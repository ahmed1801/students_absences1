<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// حذف طالب محدد
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$delete_id]);

    // تسجيل في logs
    $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, admin_id, created_at) VALUES (?, ?, ?, NOW())");
    $log_stmt->execute(['حذف طالب', "تم حذف الطالب برقم ID: $delete_id", $_SESSION['admin_id']]);

    $_SESSION['msg'] = "✅ تم حذف الطالب بنجاح.";
    header("Location: manage_students.php");
    exit;
}

// حذف جميع الطلاب
if (isset($_GET['delete_all'])) {
    $pdo->exec("DELETE FROM students");

    // تسجيل في logs
    $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, admin_id, created_at) VALUES (?, ?, ?, NOW())");
    $log_stmt->execute(['حذف جميع الطلاب', "تم حذف جميع الطلاب من النظام", $_SESSION['admin_id']]);

    $_SESSION['msg'] = "✅ تم حذف جميع الطلاب بنجاح.";
    header("Location: manage_students.php");
    exit;
}

// جلب الطلاب مع المستوى والقسم
$stmt = $pdo->prepare("
    SELECT students.*, sections.name AS section_name, levels.name AS level_name
    FROM students
    JOIN sections ON students.section_id = sections.id
    JOIN levels ON sections.level_id = levels.id
    ORDER BY levels.id ASC, sections.name ASC, students.name ASC
");
$stmt->execute();
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>إدارة الطلاب</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>📚 إدارة الطلاب</h1>

    <?php if (isset($_SESSION['msg'])): ?>
        <p><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
    <?php endif; ?>
    <a href="import_students.php"> <button>👨‍🎓 استراد الطلاب</button></a>
    <a href="add_student.php"><button>➕ إضافة طالب</button></a>
    <a href="manage_students.php?delete_all=1" onclick="return confirm('⚠️ هل أنت متأكد من حذف جميع الطلاب؟ هذا الإجراء لا يمكن التراجع عنه.')">
        <button style="background-color:#c0392b; color:#fff;">🗑️ حذف جميع الطلاب</button>
    </a>

    <table>
        <thead>
            <tr>
                <th>📛 الاسم</th>
                <th>📘 المستوى</th>
                <th>🏫 القسم</th>
                <th>📞 الهاتف</th>
                <th>📧 البريد</th>
                <th>⚙️ الإجراءات</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($students): ?>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['name']) ?></td>
                    <td><?= htmlspecialchars($student['level_name']) ?></td>
                    <td><?= htmlspecialchars($student['section_name']) ?></td>
                    <td><?= htmlspecialchars($student['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($student['email'] ?? '-') ?></td>
                    <td>
                        <a href="edit_student.php?id=<?= $student['id'] ?>" title="تعديل">✏️</a>
                        <a href="manage_students.php?delete_id=<?= $student['id'] ?>" onclick="return confirm('هل أنت متأكد من حذف الطالب؟');" title="حذف">🗑️</a>
                        <a href="view_student_details.php?id=<?= $student['id'] ?>" title="تفاصيل">👁️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">❌ لا يوجد طلاب حاليًا.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <br>
    <a href="admin_dashboard.php"><button>🏠 العودة للوحة التحكم</button></a>
</div>
</body>
</html>
