<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['authenticated' => false]);
    exit();
}

$q1 = $pdo->query("SELECT COUNT(*) FROM students");
$total = (int)$q1->fetchColumn();

$q2 = $pdo->query("SELECT COUNT(*) FROM students WHERE created_at >= NOW() - INTERVAL 1 DAY");
$recent = (int)$q2->fetchColumn();

echo json_encode([
    'authenticated' => true,
    'username' => $_SESSION['username'],
    'total_students' => $total,
    'recent_students' => $recent
]);
?>