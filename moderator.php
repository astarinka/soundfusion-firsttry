<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$user = getCurrentUser($db);
if (!$user) {
    redirectTo('login.php');
}
if (($user['role'] ?? 'user') !== 'moderator') {
    redirectTo('profile.php');
}

$members = [];
$queryError = '';
if ($db) {
    $result = $db->query("SELECT u.id, u.email, u.username, u.display_name, u.created_at, COUNT(DISTINCT f.artist_id) AS favorite_count, COALESCE(GROUP_CONCAT(DISTINCT c.companyname ORDER BY c.companyname SEPARATOR ', '), '') AS favorites FROM users u LEFT JOIN user_artist_favorites f ON f.user_id = u.id LEFT JOIN content c ON c.id = f.artist_id GROUP BY u.id, u.email, u.username, u.display_name, u.created_at ORDER BY u.created_at DESC, u.id DESC");
    if ($result) {
        $members = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $queryError = 'Не удалось загрузить список. Проверьте, что миграция database/user-accounts.sql импортирована в базу db_web.';
    }
} else {
    $queryError = 'Нет подключения к базе данных.';
}

renderHeader('Модерация', $db);
?>
<section class="moderator-page">
    <p class="eyebrow">Закрытый раздел</p>
    <h1>Участники SoundFusion.</h1>
    <p class="auth-lead">Здесь отображаются аккаунты и выбранные ими любимые артисты. Пароли и их хеши в этом списке не показываются.</p>
    <nav class="moderator-tabs" aria-label="Разделы модерации">
        <a href="moderator.php" aria-current="page">Участники</a>
        <a href="moderator-artists.php">Артисты</a>
    </nav>
    <div class="moderator-summary"><strong><?= count($members) ?></strong><span>зарегистрированных пользователей</span></div>
    <?php if ($queryError !== ''): ?><div class="form-message form-message-error" role="alert"><?= e($queryError) ?></div><?php elseif (!$members): ?><div class="empty-state"><p>Пока никто не зарегистрировался.</p></div><?php else: ?>
        <div class="member-list-wrap">
            <table class="member-list">
                <thead><tr><th>Пользователь</th><th>Почта</th><th>Любимые артисты</th><th>Дата регистрации</th></tr></thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><strong><?= e((string) $member['display_name']) ?></strong><span class="member-username">@<?= e((string) $member['username']) ?></span></td>
                            <td><a href="mailto:<?= e((string) $member['email']) ?>"><?= e((string) $member['email']) ?></a></td>
                            <td><?= (int) $member['favorite_count'] > 0 ? e((string) $member['favorites']) : '<span class="member-empty">Пока не выбраны</span>' ?></td>
                            <td><?= e(date('d.m.Y', strtotime((string) $member['created_at']) ?: 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); closeDB($db); ?>
