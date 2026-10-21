<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$error = '';

if (getCurrentUser($db)) {
    redirectTo('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (!validCsrfToken()) {
        $error = 'Форма устарела. Обновите страницу и попробуйте ещё раз.';
    } elseif (!$db) {
        $error = 'Не удалось подключиться к базе данных.';
    } elseif (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 80) {
        $error = 'Имя должно содержать от 2 до 80 символов.';
    } elseif (!preg_match('/^[\p{L}\p{N}_.-]{3,24}$/u', $username)) {
        $error = 'Никнейм: 3–24 символа — буквы, цифры, точка, дефис или подчёркивание.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Введите почту в корректном формате, например name@example.com.';
    } elseif (strlen($password) < 8 || strlen($password) > 200) {
        $error = 'Пароль должен содержать от 8 до 200 символов.';
    } elseif (!preg_match('/[A-ZА-ЯЁ]/u', $password) || !preg_match('/\d/', $password)) {
        $error = 'Добавьте в пароль хотя бы одну заглавную букву и одну цифру.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Пароли не совпадают.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $db->prepare('INSERT INTO users (email, username, display_name, password_hash) VALUES (?, ?, ?, ?)');
        if ($statement) {
            $statement->bind_param('ssss', $email, $username, $displayName, $passwordHash);
            if ($statement->execute()) {
                $newUserId = (int) $db->insert_id;
                $statement->close();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUserId;
                setFlashMessage('Аккаунт создан. Добро пожаловать!');
                redirectTo('profile.php');
            }
            $duplicate = $statement->errno === 1062;
            $statement->close();
            $error = $duplicate ? 'Эта почта или никнейм уже зарегистрированы.' : 'Не удалось создать аккаунт. Проверьте базу данных.';
        } else {
            $error = 'Таблицы аккаунтов ещё не созданы. Сначала импортируйте database/user-accounts.sql в базу db_web.';
        }
    }
}

renderHeader('Регистрация', $db);
?>
<section class="auth-page">
    <p class="eyebrow">SoundFusion · новый аккаунт</p>
    <h1>Создайте профиль.</h1>
    <p class="auth-lead">Соберите личный список любимых артистов и следите за новостями о них.</p>
    <?php if ($error !== ''): ?><div class="form-message form-message-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form class="form-card registration-form" method="post" action="register.php">
        <?= csrfField() ?>
        <label for="display_name">Имя</label>
        <input id="display_name" name="display_name" type="text" autocomplete="name" maxlength="80" required value="<?= e((string) ($_POST['display_name'] ?? '')) ?>">
        <label for="username">Уникальный никнейм</label>
        <input id="username" name="username" type="text" autocomplete="username" minlength="3" maxlength="24" required value="<?= e((string) ($_POST['username'] ?? '')) ?>">
        <small class="form-help">3–24 символа: буквы, цифры, точка, дефис или подчёркивание.</small>
        <label for="email">Электронная почта</label>
        <input id="email" name="email" type="email" autocomplete="email" inputmode="email" maxlength="190" spellcheck="false" required value="<?= e((string) ($_POST['email'] ?? '')) ?>">
        <small class="form-help">Укажите действительный формат адреса, например name@example.com.</small>
        <label for="password">Пароль</label>
        <div class="password-field">
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="200" pattern="(?=.*[A-ZА-ЯЁ])(?=.*[0-9]).{8,200}" title="Не менее 8 символов, включая заглавную букву и цифру." aria-describedby="password-rules" required>
            <button class="password-visibility" type="button" data-password-visibility aria-label="Удерживайте, чтобы показать пароль" title="Удерживайте, чтобы показать пароль" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-visibility-slash" d="m4 4 16 16"/></svg>
            </button>
        </div>
        <small class="form-help" id="password-rules">Не менее 8 символов, включая хотя бы одну заглавную букву и одну цифру.</small>
        <label for="password_confirm">Повторите пароль</label>
        <div class="password-field">
            <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" maxlength="200" required>
            <button class="password-visibility" type="button" data-password-visibility aria-label="Удерживайте, чтобы показать пароль" title="Удерживайте, чтобы показать пароль" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-visibility-slash" d="m4 4 16 16"/></svg>
            </button>
        </div>
        <button class="account-button form-submit" type="submit">Зарегистрироваться</button>
    </form>
    <p class="auth-switch">Уже есть аккаунт? <a href="login.php">Войти</a>.</p>
</section>
<script src="password-visibility.js" defer></script>
<?php renderFooter(); closeDB($db); ?>
