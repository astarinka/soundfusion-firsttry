<?php
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/news-feed.php';
$db = connectDB();
$newsItems = fetchMusicNews();
$currentUser = getCurrentUser($db);
$favoriteArtists = $currentUser ? getUserFavoriteArtists($db, (int) $currentUser['id']) : [];
$newsItems = prioritizeNewsForFavorites($newsItems, $favoriteArtists);
$feedErrors = $GLOBALS['soundfusionNewsFeedErrors'] ?? [];
$russianNews = array_values(array_filter($newsItems, static fn (array $item): bool => ($item['language'] ?? '') === 'ru'));
$englishNews = array_values(array_filter($newsItems, static fn (array $item): bool => ($item['language'] ?? '') === 'en'));
$newsSections = [['title' => 'Новости по-русски', 'label' => 'Русская лента', 'items' => $russianNews, 'empty' => 'Русская лента временно недоступна.', 'url' => 'https://daily.afisha.ru/music/'], ['title' => 'News in English', 'label' => 'English feed', 'items' => $englishNews, 'empty' => 'The English feed is temporarily unavailable.', 'url' => 'https://pitchfork.com/news/']];
usort($newsSections, static fn (array $left, array $right): int => (count($right['items']) > 0) <=> (count($left['items']) > 0));
renderHeader('Новости', $db);
?>
<section class="page-intro news-intro">
    <p class="eyebrow">Музыкальная пресса · RU / EN</p>
    <h1>Новости на двух языках.</h1>
    <p>Свежие музыкальные публикации из русскоязычной и англоязычной прессы. Заголовки ведут на сайты изданий.</p>
</section>

<?php foreach ($newsSections as $section): ?>
    <section class="news-language-section" aria-label="<?= e($section['title']) ?>">
        <div class="section-heading news-section-heading">
            <div><p class="eyebrow"><?= e($section['label']) ?></p><h2><?= e($section['title']) ?></h2></div>
            <p><?= count($section['items']) ?> публикаций</p>
        </div>
        <?php if ($section['items']): ?>
            <div class="news-grid">
                <?php foreach ($section['items'] as $item): ?>
                    <article class="news-card<?= !empty($item['favorite_matches']) ? ' news-card-favorite' : '' ?>">
                        <?php if (!empty($item['favorite_matches'])): ?><p class="favorite-news-label"><?= count($item['favorite_matches']) === 1 ? 'Your favorite singer' : 'Your favorite artists' ?> — <?= e(implode(', ', $item['favorite_matches'])) ?></p><?php endif; ?>
                        <p class="eyebrow"><?= e($item['source']) ?><?php if (!empty($item['published_at'])): ?> · <?= e(date('d.m.Y', (int) $item['published_at'])) ?><?php endif; ?></p>
                        <h3><a href="<?= e($item['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($item['title']) ?></a></h3>
                        <?php if ($item['description'] !== ''): ?><p><?= e($item['description']) ?></p><?php endif; ?>
                        <a class="text-link" href="<?= e($item['url']) ?>" target="_blank" rel="noopener noreferrer">Читать источник ↗</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><p><?= e($section['empty']) ?></p><a class="text-link" href="<?= e($section['url']) ?>" target="_blank" rel="noopener noreferrer">Открыть сайт издания ↗</a></div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
<?php if ($currentUser && !$favoriteArtists): ?><p class="news-personalization-note">Добавьте артистов в разделе <a class="text-link" href="profile.php?tab=favorites">«Любимые артисты»</a>, чтобы поднимать новости о них выше.</p><?php endif; ?>
<?php if ($feedErrors): ?>
    <details class="feed-diagnostics">
        <summary>Почему часть новостей не загрузилась?</summary>
        <ul><?php foreach (array_unique($feedErrors) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </details>
<?php endif; ?>
<?php renderFooter(); closeDB($db); ?>
