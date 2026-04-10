<?php
session_start(); // Убедитесь, что сессия запущена

// Подключение к БД (ваш существующий код)
require_once 'db.php'; // где определён $pdo

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];

if ($quantity <= 0) {
    die("Некорректное количество");
}

// Проверяем, существует ли товар и активен ли он
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);<?php
session_start();
require_once 'db.php'; // ваш файл с подключением $pdo

header('Content-Type: application/json');

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($quantity <= 0 || $product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit;
}

// Проверяем товар в БД
$stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit;
}

if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно на складе']);
    exit;
}

// Работа с сессией
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = $quantity;
}

// Подсчитываем общее количество товаров в корзине
$totalCount = array_sum($_SESSION['cart']);

echo json_encode([
    'success' => true,
    'message' => 'Товар добавлен',
    'cart_count' => $totalCount
]);
if (!$stmt->fetch()) {
    die("Товар не найден или недоступен");
}

// Инициализируем корзину
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Сохраняем только id и количество (данные о цене не храним!)
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = $quantity;
}

// Редирект на страницу корзины
header('Location: cart.php');
exit;