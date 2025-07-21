<?php
session_start();
require 'db.php';

// حماية الجلسة
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// جلب الأقسام
$sectionsStmt = $pdo->query("
    SELECT sections.id, sections.name AS section_name, levels.name AS level_name
    FROM sections
    JOIN levels ON sections.level_id = levels.id
    ORDER BY levels.id, sections.name
");
$sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

// إضافة مشرف جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supervisor'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $selected_sections = $_POST['sections'] ?? [];

    if ($name && $username && $password && !empty($selected_sections)) {
        // التحقق من عدم تكرار اسم المستخدم
        $checkStmt = $pdo->prepare("SELECT id FROM supervisors WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            $msg = "⚠️ اسم المستخدم موجود مسبقًا، الرجاء اختيار اسم آخر.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $pdo->beginTransaction();
            try {
                // إضافة المشرف
                $stmt = $pdo->prepare("INSERT INTO supervisors (name, username, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $username, $hashed_password]);
                $supervisor_id = $pdo->lastInsertId();

                // ربط الأقسام
                $linkStmt = $pdo->prepare("INSERT INTO supervisor_sections (supervisor_id, section_id) VALUES (?, ?)");
                foreach ($selected_sections as $section_id) {
                    $linkStmt->execute([$supervisor_id, $section_id]);
                }

                // تسجيل العملية في logs
                $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$_SESSION['admin_id'], 'إضافة', 'supervisors', $supervisor_id]);

                $pdo->commit();
                $_SESSION['msg'] = "✅ تم إضافة المشرف وربطه بالأقسام بنجاح.";
                header("Location: manage_supervisors.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "❌ حدث خطأ: " . $e->getMessage();
            }
        }
    } else {
        $msg = "⚠️ يرجى ملء جميع الحقول وتحديد الأقسام.";
    }
}

// جلب جميع المشرفين مع الأقسام
$supervisorsStmt = $pdo->query("SELECT * FROM supervisors ORDER BY id DESC");
$supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة المشرفين</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>👤 إدارة المشرفين</h2>

    <?php if (isset($_SESSION['msg'])): ?>
        <p class="success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
    <?php elseif (isset($msg)): ?>
        <p class="error"><?= $msg; ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" placeholder="اسم المشرف" required>
        <input type="text" name="username" placeholder="اسم المستخدم" required>
        <input type="password" name="password" placeholder="كلمة المرور" required>
        <label>🗂️ الأقسام التي يشرف عليها:</label>
        <div class="multiselect-container">
            <?php foreach ($sections as $sec): ?>
                <label>
                    <input type="checkbox" name="sections[]" value="<?= $sec['id'] ?>">
                    <?= htmlspecialchars($sec['level_name'] . " - " . $sec['section_name']) ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="add_supervisor">➕ إضافة مشرف</button>
    </form>

    <h3>📋 قائمة المشرفين</h3>
    <?php if ($supervisors): ?>
        <table>
            <tr>
                <th>الاسم</th>
                <th>اسم المستخدم</th>
                <th>الأقسام المشرف عليها</th>
                <th>الإجراءات</th>
            </tr>
            <?php foreach ($supervisors as $supervisor): ?>
                <?php
                $sectionsStmt = $pdo->prepare("
                    SELECT sections.name AS section_name, levels.name AS level_name
                    FROM supervisor_sections
                    JOIN sections ON supervisor_sections.section_id = sections.id
                    JOIN levels ON sections.level_id = levels.id
                    WHERE supervisor_sections.supervisor_id = ?
                ");
                $sectionsStmt->execute([$supervisor['id']]);
                $supervisor_sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <tr>
                    <td><?= htmlspecialchars($supervisor['name']) ?></td>
                    <td><?= htmlspecialchars($supervisor['username']) ?></td>
                    <td>
                        <?php
                        if ($supervisor_sections) {
                            foreach ($supervisor_sections as $s) {
                                echo htmlspecialchars($s['level_name'] . " - " . $s['section_name']) . "<br>";
                            }
                        } else {
                            echo "<span class='warning'>لا توجد أقسام</span>";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="edit_supervisor.php?id=<?= $supervisor['id'] ?>">✏️</a>
                        <a href="delete_supervisor.php?id=<?= $supervisor['id'] ?>" onclick="return confirm('⚠️ هل أنت متأكد من حذف هذا المشرف؟');">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>⚠️ لا يوجد مشرفون مسجلون حتى الآن.</p>
    <?php endif; ?>

    <a href="admin_dashboard.php"><button>🏠 رجوع إلى لوحة التحكم</button></a>
</div>
</body>
</html>
