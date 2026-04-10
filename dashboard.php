<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Личный кабинет</title>
</head>
<body>
    <h2>Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
    <p><a href="logout.php">Выйти</a></p>
</body>
</html>