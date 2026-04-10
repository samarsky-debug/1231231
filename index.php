<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; // ваш файл с PDO

if (!isset($pdo)) {
    die('Ошибка: переменная $pdo не определена.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fashion Future</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <div class="header-content">
      <img class="header-logo" src="img/logo.png" alt="Fashion Future">
      <h1>Fashion Future</h1>
      <div class="shopping">
        <button type="image" id="cartIconBtn">
          <img src="img/shopping-cart.png" alt="Корзина">
        </button>
      </div>
      <div class="user-links">
        <?php if (isset($_SESSION['user_id'])): ?>
          <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
        <?php else: ?>
          <a href="login.php">Войти</a>
          <a href="registration.php">Регистрация</a>
        <?php endif; ?>
      </div>
      <div class="dashboard">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="personal_account.php">Личный кабинет</a>
        <?php else: ?>
          <a href="login.php">Личный кабинет</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <nav>
    <a href="category.php">Мужчине</a>
    <a href="womencategory.html">Женщине</a>
    <a href="about-us.html">О нас</a>
  </nav>
  <main>
    <section class="hero">
      <div class="hero-text">
        <h2>Одежда как<br>вторая кожа</h2>
        <p>Семиотика стиля, материальный манифест вашего «Я». Откройте новую коллекцию.</p>
        <a href="about-us.html" class="btn btn-outline">О бренде</a>
      </div>
      <div class="hero-image">
        <img src="img/t-shirt.jpg" alt="hero style" style="object-fit: cover; width: 100%; height: 100%;">
      </div>
    </section>
    <div class="fashion-container">
      <div class="fashionone">
        <p>Одежда — это не просто прикладной инструмент для защиты тела от холода или зноя. Это сложнейшая семиотическая система, «вторая кожа» и материальный манифест нашего внутреннего «Я». Если вдуматься, одежда — это единственный объект материального мира, который сопровождает человека от первого вздоха до последнего, становясь молчаливым свидетелем его эволюции.</p>
      </div>
      <div class="fashiontwo">
        <p>Одежда как граница между «Я» и «Миром». Одежда представляет собой пограничное состояние. Она обозначает предел нашего физического тела и начало внешнего пространства. Это мембрана, которая одновременно защищает нашу уязвимость и транслирует нашу силу. Выбирая, что надеть, мы бессознательно решаем, какую часть своей души мы готовы открыть миру, а какую — оставить в сакральной тишине приватности.</p>
      </div>
    </div>

    <!-- Секция "Бестселлеры" (мужская категория) -->
    <section id="men" class="category">
      <div class="bestseller"><h1>Бестселлеры</h1></div>
      <div class="products">
        <?php
        // Укажите ID товаров, которые относятся к мужской категории
        $men_ids = [1, 2, 3]; // ЗАМЕНИТЕ НА РЕАЛЬНЫЕ ID ИЗ ВАШЕЙ БД
        $ids_string = implode(',', array_map('intval', $men_ids));
        
        $sql_men = "SELECT 
                        p.id, 
                        p.title AS name, 
                        p.price,
                        (SELECT image_url FROM product_images 
                         WHERE product_id = p.id 
                         ORDER BY is_main DESC, sort_order LIMIT 1) AS image_url
                    FROM products p
                    WHERE p.id IN ($ids_string)
                    ORDER BY p.id
                    LIMIT 3";
        $stmt_men = $pdo->query($sql_men);
        $men_products = $stmt_men->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($men_products as $product):
            $product_id = $product['id'];
            $name = htmlspecialchars($product['name']);
            $price = (int)$product['price'];
            $price_formatted = number_format($price, 0, '.', ' ');
            $image_url = htmlspecialchars($product['image_url'] ?? '');
        ?>
        <div class="product" 
             data-id="<?= $product_id ?>" 
             data-name="<?= $name ?>" 
             data-price="<?= $price ?>" 
             data-image="<?= $image_url ?>">
          <a href="items/product.php?id=<?= $product_id ?>">
            <?php if (!empty($image_url)): ?>
              <img src="<?= $image_url ?>" alt="<?= $name ?>">
            <?php else: ?>
              <div class="no-image" style="width:100%; height:200px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">Нет фото</div>
            <?php endif; ?>
          </a>
          <div class="product-meta">
            <h3><?= $name ?></h3>
            <p class="price"><?= $price_formatted ?> ₽</p>
            <a href="items/product.php?id=<?= $product_id ?>" class="btn-small">Подробнее</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Секция "Женская категория" -->
    <section id="women" class="category">
      <div class="products">
        <?php
        // Укажите ID товаров, которые относятся к женской категории
        $women_ids = [4, 5, 6]; // ЗАМЕНИТЕ НА РЕАЛЬНЫЕ ID
        $ids_string_w = implode(',', array_map('intval', $women_ids));
        
        $sql_women = "SELECT 
                          p.id, 
                          p.title AS name, 
                          p.price,
                          (SELECT image_url FROM product_images 
                           WHERE product_id = p.id 
                           ORDER BY is_main DESC, sort_order LIMIT 1) AS image_url
                      FROM products p
                      WHERE p.id IN ($ids_string_w)
                      ORDER BY p.id
                      LIMIT 3";
        $stmt_women = $pdo->query($sql_women);
        $women_products = $stmt_women->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($women_products as $product):
            $product_id = $product['id'];
            $name = htmlspecialchars($product['name']);
            $price = (int)$product['price'];
            $price_formatted = number_format($price, 0, '.', ' ');
            $image_url = htmlspecialchars($product['image_url'] ?? '');
        ?>
        <div class="product" 
             data-id="<?= $product_id ?>" 
             data-name="<?= $name ?>" 
             data-price="<?= $price ?>" 
             data-image="<?= $image_url ?>">
          <a href="items/product.php?id=<?= $product_id ?>">
            <?php if (!empty($image_url)): ?>
              <img src="<?= $image_url ?>" alt="<?= $name ?>">
            <?php else: ?>
              <div class="no-image" style="width:100%; height:200px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">Нет фото</div>
            <?php endif; ?>
          </a>
          <div class="product-meta">
            <h3><?= $name ?></h3>
            <p class="price"><?= $price_formatted ?> ₽</p>
            <a href="items/product.php?id=<?= $product_id ?>" class="btn-small">Подробнее</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <!-- панель корзины -->
  <div class="cart-overlay" id="cartOverlay"></div>
  <div class="cart-panel" id="cartPanel">
    <div class="cart-header">
      <h2>Корзина</h2>
      <button class="close-cart" id="closeCart">&times;</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer" id="cartFooter">
      <div class="cart-total">
        <span>Итого:</span>
        <span id="cartTotal">0 ₽</span>
      </div>
      <button class="checkout-btn" id="checkoutBtn">Оформить заказ</button>
    </div>
  </div>

  <footer class="main-footer">
    <div class="footer-container">
      <div class="footer-col logo-info">
        <div class="legal-info">
          <p>ИП Самарский Дмитрий Константинович</p>
          <p>ИНН 77777777777</p>
          <p>ОГРНИП 777777777777777</p>
          <p>FashionFuture@yandex.ru</p>
        </div>
      </div>
      <div class="footer-col">
        <h3>Полезное</h3>
        <ul>
          <li><a href="#">Доставка и оплата</a></li>
          <li><a href="#">Политика конфиденциальности</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h3>Контакты</h3>
        <p>Адрес оффлайн магазина — г. Кемерово, ул. Ленина, 73</p>
        <p class="phone">Для заказа онлайн и в другие города<br>+7 800 555 35 35</p>
        <div class="social-icons">
          <a href="https://t.me/">Telegram</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="js/korzina.js"></script>
</body>
</html>