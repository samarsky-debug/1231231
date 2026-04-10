<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
$stmt->execute([$login, $login]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    echo json_encode(['success' => true, 'username' => $user['username'], 'message' => 'Вход выполнен']);
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
}