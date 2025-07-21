<?php
require 'db.php';

if (isset($_GET['level_id'])) {
    $level_id = intval($_GET['level_id']);
    $stmt = $pdo->prepare("SELECT id, name FROM sections WHERE level_id = ? ORDER BY name ASC");
    $stmt->execute([$level_id]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($sections);
}
?>
