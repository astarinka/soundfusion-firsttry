<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function connectDB(): ?mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $configPath = null;
    foreach ([__DIR__ . '/local.config.php', __DIR__ . '/config.local.php'] as $candidate) {
        if (is_file($candidate)) {
            $configPath = $candidate;
            break;
        }
    }

    if ($configPath === null) {
        error_log('SoundFusion database configuration is missing. Copy config.example.php to local.config.php.');
        return null;
    }

    try {
        $settings = require $configPath;
    } catch (Throwable $error) {
        error_log('SoundFusion database configuration could not be loaded.');
        return null;
    }

    if (!is_array($settings)) {
        error_log('SoundFusion database configuration must return an array.');
        return null;
    }

    foreach (['host', 'database', 'username', 'password'] as $key) {
        if (!array_key_exists($key, $settings) || !is_string($settings[$key])) {
            error_log('SoundFusion database configuration is missing a valid ' . $key . ' value.');
            return null;
        }
    }
    if ($settings['host'] === '' || $settings['database'] === '' || $settings['username'] === '') {
        error_log('SoundFusion database host, database, and username must not be empty.');
        return null;
    }

    $port = filter_var($settings['port'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if ($port === false) {
        error_log('SoundFusion database configuration has an invalid port.');
        return null;
    }

    try {
        $connection = @new mysqli(
            (string) $settings['host'],
            (string) $settings['username'],
            (string) $settings['password'],
            (string) $settings['database'],
            $port
        );
        if ($connection->connect_errno) {
            error_log('SoundFusion database connection failed. Check the local settings and server log.');
            return null;
        }
        $connection->set_charset('utf8mb4');
        return $connection;
    } catch (Throwable $error) {
        error_log('SoundFusion database connection failed. Check the local settings and server log.');
        return null;
    }
}

function closeDB(?mysqli $connection): void
{
    if ($connection instanceof mysqli) {
        $connection->close();
    }
}

function getNavigation(?mysqli $connection): array
{
    $fallback = [
        ['name' => 'Главная', 'link' => 'index.php'],
        ['name' => 'Музыка', 'link' => 'music.php'],
        ['name' => 'Новости', 'link' => 'news.php'],
        ['name' => 'О проекте', 'link' => 'about.php'],
    ];

    if (!$connection) {
        return $fallback;
    }
    $result = $connection->query('SELECT name, link FROM main ORDER BY id');
    if (!$result || $result->num_rows === 0) {
        return $fallback;
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function renderHeader(string $title, ?mysqli $connection): void
{
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $navigation = getNavigation($connection);
    $currentUser = getCurrentUser($connection);
    $allowedPages = ['music.php', 'news.php', 'about.php'];
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#080d18">
        <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
        <title><?= e($title) ?> — SoundFusion</title>
        <link rel="icon" href="favicon.ico">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="SoundFusion — главная">
            <img src="SoundFusion white.png" alt="SoundFusion">
        </a>
        <nav class="site-nav" aria-label="Основная навигация">
            <?php foreach ($navigation as $item): ?>
                <?php $link = basename(str_replace('\\', '/', (string) ($item['link'] ?? 'index.php'))); ?>
                <?php if (!in_array($link, $allowedPages, true)) { continue; } ?>
                <a href="<?= e($link) ?>"<?= $link === $currentPage ? ' aria-current="page"' : '' ?>><?= e((string) ($item['name'] ?? '')) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="account-actions">
            <?php if ($currentUser): ?>
                <?php if (($currentUser['role'] ?? 'user') === 'moderator'): ?><a class="account-link" href="moderator.php">Модерация</a><?php endif; ?>
                <form action="logout.php" method="post">
                    <?= csrfField() ?>
                    <button class="account-button" type="submit">Выйти</button>
                </form>
                <?php
                $avatarPath = (string) ($currentUser['avatar_path'] ?? '');
                $avatarFile = __DIR__ . '/' . $avatarPath;
                $hasAvatar = preg_match('~^uploads/avatars/[A-Za-z0-9._-]+$~D', $avatarPath) === 1 && is_file($avatarFile);
                $avatarInitial = mb_strtoupper(mb_substr((string) ($currentUser['display_name'] ?? 'П'), 0, 1));
                ?>
                <a class="account-avatar" href="profile.php" aria-label="Профиль: <?= e((string) ($currentUser['display_name'] ?? 'пользователь')) ?>">
                    <?php if ($hasAvatar): ?>
                        <img src="<?= e($avatarPath) ?>" alt="">
                    <?php else: ?>
                        <span aria-hidden="true"><?= e($avatarInitial) ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a class="account-button" href="login.php">Войти</a>
            <?php endif; ?>
        </div>
    </header>
    <main class="page-shell">
    <?php
}

function renderFooter(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <div class="footer-inner">
            <a class="footer-brand" href="index.php">SoundFusion<span>Музыка, которую хочется переслушать.</span></a>
            <nav class="footer-links" aria-label="Ссылки в подвале">
                <a href="music.php">Музыка</a>
                <a href="news.php">Новости</a>
                <a href="about.php">О проекте</a>
            </nav>
            <div class="footer-socials">
                <a href="https://t.me/astarinokk" target="_blank" rel="noopener noreferrer">Telegram ↗</a>
                <a href="https://www.instagram.com/astarinokk/" target="_blank" rel="noopener noreferrer">Instagram ↗</a>
            </div>
            <p class="footer-note">Учебный проект о музыке. Имена и изображения принадлежат их правообладателям.</p>
            <p class="footer-copy">© <?= date('Y') ?> SoundFusion</p>
        </div>
    </footer>
    <div class="artist-chat" id="artist-chat" data-api="artist-chat-api.php">
        <section class="artist-chat-panel" id="artist-chat-panel" aria-label="Чаты по артистам" hidden>
            <header class="artist-chat-header">
                <div><p class="eyebrow">SoundFusion · сообщество</p><h2>Чаты артистов</h2></div>
                <button class="artist-chat-close" type="button" data-chat-close aria-label="Свернуть чаты">×</button>
            </header>
            <div class="artist-chat-content">
                <nav class="artist-chat-rooms" id="artist-chat-rooms" aria-label="Мои чаты"></nav>
                <section class="artist-chat-conversation" id="artist-chat-conversation" aria-label="Переписка">
                    <div class="artist-chat-empty" id="artist-chat-empty">Вступите в чат артиста из его карточки — он появится здесь.</div>
                    <div class="artist-chat-room-title" id="artist-chat-room-title" hidden>
                        <span id="artist-chat-room-name"></span>
                        <span class="artist-chat-room-tools">
                            <button class="artist-chat-members-toggle" id="artist-chat-members-toggle" type="button" aria-expanded="false">Участники · <span id="artist-chat-member-count">0</span></button>
                            <button class="artist-chat-private-back" id="artist-chat-private-back" type="button" hidden>← Групповой чат</button>
                        </span>
                    </div>
                    <p class="artist-chat-private-note" id="artist-chat-private-note" hidden>Личные сообщения видны только вам и собеседнику.</p>
                    <div class="artist-chat-members" id="artist-chat-members" hidden></div>
                    <div class="artist-chat-messages" id="artist-chat-messages" aria-live="polite" aria-relevant="additions text"></div>
                    <form class="artist-chat-compose" id="artist-chat-compose" hidden>
                        <label class="sr-only" for="artist-chat-input">Сообщение</label>
                        <textarea id="artist-chat-input" name="message" maxlength="1000" rows="1" placeholder="Напишите сообщение…" required></textarea>
                        <button type="submit" aria-label="Отправить сообщение">Отправить</button>
                    </form>
                </section>
            </div>
            <p class="artist-chat-status" id="artist-chat-status" role="status" aria-live="polite"></p>
        </section>
        <button class="artist-chat-launcher" type="button" data-chat-open aria-expanded="false" aria-controls="artist-chat-panel">
            <span class="artist-chat-launcher-icon" aria-hidden="true">✦</span><span>Чаты</span><span class="artist-chat-count" id="artist-chat-count" hidden>0</span>
        </button>
    </div>
    <script src="artist-chat.js" defer></script>
    </body>
    </html>
    <?php
}

function fetchArtists(?mysqli $connection, ?int $industryId = null, string $search = '', bool $featuredFirst = false): array
{
    if (!$connection) {
        return [];
    }

    $search = trim(mb_substr($search, 0, 100));
    if ($search !== '' && $industryId !== null) {
        $orderBy = $featuredFirst ? 'c.featured DESC, c.companyname' : 'c.companyname';
        $statement = $connection->prepare('SELECT c.*, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry WHERE c.industry = ? AND c.active = 1 AND (c.companyname LIKE ? OR c.about LIKE ? OR i.description LIKE ?) ORDER BY ' . $orderBy);
        if (!$statement) {
            return [];
        }
        $pattern = '%' . $search . '%';
        $statement->bind_param('isss', $industryId, $pattern, $pattern, $pattern);
        if (!$statement->execute()) {
            $statement->close();
            return [];
        }
        $result = $statement->get_result();
        $artists = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $statement->close();
        return $artists;
    }

    if ($search !== '') {
        $orderBy = $featuredFirst ? 'c.featured DESC, c.companyname' : 'c.companyname';
        $statement = $connection->prepare('SELECT c.*, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry WHERE c.active = 1 AND (c.companyname LIKE ? OR c.about LIKE ? OR i.description LIKE ?) ORDER BY ' . $orderBy);
        if (!$statement) {
            return [];
        }
        $pattern = '%' . $search . '%';
        $statement->bind_param('sss', $pattern, $pattern, $pattern);
        if (!$statement->execute()) {
            $statement->close();
            return [];
        }
        $result = $statement->get_result();
        $artists = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $statement->close();
        return $artists;
    }

    if ($industryId !== null) {
        $statement = $connection->prepare('SELECT c.*, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry WHERE c.industry = ? AND c.active = 1 ORDER BY c.id');
        if (!$statement) {
            return [];
        }
        $statement->bind_param('i', $industryId);
        if (!$statement->execute()) {
            $statement->close();
            return [];
        }
        $result = $statement->get_result();
        $artists = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $statement->close();
        return $artists;
    }

    $orderBy = $featuredFirst ? 'c.featured DESC, c.id' : 'c.id';
    $result = $connection->query('SELECT c.*, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry WHERE c.active = 1 ORDER BY ' . $orderBy);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function fetchFeaturedArtists(?mysqli $connection): array
{
    if (!$connection) {
        return [];
    }

    $result = $connection->query('SELECT c.*, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry WHERE c.active = 1 AND c.featured = 1 ORDER BY c.id LIMIT 4');
    if (!$result) {
        // Older local databases can still preview the page until the featured migration is applied.
        return array_slice(fetchArtists($connection), 0, 4);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserFavoriteArtists(?mysqli $connection, int $userId): array
{
    if (!$connection || $userId < 1) {
        return [];
    }
    $statement = $connection->prepare('SELECT c.id, c.companyname, c.logo, c.about, c.description, c.album, i.description AS category_name FROM user_artist_favorites f JOIN content c ON c.id = f.artist_id LEFT JOIN industry i ON i.id = c.industry WHERE f.user_id = ? AND c.active = 1 ORDER BY c.companyname');
    if (!$statement) {
        return [];
    }
    $statement->bind_param('i', $userId);
    if (!$statement->execute()) {
        $statement->close();
        return [];
    }
    $result = $statement->get_result();
    $artists = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();
    return $artists;
}

function newsMatchesFavoriteArtists(array $item, array $favorites): array
{
    $text = trim((string) ($item['title'] ?? '') . ' ' . (string) ($item['description'] ?? ''));
    $matches = [];
    foreach ($favorites as $artist) {
        $name = trim((string) ($artist['companyname'] ?? ''));
        $pattern = $name === '' ? null : '/(?<![\p{L}\p{N}])' . preg_quote($name, '/') . '(?![\p{L}\p{N}])/iu';
        if ($pattern !== null && preg_match($pattern, $text) === 1) {
            $matches[] = $name;
        }
    }
    return $matches;
}

function prioritizeNewsForFavorites(array $items, array $favorites): array
{
    if (!$favorites) {
        return $items;
    }
    foreach ($items as $index => $item) {
        $items[$index]['favorite_matches'] = newsMatchesFavoriteArtists($item, $favorites);
    }
    usort($items, static function (array $left, array $right): int {
        $leftMatches = count($left['favorite_matches'] ?? []);
        $rightMatches = count($right['favorite_matches'] ?? []);
        if ($leftMatches !== $rightMatches) {
            return $rightMatches <=> $leftMatches;
        }
        return (int) ($right['published_at'] ?? 0) <=> (int) ($left['published_at'] ?? 0);
    });
    return $items;
}

function artistImagePath(?string $value): string
{
    $path = str_replace('\\', '/', trim((string) $value));
    if ($path === '') {
        return '';
    }
    if (preg_match('~^uploads/artists/[A-Za-z0-9._-]+$~D', $path) === 1) {
        return is_file(__DIR__ . '/' . $path) ? $path : '';
    }
    $fileName = basename($path);
    return is_file(__DIR__ . '/' . $fileName) ? $fileName : '';
}

function artistBiographyPreview(?string $biography, int $limit = 180): string
{
    $text = preg_replace('/\s+/u', ' ', trim((string) $biography)) ?? '';
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, max(1, $limit - 1))) . '…';
}

function renderArtistCard(array $artist): void
{
    $image = artistImagePath((string) ($artist['logo'] ?? ''));
    ?>
    <article class="artist-card">
        <a class="artist-art" href="f.php?companyid=<?= (int) ($artist['id'] ?? 0) ?>" aria-label="Открыть: <?= e((string) ($artist['companyname'] ?? 'Исполнитель')) ?>">
            <?php if ($image !== '' && is_file(__DIR__ . '/' . $image)): ?>
                <img src="<?= e($image) ?>" alt="<?= e((string) ($artist['companyname'] ?? '')) ?>" loading="lazy">
            <?php else: ?>
                <span class="image-fallback">SF</span>
            <?php endif; ?>
        </a>
        <div class="artist-card-body">
            <div>
                <p class="eyebrow"><?= e((string) ($artist['category_name'] ?? 'Артист')) ?></p>
                <h2><?= e((string) ($artist['companyname'] ?? '')) ?></h2>
                <?php if (!empty($artist['about'])): ?><p class="artist-bio-preview"><?= e(artistBiographyPreview((string) $artist['about'])) ?></p><?php endif; ?>
                <?php if (!empty($artist['album'])): ?><p class="artist-release"><?= e((string) $artist['album']) ?></p><?php endif; ?>
            </div>
            <div class="artist-card-actions">
                <a class="text-link" href="f.php?companyid=<?= (int) ($artist['id'] ?? 0) ?>">Карточка артиста <span aria-hidden="true">↗</span></a>
                <?php renderArtistChatButton($artist); ?>
                <?php renderArtistPlatforms($artist); ?>
            </div>
        </div>
    </article>
    <?php
}

function renderArtistChatButton(array $artist): void
{
    $artistId = (int) ($artist['id'] ?? 0);
    $artistName = (string) ($artist['companyname'] ?? 'артиста');
    if ($artistId < 1) {
        return;
    }
    if ((int) ($_SESSION['user_id'] ?? 0) < 1) {
        echo '<a class="artist-chat-join artist-chat-login" href="login.php">Войдите, чтобы вступить в чат</a>';
        return;
    }
    echo '<button class="artist-chat-join" type="button" data-chat-join="' . $artistId . '" data-chat-name="' . e($artistName) . '">Войти в чат</button>';
}

function renderArtistPlatforms(array $artist): void
{
    $name = trim((string) ($artist['companyname'] ?? ''));
    if ($name === '') {
        return;
    }
    $spotifyUrl = 'https://open.spotify.com/search/' . rawurlencode($name);
    $soundcloudUrl = 'https://soundcloud.com/search?q=' . rawurlencode($name);
    ?>
    <nav class="artist-platforms" aria-label="Найти музыку: <?= e($name) ?>">
        <a href="<?= e($spotifyUrl) ?>" target="_blank" rel="noopener noreferrer">Spotify ↗</a>
        <a href="<?= e($soundcloudUrl) ?>" target="_blank" rel="noopener noreferrer">SoundCloud ↗</a>
    </nav>
    <?php
}
