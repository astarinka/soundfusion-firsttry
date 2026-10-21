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

$error = '';
$success = takeFlashMessage();
$artistSearch = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
$editId = (int) ($_GET['edit'] ?? 0);
$formArtist = [
    'id' => 0,
    'companyname' => '',
    'about' => '',
    'logo' => '',
    'industry' => 0,
    'active' => 1,
    'featured' => 0,
    'link' => '',
    'description' => '',
    'album' => '',
];
$industries = [];

if ($db) {
    $industryResult = $db->query('SELECT id, description FROM industry ORDER BY description');
    if ($industryResult) {
        $industries = $industryResult->fetch_all(MYSQLI_ASSOC);
    }
    if ($editId > 0) {
        $editStatement = $db->prepare('SELECT id, companyname, about, logo, industry, active, featured, link, description, album FROM content WHERE id = ? LIMIT 1');
        if ($editStatement) {
            $editStatement->bind_param('i', $editId);
            if ($editStatement->execute()) {
                $editResult = $editStatement->get_result();
                $savedArtist = $editResult ? $editResult->fetch_assoc() : null;
                if ($savedArtist) {
                    $formArtist = $savedArtist;
                } else {
                    $error = 'Такой артист в каталоге не найден.';
                }
            }
            $editStatement->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if (!validCsrfToken()) {
        $error = 'Форма устарела. Обновите страницу и попробуйте ещё раз.';
    } elseif (!$db) {
        $error = 'Не удалось подключиться к базе данных.';
    } elseif ($action === 'save_artist') {
        $artistId = (int) ($_POST['artist_id'] ?? 0);
        $existing = null;
        if ($artistId > 0) {
            $existingStatement = $db->prepare('SELECT id, logo FROM content WHERE id = ? LIMIT 1');
            if ($existingStatement) {
                $existingStatement->bind_param('i', $artistId);
                if ($existingStatement->execute()) {
                    $existingResult = $existingStatement->get_result();
                    $existing = $existingResult ? $existingResult->fetch_assoc() : null;
                }
                $existingStatement->close();
            }
            if (!$existing) {
                $error = 'Артист для редактирования не найден.';
            }
        }

        $name = trim((string) ($_POST['companyname'] ?? ''));
        $about = trim((string) ($_POST['about'] ?? ''));
        $industryId = (int) ($_POST['industry'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $album = trim((string) ($_POST['album'] ?? ''));
        $link = trim((string) ($_POST['link'] ?? ''));
        $active = isset($_POST['active']) ? 1 : 0;
        $featured = $active === 1 && isset($_POST['featured']) ? 1 : 0;
        $logo = (string) ($existing['logo'] ?? '');
        $formArtist = [
            'id' => $artistId,
            'companyname' => $name,
            'about' => $about,
            'logo' => $logo,
            'industry' => $industryId,
            'active' => $active,
            'featured' => $featured,
            'link' => $link,
            'description' => $description,
            'album' => $album,
        ];

        if ($error === '' && (mb_strlen($name) < 2 || mb_strlen($name) > 160)) {
            $error = 'Имя артиста должно содержать от 2 до 160 символов.';
        } elseif ($error === '' && mb_strlen($about) > 10000) {
            $error = 'Биография слишком длинная (максимум 10 000 символов).';
        } elseif ($error === '' && (mb_strlen($description) > 255 || mb_strlen($album) > 5000)) {
            $error = 'Жанр должен быть до 255 символов, описание релизов — до 5 000.';
        } elseif ($error === '' && !in_array($industryId, array_map(static fn (array $item): int => (int) $item['id'], $industries), true)) {
            $error = 'Выберите жанр из списка.';
        } elseif ($error === '' && $link !== '') {
            $urlParts = parse_url($link);
            if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($urlParts['scheme'] ?? '')), ['http', 'https'], true)) {
                $error = 'Ссылка должна быть корректным адресом, начинающимся с http:// или https://.';
            }
        }

        if ($error === '' && $featured === 1 && $active === 1) {
            $countStatement = $db->prepare('SELECT COUNT(*) AS total FROM content WHERE active = 1 AND featured = 1 AND id <> ?');
            if (!$countStatement) {
                $error = 'Не удалось проверить места в авторской подборке.';
            } else {
                $countStatement->bind_param('i', $artistId);
                if ($countStatement->execute()) {
                    $countResult = $countStatement->get_result();
                    $featuredCount = (int) (($countResult ? $countResult->fetch_assoc()['total'] : 4));
                    if ($featuredCount >= 4) {
                        $error = 'В авторской подборке уже четыре артиста. Сначала снимите отметку с одного из них.';
                    }
                } else {
                    $error = 'Не удалось проверить места в авторской подборке.';
                }
                $countStatement->close();
            }
        }

        $uploadedLogoPath = '';
        $upload = $_FILES['logo'] ?? null;
        $uploadError = is_array($upload) ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        if ($error === '' && $uploadError !== UPLOAD_ERR_NO_FILE) {
            if ($uploadError !== UPLOAD_ERR_OK || (int) ($upload['size'] ?? 0) > 3 * 1024 * 1024) {
                $error = 'Изображение не загрузилось или превышает 3 МБ.';
            } else {
                $tmpPath = (string) ($upload['tmp_name'] ?? '');
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = is_uploaded_file($tmpPath) ? $finfo->file($tmpPath) : false;
                $dimensions = $tmpPath !== '' ? @getimagesize($tmpPath) : false;
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime]) || $dimensions === false) {
                    $error = 'Поддерживаются только настоящие изображения JPG, PNG и WebP.';
                } else {
                    $uploadDirectory = __DIR__ . '/uploads/artists';
                    if (!is_dir($uploadDirectory) && !@mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                        $error = 'Не удалось создать папку uploads/artists.';
                    } elseif (!is_writable($uploadDirectory)) {
                        $error = 'Папка uploads/artists недоступна для записи.';
                    } else {
                        $fileName = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
                        if (move_uploaded_file($tmpPath, $uploadDirectory . '/' . $fileName)) {
                            $uploadedLogoPath = 'uploads/artists/' . $fileName;
                            $logo = $uploadedLogoPath;
                            $formArtist['logo'] = $logo;
                        } else {
                            $error = 'Не удалось сохранить изображение артиста.';
                        }
                    }
                }
            }
        }

        if ($error === '') {
            if ($artistId > 0) {
                $saveStatement = $db->prepare('UPDATE content SET companyname = ?, about = ?, logo = ?, industry = ?, active = ?, featured = ?, link = ?, description = ?, album = ? WHERE id = ?');
                if ($saveStatement) {
                    $saveStatement->bind_param('sssiiisssi', $name, $about, $logo, $industryId, $active, $featured, $link, $description, $album, $artistId);
                }
            } else {
                $saveStatement = $db->prepare('INSERT INTO content (companyname, about, logo, industry, active, featured, link, description, album) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                if ($saveStatement) {
                    $saveStatement->bind_param('sssiiisss', $name, $about, $logo, $industryId, $active, $featured, $link, $description, $album);
                }
            }

            if (isset($saveStatement) && $saveStatement && $saveStatement->execute()) {
                $saveStatement->close();
                setFlashMessage($artistId > 0 ? 'Данные артиста обновлены.' : 'Артист добавлен в каталог.');
                redirectTo('moderator-artists.php');
            }
            if (isset($saveStatement) && $saveStatement) {
                $saveStatement->close();
            }
            if ($uploadedLogoPath !== '' && is_file(__DIR__ . '/' . $uploadedLogoPath)) {
                @unlink(__DIR__ . '/' . $uploadedLogoPath);
            }
            $error = 'Не удалось сохранить артиста. Проверьте структуру базы данных и повторите попытку.';
        }
    } elseif ($action === 'toggle_featured' || $action === 'toggle_active') {
        $artistId = (int) ($_POST['artist_id'] ?? 0);
        $artistStatement = $db->prepare('SELECT id, active, featured FROM content WHERE id = ? LIMIT 1');
        $managedArtist = null;
        if ($artistStatement) {
            $artistStatement->bind_param('i', $artistId);
            if ($artistStatement->execute()) {
                $artistResult = $artistStatement->get_result();
                $managedArtist = $artistResult ? $artistResult->fetch_assoc() : null;
            }
            $artistStatement->close();
        }

        if (!$managedArtist) {
            $error = 'Артист не найден.';
        } elseif ($action === 'toggle_featured' && (int) $managedArtist['featured'] === 0) {
            if ((int) $managedArtist['active'] !== 1) {
                $error = 'Сначала включите артиста в каталоге.';
            } else {
                $countResult = $db->query('SELECT COUNT(*) AS total FROM content WHERE active = 1 AND featured = 1');
                $featuredCount = (int) (($countResult ? $countResult->fetch_assoc()['total'] : 4));
                if ($featuredCount >= 4) {
                    $error = 'В авторской подборке уже четыре артиста. Сначала снимите отметку с одного из них.';
                }
            }
        }

        if ($error === '') {
            if ($action === 'toggle_featured') {
                $newValue = (int) $managedArtist['featured'] === 1 ? 0 : 1;
                $updateStatement = $db->prepare('UPDATE content SET featured = ? WHERE id = ?');
                if ($updateStatement) {
                    $updateStatement->bind_param('ii', $newValue, $artistId);
                }
                $message = $newValue === 1 ? 'Артист добавлен в авторскую подборку.' : 'Артист убран из авторской подборки.';
            } else {
                $newValue = (int) $managedArtist['active'] === 1 ? 0 : 1;
                $updateStatement = $db->prepare('UPDATE content SET active = ?, featured = CASE WHEN ? = 0 THEN 0 ELSE featured END WHERE id = ?');
                if ($updateStatement) {
                    $updateStatement->bind_param('iii', $newValue, $newValue, $artistId);
                }
                $message = $newValue === 1 ? 'Артист снова показывается в каталоге.' : 'Артист скрыт из каталога.';
            }

            if (isset($updateStatement) && $updateStatement && $updateStatement->execute()) {
                $updateStatement->close();
                setFlashMessage($message);
                redirectTo('moderator-artists.php');
            }
            if (isset($updateStatement) && $updateStatement) {
                $updateStatement->close();
            }
            $error = 'Не удалось обновить артиста.';
        }
    }
}

