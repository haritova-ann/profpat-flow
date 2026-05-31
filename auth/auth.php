<?php

session_start();

require_once __DIR__ . '/../config/db.php';

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE login = :login
");

$stmt->execute([
    'login' => $login
]);

$user = $stmt->fetch();

if (!$user) {
    die('Неверный логин или пароль');
}

if (!password_verify($password, $user['password_hash'])) {
    die('Неверный логин или пароль');
}

$_SESSION['user'] = [
    'id' => $user['id'],
    'role' => $user['role'],
    'full_name' => $user['full_name'],
    'specialization' => $user['specialization']
];

header('Location: /index.php');
exit;