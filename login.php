<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_email = isset($_POST['username_email']) ? trim($_POST['username_email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username_email) || empty($password)) {
        header("Location: login.html?error=1");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$username_email, $username_email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];

        header("Location: index.html?login_success=1&user=" . urlencode($user['username']));
        exit();
    } else {
        header("Location: login.html?error=1");
        exit();
    }
}
header("Location: login.html");
exit();
?>