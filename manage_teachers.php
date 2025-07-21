<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

// جلب المواد
$subjectsStmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
$subjects = $subjectsStmt->fetchAll();

// إضافة أستاذ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
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
        $msg = "❌ يرجى ملء جميع الحقول واختيار المواد.";
    }
}

// جلب الأساتذة
$teachersStmt = $pdo->query("SELECT * FROM teachers ORDER BY id DESC");
$teachers = $teachersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="styles.css">
<title>📘 إدارة الأساتذة</title>
</head>
<body>
<div class="container">
    <h1>📘 إدارة الأساتذة</h1>

    <?php if (isset($_SESSION['msg'])): ?>
        <p><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
    <?php elseif (isset($msg)): ?>
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
        <button type="submit" name="add_teacher">➕ إضافة أستاذ</button>
    </form>

    <h2>📋 قائمة الأساتذة</h2>
    <?php if (count($teachers) > 0): ?>
        <table>
            <tr>
                <th>👤 الاسم</th>
                <th>📚 المواد</th>
                <th>⚙️ الإجراءات</th>
            </tr>
            <?php foreach ($teachers as $teacher): ?>
                <?php
                $subjectsStmt = $pdo->prepare("
                    SELECT subjects.name 
                    FROM teacher_subjects 
                    JOIN subjects ON teacher_subjects.subject_id = subjects.id 
                    WHERE teacher_subjects.teacher_id = ?
                    ORDER BY subjects.name
                ");
                $subjectsStmt->execute([$teacher['id']]);
                $teacher_subjects = $subjectsStmt->fetchAll();
                ?>
                <tr>
                    <td><?= htmlspecialchars($teacher['name']) ?></td>
                    <td>
                        <?php
                        if ($teacher_subjects) {
                            foreach ($teacher_subjects as $sub) {
                                echo htmlspecialchars($sub['name']) . "<br>";
                            }
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="edit_teacher.php?id=<?= $teacher['id'] ?>" title="تعديل">✏️</a>
                        <a href="delete_teacher.php?id=<?= $teacher['id'] ?>" onclick="return confirm('هل أنت متأكد من حذف الأستاذ؟');" title="حذف">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>لا يوجد أساتذة حالياً.</p>
    <?php endif; ?>

    <a href="admin_dashboard.php"><button>🏠 رجوع</button></a>
</div>
</body>
</html>
