<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$user = getCurrentUser($db);
if (!$user) {
    $_SESSION['after_login'] = 'profile.php';
    redirectTo('login.php');
}

$tab = (string) ($_GET['tab'] ?? 'profile');
if (!in_array($tab, ['profile', 'favorites'], true)) {
    $tab = 'profile';
}
$error = '';
$success = takeFlashMessage();
$formDisplayName = (string) ($user['display_name'] ?? '');
$formUsername = (string) ($user['username'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if (!validCsrfToken()) {
        $error = 'Форма устарела. Обновите страницу и попробуйте ещё раз.';
    } elseif (!$db) {
        $error = 'Не удалось подключиться к базе данных.';
    } elseif ($action === 'save_profile') {
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $formDisplayName = $displayName;
        $formUsername = $username;
        if (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 80) {
            $error = 'Имя должно содержать от 2 до 80 символов.';
        } elseif (!preg_match('/^[\p{L}\p{N}_.-]{3,24}$/u', $username)) {
            $error = 'Никнейм: от 3 до 24 символов — буквы, цифры, точка, дефис или подчёркивание.';
        } else {
            $statement = $db->prepare('UPDATE users SET display_name = ?, username = ? WHERE id = ?');
            if ($statement) {
                $userId = (int) $user['id'];
                $statement->bind_param('ssi', $displayName, $username, $userId);
                if ($statement->execute()) {
                    setFlashMessage('Данные профиля сохранены.');
                    $statement->close();
                    redirectTo('profile.php');
                }
                $duplicate = $statement->errno === 1062;
                $statement->close();
                $error = $duplicate ? 'Этот никнейм уже занят.' : 'Не удалось сохранить профиль.';
            } else {
                $error = 'Не удалось подготовить сохранение профиля.';
            }
        }
        $tab = 'profile';
    } elseif ($action === 'upload_avatar') {
        $upload = $_FILES['avatar'] ?? null;
        if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = 'Выберите изображение размером не более 3 МБ.';
        } elseif ((int) ($upload['size'] ?? 0) > 3 * 1024 * 1024) {
            $error = 'Файл слишком большой. Максимальный размер — 3 МБ.';
        } else {
            $tmpPath = (string) ($upload['tmp_name'] ?? '');
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = is_uploaded_file($tmpPath) ? $finfo->file($tmpPath) : false;
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $dimensions = $tmpPath !== '' ? @getimagesize($tmpPath) : false;
            if (!isset($extensions[$mime]) || $dimensions === false) {
                $error = 'Поддерживаются только изображения JPG, PNG и WebP.';
            } else {
                $directory = __DIR__ . '/uploads/avatars';
                if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
                    $error = 'Не удалось создать папку для аватаров. Проверьте права на папку проекта.';
                } elseif (!is_writable($directory)) {
                    $error = 'Папка uploads/avatars недоступна для записи.';
                } else {
                    $fileName = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
                    $relativePath = 'uploads/avatars/' . $fileName;
                    $statement = $db->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
                    if ($statement && move_uploaded_file($tmpPath, $directory . '/' . $fileName)) {
                        $userId = (int) $user['id'];
                        $statement->bind_param('si', $relativePath, $userId);
                        if ($statement->execute()) {
                            $statement->close();
                            $oldAvatar = (string) ($user['avatar_path'] ?? '');
                            if (str_starts_with($oldAvatar, 'uploads/avatars/')) {
                                $oldPath = __DIR__ . '/' . $oldAvatar;
                                if (is_file($oldPath)) { @unlink($oldPath); }
                            }
                            setFlashMessage('Аватар обновлён.');
                            redirectTo('profile.php');
                        }
                        $statement->close();
                        @unlink($directory . '/' . $fileName);
                    }
                    $error = 'Не удалось сохранить аватар.';
                }
            }
        }
        $tab = 'profile';
    } elseif ($action === 'remove_avatar') {
        $statement = $db->prepare("UPDATE users SET avatar_path = '' WHERE id = ?");
        if ($statement) {
            $userId = (int) $user['id'];
            $statement->bind_param('i', $userId);
            if ($statement->execute()) {
                $statement->close();
                $oldAvatar = (string) ($user['avatar_path'] ?? '');
                if (str_starts_with($oldAvatar, 'uploads/avatars/')) {
                    $oldPath = __DIR__ . '/' . $oldAvatar;
                    if (is_file($oldPath)) { @unlink($oldPath); }
                }
                setFlashMessage('Аватар удалён.');
                redirectTo('profile.php');
            }
            $statement->close();
        }
        $error = 'Не удалось удалить аватар.';
        $tab = 'profile';
    } elseif ($action === 'save_favorites') {
        $selectedIds = $_POST['artist_ids'] ?? [];
        if (!is_array($selectedIds)) { $selectedIds = []; }
        $selectedIds = array_values(array_unique(array_filter(array_map('intval', $selectedIds), static fn (int $id): bool => $id > 0)));
        $db->begin_transaction();
        $delete = $db->prepare('DELETE FROM user_artist_favorites WHERE user_id = ?');
        $ok = false;
        if ($delete) {
            $userId = (int) $user['id'];
            $delete->bind_param('i', $userId);
            $ok = $delete->execute();
            $delete->close();
        }
        if ($ok && $selectedIds) {
            $insert = $db->prepare('INSERT IGNORE INTO user_artist_favorites (user_id, artist_id) SELECT ?, id FROM content WHERE id = ? AND active = 1');
            if (!$insert) {
                $ok = false;
            } else {
                foreach ($selectedIds as $artistId) {
                    $insert->bind_param('ii', $userId, $artistId);
                    if (!$insert->execute()) { $ok = false; break; }
                }
                $insert->close();
            }
        }
        if ($ok) {
            $db->commit();
            setFlashMessage('Список любимых артистов обновлён.');
            redirectTo('profile.php?tab=favorites');
        }
        $db->rollback();
        $error = 'Не удалось сохранить любимых артистов. Проверьте, что обновление таблиц аккаунтов импортировано.';
        $tab = 'favorites';
    }
}

