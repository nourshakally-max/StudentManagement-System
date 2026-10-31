<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?auth_required=1");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT profile_picture FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        if (!empty($row['profile_picture']) && file_exists($row['profile_picture'])) {
            unlink($row['profile_picture']);
        }
        $del = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $del->execute([$id]);
    }
}

header("Location: view_students.php?deleted=1");
exit();
?>