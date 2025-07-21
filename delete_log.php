<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "✅ تم حذف السجل بنجاح.";
}

header('Location: logs.php');
exit;
?>
