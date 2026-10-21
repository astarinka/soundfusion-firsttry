<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$searchQuery = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
$artists = fetchArtists($db, null, $searchQuery, true);
renderHeader('Музыка', $db);
?>
<section class="page-intro">
    <p class="eyebrow">Каталог</p>
    <h1>Найдите артиста и его музыку.</h1>
    <p>Откройте карточку исполнителя или сразу найдите его музыку в Spotify и SoundCloud. Ссылки ведут на поиск по имени артиста.</p>
</section>
<div class="music-search-wrap">
    <form class="music-search" role="search" method="get" action="music.php">
        <label class="sr-only" for="artist-search">Поиск исполнителя</label>
        <span class="music-search-icon" aria-hidden="true">⌕</span>
        <input id="artist-search" name="q" type="search" maxlength="100" placeholder="Найти артиста или жанр…" value="<?= e($searchQuery) ?>" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="artist-search-results" autocomplete="off">
        <button type="submit">Найти</button>
    </form>
    <div class="music-search-popover" id="artist-search-popover" hidden>
        <div class="music-search-results" id="artist-search-results" role="listbox" aria-label="Подходящие исполнители"></div>
        <p class="music-search-message" id="artist-search-message" role="status" hidden></p>
    </div>
    <p class="sr-only" id="artist-search-status" role="status" aria-live="polite"></p>
</div>
<?php if (!$db): ?>
    <div class="empty-state"><p>Каталог временно недоступен: проверьте подключение к MySQL и настройки в <code>config.example.php</code>.</p></div>
<?php elseif (!$artists && $searchQuery !== ''): ?>
    <div class="empty-state"><p>По запросу «<?= e($searchQuery) ?>» ничего не нашлось.</p><p><a class="text-link" href="music.php">Показать весь каталог ↗</a></p></div>
<?php elseif (!$artists): ?>
    <div class="empty-state"><p>В базе пока нет музыкального контента.</p></div>
<?php else: ?>
    <?php if ($searchQuery !== ''): ?><p class="music-search-count">Найдено: <?= count($artists) ?></p><?php endif; ?>
    <div class="artist-grid"><?php foreach ($artists as $artist) { renderArtistCard($artist); } ?></div>
<?php endif; ?>
<script src="music-search.js" defer></script>
<?php renderFooter(); closeDB($db); ?>
