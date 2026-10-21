<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$error = '';
$success = takeFlashMessage();

if (getCurrentUser($db)) {
    redirectTo('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!validCsrfToken()) {
        $error = 'Форма устарела. Обновите страницу и попробуйте ещё раз.';
    } elseif (!$db) {
        $error = 'Не удалось подключиться к базе данных.';
    } elseif ($identity === '' || $password === '') {
        $error = 'Введите почту или никнейм и пароль.';
    } else {
        $statement = $db->prepare('SELECT id, password_hash FROM users WHERE email = ? OR username = ? LIMIT 1');
        if ($statement) {
            $statement->bind_param('ss', $identity, $identity);
            $statement->execute();
            $result = $statement->get_result();
            $account = $result ? $result->fetch_assoc() : null;
            $statement->close();
            if ($account && password_verify($password, (string) $account['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $account['id'];
                setFlashMessage('Вы вошли в SoundFusion.');
                $destination = (string) ($_SESSION['after_login'] ?? 'profile.php');
                unset($_SESSION['after_login']);
                redirectTo($destination === 'profile.php' ? $destination : 'profile.php');
            }
        }
        $error = 'Не удалось войти. Проверьте почту или никнейм и пароль.';
    }
}

renderHeader('Войти', $db);
?>
<section class="auth-page">
    <p class="eyebrow">SoundFusion · аккаунт</p>
    <h1>С возвращением.</h1>
    <p class="auth-lead">Войдите, чтобы управлять любимыми артистами и получать новости о них в приоритете.</p>
    <?php if ($success): ?><div class="form-message form-message-success" role="status"><?= e((string) ($success['text'] ?? '')) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="form-message form-message-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form class="form-card" method="post" action="login.php">
        <?= csrfField() ?>
        <label for="identity">Почта или никнейм</label>
        <input id="identity" name="identity" type="text" autocomplete="username" required value="<?= e((string) ($_POST['identity'] ?? '')) ?>">
        <label for="password">Пароль</label>
        <div class="password-field">
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="password-visibility" type="button" data-password-visibility aria-label="Удерживайте, чтобы показать пароль" title="Удерживайте, чтобы показать пароль" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-visibility-slash" d="m4 4 16 16"/></svg>
            </button>
        </div>
        <button class="account-button form-submit" type="submit">Войти</button>
    </form>
    <p class="auth-switch">Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>.</p>
    <?php if (!$db): ?><p class="form-help">Проверьте, что MySQL запущен и база данных `db_web` доступна.</p><?php endif; ?>
</section>
<script src="password-visibility.js" defer></script>
<?php renderFooter(); closeDB($db); ?>