$user = getCurrentUser($db) ?? $user;
$allArtists = fetchArtists($db);
$favoriteArtists = getUserFavoriteArtists($db, (int) $user['id']);
$favoriteIds = array_map(static fn (array $artist): int => (int) $artist['id'], $favoriteArtists);
renderHeader('Профиль', $db);
?>
<section class="profile-page">
    <p class="eyebrow">Личный кабинет</p>
    <h1>Ваш SoundFusion.</h1>
    <p class="auth-lead">Настройте профиль и выберите артистов, о которых хотите чаще видеть новости.</p>
    <?php if ($success): ?><div class="form-message form-message-success" role="status"><?= e((string) ($success['text'] ?? '')) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="form-message form-message-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <nav class="profile-tabs" aria-label="Разделы профиля">
        <a href="profile.php?tab=profile"<?= $tab === 'profile' ? ' aria-current="page"' : '' ?>>Профиль</a>
        <a href="profile.php?tab=favorites"<?= $tab === 'favorites' ? ' aria-current="page"' : '' ?>>Любимые артисты <span><?= count($favoriteArtists) ?></span></a>
    </nav>

    <?php if ($tab === 'profile'): ?>
        <div class="profile-layout">
            <section class="form-card avatar-card">
                <h2>Аватар</h2>
                <?php $avatar = (string) ($user['avatar_path'] ?? ''); ?>
                <?php if ($avatar !== '' && is_file(__DIR__ . '/' . $avatar)): ?>
                    <img class="profile-avatar" src="<?= e($avatar) ?>" alt="Аватар пользователя">
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) ($user['display_name'] ?? 'U'), 0, 1))) ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" action="profile.php">
                    <?= csrfField() ?><input type="hidden" name="action" value="upload_avatar">
                    <label for="avatar">Изображение</label>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
                    <small class="form-help">JPG, PNG или WebP, до 3 МБ.</small>
                    <button class="account-button form-submit" type="submit">Загрузить аватар</button>
                </form>
                <?php if ($avatar !== ''): ?><form method="post" action="profile.php"><?= csrfField() ?><input type="hidden" name="action" value="remove_avatar"><button class="subtle-button" type="submit">Удалить аватар</button></form><?php endif; ?>
            </section>
            <form class="form-card" method="post" action="profile.php">
                <?= csrfField() ?><input type="hidden" name="action" value="save_profile">
                <h2>Данные профиля</h2>
                <label for="display_name">Имя</label>
                <input id="display_name" name="display_name" type="text" maxlength="80" required value="<?= e($formDisplayName) ?>">
                <label for="username">Никнейм</label>
                <input id="username" name="username" type="text" minlength="3" maxlength="24" required value="<?= e($formUsername) ?>">
                <label for="profile_email">Электронная почта</label>
                <input id="profile_email" type="email" value="<?= e((string) ($user['email'] ?? '')) ?>" disabled>
                <button class="account-button form-submit" type="submit">Сохранить изменения</button>
            </form>
        </div>
    <?php else: ?>
        <section class="favorites-panel">
            <h2>Любимые артисты</h2>
            <p>Выбранные имена будут учитываться при сортировке новостей. Можно выбрать несколько и изменить список в любое время.</p>
            <?php if ($allArtists): ?>
                <form method="post" action="profile.php?tab=favorites">
                    <?= csrfField() ?><input type="hidden" name="action" value="save_favorites">
                    <div class="favorite-choices">
                        <?php foreach ($allArtists as $artist): ?>
                            <label class="favorite-choice">
                                <input type="checkbox" name="artist_ids[]" value="<?= (int) $artist['id'] ?>"<?= in_array((int) $artist['id'], $favoriteIds, true) ? ' checked' : '' ?>>
                                <span><?php $favoriteImage = artistImagePath((string) ($artist['logo'] ?? '')); ?><?php if ($favoriteImage !== ''): ?><img src="<?= e($favoriteImage) ?>" alt="" loading="lazy"><?php else: ?><span class="favorite-initial" aria-hidden="true">SF</span><?php endif; ?></span>
                                <span><strong><?= e((string) $artist['companyname']) ?></strong><small><?= e((string) ($artist['category_name'] ?? 'Исполнитель')) ?></small><?php if (!empty($artist['about'])): ?><small class="favorite-bio"><?= e(artistBiographyPreview((string) $artist['about'], 110)) ?></small><?php endif; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button class="account-button form-submit" type="submit">Сохранить любимых артистов</button>
                </form>
            <?php else: ?>
                <div class="empty-state"><p>В каталоге пока нет активных исполнителей.</p><a class="text-link" href="music.php">Открыть каталог ↗</a></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
<?php renderFooter(); closeDB($db); ?>