$artists = [];
if ($db) {
    if ($artistSearch !== '') {
        $artistStatement = $db->prepare('SELECT c.id, c.companyname, c.logo, c.active, c.featured, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry WHERE c.companyname LIKE ? OR i.description LIKE ? ORDER BY c.companyname');
        if ($artistStatement) {
            $pattern = '%' . $artistSearch . '%';
            $artistStatement->bind_param('ss', $pattern, $pattern);
            if ($artistStatement->execute()) {
                $artistResult = $artistStatement->get_result();
                $artists = $artistResult ? $artistResult->fetch_all(MYSQLI_ASSOC) : [];
            }
            $artistStatement->close();
        }
    } else {
        $artistResult = $db->query('SELECT c.id, c.companyname, c.logo, c.active, c.featured, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry ORDER BY c.companyname');
        if ($artistResult) {
            $artists = $artistResult->fetch_all(MYSQLI_ASSOC);
        }
    }
}

renderHeader('Артисты — модерация', $db);
?>
<section class="moderator-page">
    <p class="eyebrow">Закрытый раздел</p>
    <h1>Управление артистами.</h1>
    <p class="auth-lead">Добавляйте и редактируйте карточки каталога, временно скрывайте артистов и выбирайте до четырёх имён для гостевой авторской подборки.</p>
    <nav class="moderator-tabs" aria-label="Разделы модерации">
        <a href="moderator.php">Участники</a>
        <a href="moderator-artists.php" aria-current="page">Артисты</a>
    </nav>

    <?php if ($success): ?><div class="form-message form-message-success" role="status"><?= e((string) ($success['text'] ?? '')) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="form-message form-message-error" role="alert"><?= e($error) ?></div><?php endif; ?>

    <section class="artist-editor-section" aria-labelledby="artist-editor-title">
        <div class="section-heading"><div><p class="eyebrow"><?= (int) $formArtist['id'] > 0 ? 'Изменение каталога' : 'Новая запись' ?></p><h2 id="artist-editor-title"><?= (int) $formArtist['id'] > 0 ? 'Редактировать артиста' : 'Добавить артиста' ?></h2></div></div>
        <?php if (!$industries): ?>
            <div class="empty-state"><p>Список жанров недоступен. Проверьте таблицу industry в базе данных.</p></div>
        <?php else: ?>
            <form class="form-card artist-editor-form" method="post" enctype="multipart/form-data" action="moderator-artists.php<?= (int) $formArtist['id'] > 0 ? '?edit=' . (int) $formArtist['id'] : '' ?>">
                <?= csrfField() ?><input type="hidden" name="action" value="save_artist"><input type="hidden" name="artist_id" value="<?= (int) $formArtist['id'] ?>">
                <div class="artist-editor-grid">
                    <div class="artist-editor-fields">
                        <label for="artist-name">Имя исполнителя</label>
                        <input id="artist-name" name="companyname" type="text" maxlength="160" required value="<?= e((string) $formArtist['companyname']) ?>">
                        <label for="artist-about">Биография</label>
                        <textarea id="artist-about" name="about" rows="7" maxlength="10000" placeholder="Краткая биография и музыкальный путь артиста…"><?= e((string) $formArtist['about']) ?></textarea>
                        <label for="artist-genre">Жанр</label>
                        <select id="artist-genre" name="industry" required>
                            <option value="">Выберите жанр</option>
                            <?php foreach ($industries as $industry): ?><option value="<?= (int) $industry['id'] ?>"<?= (int) $formArtist['industry'] === (int) $industry['id'] ? ' selected' : '' ?>><?= e((string) $industry['description']) ?></option><?php endforeach; ?>
                        </select>
                        <label for="artist-description">Короткая подпись / стиль</label>
                        <input id="artist-description" name="description" type="text" maxlength="255" value="<?= e((string) $formArtist['description']) ?>">
                        <label for="artist-album">Релиз или альбом</label>
                        <textarea id="artist-album" name="album" rows="3" maxlength="5000" placeholder="Необязательно"><?= e((string) $formArtist['album']) ?></textarea>
                        <label for="artist-link">Ссылка на страницу артиста</label>
                        <input id="artist-link" name="link" type="url" maxlength="500" placeholder="https://…" value="<?= e((string) $formArtist['link']) ?>">
                    </div>
                    <div class="artist-editor-options">
                        <label for="artist-logo">Фото артиста</label>
                        <input id="artist-logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                        <small class="form-help">JPG, PNG или WebP, до 3 МБ. При редактировании загрузка необязательна.</small>
                        <?php $editorImage = artistImagePath((string) ($formArtist['logo'] ?? '')); ?>
                        <?php if ($editorImage !== ''): ?><img class="artist-editor-preview" src="<?= e($editorImage) ?>" alt="Текущее фото артиста" loading="lazy"><?php endif; ?>
                        <label class="artist-admin-check"><input type="checkbox" name="active" value="1"<?= (int) $formArtist['active'] === 1 ? ' checked' : '' ?>><span>Показывать в каталоге</span></label>
                        <label class="artist-admin-check"><input type="checkbox" name="featured" value="1"<?= (int) $formArtist['featured'] === 1 ? ' checked' : '' ?>><span>Включить в авторскую подборку (до 4)</span></label>
                        <small class="form-help">Эта подборка показывается гостям на главной.</small>
                    </div>
                </div>
                <div class="artist-editor-actions">
                    <button class="account-button form-submit" type="submit"><?= (int) $formArtist['id'] > 0 ? 'Сохранить артиста' : 'Добавить артиста' ?></button>
                    <?php if ((int) $formArtist['id'] > 0): ?><a class="account-link" href="moderator-artists.php">Отменить редактирование</a><?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="artist-admin-catalog" aria-labelledby="artist-list-title">
        <div class="section-heading"><div><p class="eyebrow">Каталог</p><h2 id="artist-list-title">Артисты проекта</h2></div><p><?= count($artists) ?> в списке</p></div>
        <form class="artist-admin-search" method="get" action="moderator-artists.php" role="search">
            <label class="sr-only" for="artist-admin-query">Найти артиста</label>
            <input id="artist-admin-query" name="q" type="search" maxlength="100" placeholder="Найти по имени или жанру…" value="<?= e($artistSearch) ?>">
            <button class="account-button" type="submit">Найти</button>
        </form>
        <?php if (!$artists): ?>
            <div class="empty-state"><p><?= $artistSearch !== '' ? 'По запросу ничего не найдено.' : 'В каталоге пока нет артистов.' ?></p></div>
        <?php else: ?>
            <div class="artist-admin-list">
                <?php foreach ($artists as $artist): ?>
                    <?php $artistImage = artistImagePath((string) ($artist['logo'] ?? '')); ?>
                    <article class="artist-admin-row">
                        <?php if ($artistImage !== '' && is_file(__DIR__ . '/' . $artistImage)): ?><img class="artist-admin-image" src="<?= e($artistImage) ?>" alt="" loading="lazy"><?php else: ?><div class="artist-admin-image artist-admin-placeholder" aria-hidden="true">SF</div><?php endif; ?>
                        <div class="artist-admin-info">
                            <p class="eyebrow"><?= e((string) ($artist['category_name'] ?? 'Артист')) ?><?= (int) $artist['active'] === 1 ? '' : ' · Скрыт' ?></p>
                            <h3><?= e((string) $artist['companyname']) ?></h3>
                        </div>
                        <div class="artist-admin-actions">
                            <form method="post" action="moderator-artists.php">
                                <?= csrfField() ?><input type="hidden" name="action" value="toggle_featured"><input type="hidden" name="artist_id" value="<?= (int) $artist['id'] ?>">
                                <button class="artist-admin-action" type="submit"<?= (int) $artist['active'] !== 1 && (int) $artist['featured'] !== 1 ? ' disabled' : '' ?>><?= (int) $artist['featured'] === 1 ? '★ В подборке' : '☆ В подборку' ?></button>
                            </form>
                            <a class="artist-admin-action" href="moderator-artists.php?edit=<?= (int) $artist['id'] ?>">Редактировать</a>
                            <form method="post" action="moderator-artists.php">
                                <?= csrfField() ?><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="artist_id" value="<?= (int) $artist['id'] ?>">
                                <button class="artist-admin-action" type="submit"><?= (int) $artist['active'] === 1 ? 'Скрыть' : 'Вернуть в каталог' ?></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
<?php renderFooter(); closeDB($db); ?>
