<?php
session_start();
header('Content-Type: application/json');

// Подключение к БД (замените параметры на свои)
$host = 'localhost';
$dbname = 'shop';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка подключения к БД']);
    exit;
}

// Инициализация корзины в сессии
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ------------------------------------------------------------------
// 1. Добавление товара
// ------------------------------------------------------------------
if ($action === 'add') {
    $id = (int)($_POST['id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($id <= 0) {
        echo json_encode(['error' => 'Неверный ID товара']);
        exit;
    }

    // Проверяем, существует ли товар в БД
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Товар не найден']);
        exit;
    }

    // Добавляем или увеличиваем количество
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] += $quantity;
    } else {
        $_SESSION['cart'][$id] = $quantity;
    }

    echo json_encode(['success' => true]);
    exit;
}

// ------------------------------------------------------------------
// 2. Изменение количества (delta = +1 / -1)
// ------------------------------------------------------------------
if ($action === 'update') {
    $id = (int)($_POST['cartId'] ?? 0);
    $delta = (int)($_POST['delta'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['error' => 'Неверный ID товара']);
        exit;
    }

    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] += $delta;
        if ($_SESSION['cart'][$id] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Товар не найден в корзине']);
    }
    exit;
}

// ------------------------------------------------------------------
// 3. Удаление товара полностью
// ------------------------------------------------------------------
if ($action === 'remove') {
    $id = (int)($_POST['cartId'] ?? 0);
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Товар не найден']);
    }
    exit;
}

// ------------------------------------------------------------------
// 4. Очистка всей корзины
// ------------------------------------------------------------------
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true]);
    exit;
}

// ------------------------------------------------------------------
// 5. Получение текущего состояния корзины (используется при открытии и обновлении)
// ------------------------------------------------------------------
if ($action === 'get') {
    $cartItems = [];
    $total = 0;

    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, title, price, size, material FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Преобразуем в ассоциативный массив [id => данные]
        $productsById = [];
        foreach ($products as $p) {
            $productsById[$p['id']] = $p;
        }

        foreach ($_SESSION['cart'] as $id => $quantity) {
            if (!isset($productsById[$id])) {
                // Товар удалён из БД – убираем из корзины
                unset($_SESSION['cart'][$id]);
                continue;
            }
            $product = $productsById[$id];
            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;

            // Формируем путь к изображению (подставьте свою логику)
            // Например, если картинки лежат в папке img/ и называются id.jpg
            $imagePath = "img/{$id}.jpg";
            if (!file_exists($imagePath)) {
                $imagePath = "img/placeholder.jpg"; // заглушка
            }

            $cartItems[] = [
                'cartId'   => $id,
                'id'       => $id,
                'name'     => $product['title'],
                'price'    => (float)$product['price'],
                'quantity' => $quantity,
                'size'     => $product['size'],
                'material' => $product['material'],
                'image'    => $imagePath
            ];
        }
    }

    echo json_encode([
        'items' => $cartItems,
        'total' => $total
    ]);
    exit;
}

// Если action не распознан
echo json_encode(['error' => 'Неверное действие']);