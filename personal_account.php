<?php
// personal_account.php
// Личный кабинет: авторизация + отображение данных пользователя + смена пароля
// База данных: localhost, БД: samarskiy, таблица: users (поля: id, username, email, created_at, password)

session_start();

// Настройки подключения к БД
$host = 'localhost';
$user = 'root';
$password = '';        // Пароль от БД (для root часто пустой)
$dbname = 'samarskiy';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения к БД: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ------------------------------------------------------------
// 1. ПРОВЕРКА НАЛИЧИЯ ПОЛЯ "password" В ТАБЛИЦЕ users
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''");
    // Устанавливаем тестовый пароль '123456' для всех существующих пользователей
    $defaultHash = password_hash('123456', PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password = '$defaultHash' WHERE password = ''");
}
// ------------------------------------------------------------

// Обработка выхода из аккаунта
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Переменные для сообщений
$loginError = '';
$passwordChangeMessage = '';
$passwordChangeError = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $pass = $_POST['password'];

    if ($username === '' || $pass === '') {
        $loginError = 'Заполните логин и пароль.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $userRow = $res->fetch_assoc();
            if (password_verify($pass, $userRow['password']) || $userRow['password'] === $pass) {
                $_SESSION['user_id'] = $userRow['id'];
                $_SESSION['username'] = $userRow['username'];
                header("Location: personal_account.php");
                exit;
            } else {
                $loginError = 'Неверный пароль.';
            }
        } else {
            $loginError = 'Пользователь не найден.';
        }
        $stmt->close();
    }
}

// Обработка смены пароля (только для авторизованных)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && isset($_SESSION['user_id'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    $userId = (int)$_SESSION['user_id'];

    // Валидация
    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $passwordChangeError = 'Заполните все поля.';
    } elseif ($newPass !== $confirmPass) {
        $passwordChangeError = 'Новый пароль и подтверждение не совпадают.';
    } elseif (strlen($newPass) < 4) {
        $passwordChangeError = 'Новый пароль должен содержать минимум 4 символа.';
    } else {
        // Получаем текущий хэш пароля из БД
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentHash = $row['password'];
            // Проверяем текущий пароль
            if (password_verify($currentPass, $currentHash) || $currentHash === $currentPass) {
                // Хэшируем новый пароль и обновляем
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newHash, $userId);
                if ($updateStmt->execute()) {
                    $passwordChangeMessage = 'Пароль успешно изменён! Пожалуйста, войдите снова.';
                    // Разлогиниваем пользователя, чтобы он вошёл с новым паролем
                    session_destroy();
                    // Перенаправление через 2 секунды
                    header("refresh:2;url=personal_account.php");
                } else {
                    $passwordChangeError = 'Ошибка при обновлении пароля. Попробуйте позже.';
                }
                $updateStmt->close();
            } else {
                $passwordChangeError = 'Неверный текущий пароль.';
            }
        } else {
            $passwordChangeError = 'Пользователь не найден.';
        }
        $stmt->close();
    }
}

// ------------------------------------------------------------
// Получение данных текущего авторизованного пользователя
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $currentUser = $result->fetch_assoc();
    } else {
        session_destroy();
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        /* Дополнительные стили для отображения данных пользователя и сообщения об успехе */
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .info-row .label {
            font-weight: 600;
            color: #555;
        }
        .info-row .value {
            color: #333;
            text-align: right;
            word-break: break-word;
        }
        .message-success {
            background-color: #e6ffed;
            border-left: 5px solid #38a169;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            color: #2c7a4b;
            font-size: 0.9rem;
        }
        /* Кнопка-переключатель формы смены пароля (уже стилизована через button) */
        .toggle-password-btn {
            margin-bottom: 1rem;
        }
        /* Ссылка выхода */
        .logout-link {
            display: inline-block;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div style="width: 100%; max-width: 420px;">
        <a href="index.php" style="display: inline-block; margin-bottom: 1.2rem;">← На главную</a>

        <?php if ($currentUser !== null): ?>
            <!-- Блок с данными пользователя (используем класс .login-form для фона и тени) -->
            <div class="login-form">
                <div class="info-row">
                    <div class="label">Имя пользователя</div>
                    <div class="value"><strong><?= htmlspecialchars($currentUser['username']) ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="label">Email</div>
                    <div class="value"><?= htmlspecialchars($currentUser['email']) ?></div>
                </div>
                <div class="info-row">
                    <div class="label">Дата регистрации</div>
                    <div class="value">
                        <?php
                        $created = $currentUser['created_at'] ?? '';
                        if (!empty($created)) {
                            $ts = strtotime($created);
                            echo $ts ? date('d.m.Y \в H:i', $ts) : htmlspecialchars($created);
                        } else {
                            echo "— не указана —";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Кнопка для показа/скрытия формы смены пароля -->
            <button id="togglePasswordBtn" class="toggle-password-btn">Сменить пароль</button>

            <!-- Форма смены пароля (изначально скрыта) -->
            <div id="passwordChangeForm" class="login-form" style="display: none;">
                <h3 style="text-align: center; margin-bottom: 1rem;">Смена пароля</h3>
                <?php if ($passwordChangeMessage): ?>
                    <div class="message-success">✅ <?= htmlspecialchars($passwordChangeMessage) ?></div>
                <?php endif; ?>
                <?php if ($passwordChangeError): ?>
                    <div class="message-error">⚠️ <?= htmlspecialchars($passwordChangeError) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label>Текущий пароль</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>Новый пароль (мин. 4 символа)</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Подтверждение нового пароля</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password">Изменить пароль</button>
                </form>
            </div>

            <div>
                <a href="?logout=1" class="logout-link">Выйти из аккаунта</a>
            </div>

            <script>
                // Переключение видимости формы смены пароля
                const toggleBtn = document.getElementById('togglePasswordBtn');
                const formContainer = document.getElementById('passwordChangeForm');

                toggleBtn.addEventListener('click', function() {
                    if (formContainer.style.display === 'none') {
                        formContainer.style.display = 'block';
                        toggleBtn.textContent = 'Скрыть форму';
                    } else {
                        formContainer.style.display = 'none';
                        toggleBtn.textContent = 'Сменить пароль';
                    }
                });
            </script>

        <?php else: ?>
            <!-- Не авторизован: форма входа -->
            <div class="login-form">
                <h2>🔐 Вход в систему</h2>
                <?php if ($loginError): ?>
                    <div class="message-error">⚠️ <?= htmlspecialchars($loginError) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label>Логин</label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="login">Войти</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>