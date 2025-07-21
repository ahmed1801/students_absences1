<?php
require 'db.php';

if (isset($_GET['section_id'])) {
    $section_id = $_GET['section_id'];

    // جلب الطلاب التابعين لهذا القسم
    $stmt = $pdo->prepare("SELECT id, name FROM students WHERE section_id = ? ORDER BY name ASC");
    $stmt->execute([$section_id]);
    $students = $stmt->fetchAll();

    if ($students) {
        echo '<option value="">اختر الطالب</option>';
        foreach ($students as $student) {
            echo '<option value="' . $student['id'] . '">' . htmlspecialchars($student['name']) . '</option>';
        }
    } else {
        echo '<option value="">لا يوجد طلاب في هذا القسم</option>';
    }
} else {
    echo '<option value="">حدث خطأ</option>';
}
?>
