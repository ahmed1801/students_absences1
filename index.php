<?php
session_start();
require 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username !== '' && $password !== '') {
        // تحقق من المدير
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header("Location: admin_dashboard.php");
            exit;
        }

        // تحقق من المشرف
        $stmt = $pdo->prepare("SELECT * FROM supervisors WHERE username = ?");
        $stmt->execute([$username]);
        $supervisor = $stmt->fetch();

        if ($supervisor && password_verify($password, $supervisor['password'])) {
            $_SESSION['supervisor_id'] = $supervisor['id'];
            $_SESSION['supervisor_name'] = $supervisor['name'];
            header("Location: supervisor_dashboard.php");
            exit;
        }

        $msg = "❌ اسم المستخدم أو كلمة المرور غير صحيحة.";
    } else {
        $msg = "❌ يرجى ملء جميع الحقول.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            display: flex; align-items: center; justify-content: center;
            height: 100vh; background-color: #f9f9f9; font-family: sans-serif;
        }
        .login-container {
            background: #fff; padding: 20px; border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 320px; text-align: center;
        }
        .login-container h2 { margin-bottom: 20px; }
        .login-container input {
            width: 90%; padding: 10px; margin: 10px 0;
            border: 1px solid #ccc; border-radius: 4px;
        }
        .login-container button {
            width: 95%; padding: 10px; background: #4CAF50;
            border: none; color: white; border-radius: 4px;
            font-size: 16px; cursor: pointer;
        }
        .login-container button:hover { background: #45a049; }
        .msg { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>تسجيل الدخول للنظام</h2>
        <?php if ($msg): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit">🔐 تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
