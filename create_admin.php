<?php
require 'db.php';

$username = 'ahmed'; // اسم المستخدم الافتراضي
$password = '123456'; // كلمة المرور الافتراضية

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->execute([$username, $hashedPassword]);

echo "✅ تم إنشاء مستخدم admin بكلمة مرور 123456 بنجاح.";
?>
