<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['supervisor_id'])) { header('Location: index.php'); exit; }

$id = $_GET['id'] ?? null;
if (!$id) { die('معرف الغياب غير صالح.'); }

$stmt = $pdo->prepare("SELECT * FROM absences WHERE id = ?");
$stmt->execute([$id]);
$absence = $stmt->fetch();
if (!$absence) { die('السجل غير موجود.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $absence_date = $_POST['absence_date'];
    $absence_hours = $_POST['absence_hours'] ?? [];
    $absence_hours_str = implode(',', $absence_hours);

    $update = $pdo->prepare("UPDATE absences SET absence_date = ?, absence_hours = ? WHERE id = ?");
    $update->execute([$absence_date, $absence_hours_str, $id]);

    $role = isset($_SESSION['admin_id']) ? 'admin' : 'supervisor';
    $admin_id = $_SESSION['admin_id'] ?? $_SESSION['supervisor_id'] ?? null;
    $action = "تعديل غياب للطالب رقم: $id";
    $logStmt = $pdo->prepare("INSERT INTO logs (admin_id, action, created_at, role) VALUES (?, ?, NOW(), ?)");
    $logStmt->execute([$admin_id, $action, $role]);

    $_SESSION['msg'] = "✅ تم تعديل الغياب بنجاح.";
    header("Location: daily_report.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<title>تعديل غياب الطالب</title>
</head>
<body>
<div class="container">
    <h1>✏️ تعديل غياب الطالب</h1>
    <form method="post">
        <label>📅 تاريخ الغياب:</label>
        <input type="date" name="absence_date" value="<?= htmlspecialchars($absence['absence_date']) ?>" required>

        <label>⏰ ساعات الغياب (يمكن اختيار أكثر من ساعة):</label>
        <?php
        $hours_options = [
            1 => '08:00 - 09:00',
            2 => '09:00 - 10:00',
            3 => '10:00 - 11:00',
            4 => '11:00 - 12:00',
            5 => '14:00 - 15:00',
            6 => '15:00 - 16:00',
            7 => '16:00 - 17:00'
        ];
        $selected_hours = explode(',', $absence['absence_hours']);
        foreach ($hours_options as $key => $label): ?>
            <label><input type="checkbox" name="absence_hours[]" value="<?= $key ?>" <?= in_array($key, $selected_hours) ? 'checked' : '' ?>> <?= $label ?></label><br>
        <?php endforeach; ?>

        <button type="submit">💾 حفظ التغييرات</button>
    </form>
    <a href="daily_report.php"><button>🔙 رجوع</button></a>
</div>
</body>
</html>
