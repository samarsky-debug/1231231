<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (empty($_SESSION['cart'])) {
    echo json_encode(['items' => [], 'total' => 0]);
    exit;
}

$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, discount_price, image FROM products WHERE id IN ($placeholders) AND is_active = 1");
$stmt->execute($ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
$total = 0;

foreach ($products as $product) {
    $id = $product['id'];
    $quantity = $_SESSION['cart'][$id];
    $price = $product['discount_price'] ?? $product['price'];
    $subtotal = $price * $quantity;
    $total += $subtotal;

    $items[] = [
        'id' => $id,
        'name' => $product['name'],
        'price' => $price,
        'quantity' => $quantity,
        'image' => $product['image'],
        'subtotal' => $subtotal
    ];
}

echo json_encode(['items' => $items, 'total' => $total]);