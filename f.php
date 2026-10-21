<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$artistId = filter_input(INPUT_GET, 'companyid', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$artist = null;
if ($db && $artistId) {
    $statement = $db->prepare('SELECT c.*, i.description AS category_name FROM content c LEFT JOIN industry i ON i.id = c.industry WHERE c.id = ? AND c.active = 1 LIMIT 1');
    if ($statement) {
        $statement->bind_param('i', $artistId);
        if ($statement->execute()) {
            $result = $statement->get_result();
            $artist = $result ? $result->fetch_assoc() : null;
        }
        $statement->close();
    }
}
if (!$artist) {
    http_response_code($db ? 404 : 503);
}
renderHeader($artist ? (string) $artist['companyname'] : 'Исполнитель не найден', $db);
?>
<?php if (!$db): ?>
    <div class="empty-state"><h1>Каталог временно недоступен</h1><p>Проверьте, что MySQL запущен и база <code>db_web</code> импортирована.</p></div>
<?php elseif (!$artist): ?>
    <div class="empty-state"><p class="eyebrow">404 · Каталог</p><h1>Исполнитель не найден</h1><p>Ссылка могла устареть или такой записи нет в каталоге.</p><a class="button" href="music.php">Вернуться к музыке</a></div>
<?php else: ?>
    <?php
    $image = artistImagePath((string) ($artist['logo'] ?? ''));
    $externalUrl = trim((string) ($artist['link'] ?? ''));
    $externalUrlParts = $externalUrl !== '' ? parse_url($externalUrl) : false;
    if (!filter_var($externalUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($externalUrlParts['scheme'] ?? '')), ['http', 'https'], true)) {
        $externalUrl = '';
    }
    ?>
    <article class="artist-profile">
      <section class="artist-detail">
        <figure class="artist-detail-art">
            <?php if ($image !== ''): ?>
                <img src="<?= e($image) ?>" alt="Портрет <?= e((string) $artist['companyname']) ?>">
            <?php else: ?>
                <div class="image-fallback">SF</div>
            <?php endif; ?>
            <figcaption><?= e((string) ($artist['category_name'] ?: 'Музыкальный каталог')) ?></figcaption>
        </figure>
        <div class="artist-detail-copy">
            <p class="eyebrow">SoundFusion · артист</p>
            <h1><?= e((string) $artist['companyname']) ?></h1>
            <p class="artist-detail-summary"><?= !empty($artist['description']) ? e((string) $artist['description']) : 'Знакомьтесь с биографией и релизами артиста в каталоге SoundFusion.' ?></p>
            <?php if (!empty($artist['category_name'])): ?><a class="detail-category" href="desc.php?desc=<?= (int) $artist['industry'] ?>">Жанр · <?= e((string) $artist['category_name']) ?></a><?php endif; ?>
            <div class="artist-detail-actions">
                <?php if ($externalUrl !== ''): ?><a class="button button-primary" href="<?= e($externalUrl) ?>" target="_blank" rel="noopener noreferrer">Официальная страница ↗</a><?php endif; ?>
                <?php renderArtistChatButton($artist); ?>
                <?php renderArtistPlatforms($artist); ?>
            </div>
        </div>
      </section>

      <section class="artist-detail-more" aria-label="Подробнее об артисте">
        <div class="artist-biography">
            <p class="eyebrow">Биография</p>
            <h2>История и звучание</h2>
            <?php if (!empty($artist['about'])): ?><p><?= nl2br(e((string) $artist['about'])) ?></p><?php else: ?><p>Биография этого исполнителя пока готовится. Загляните позже — каталог будет пополняться.</p><?php endif; ?>
        </div>
        <aside class="artist-release-panel">
            <p class="eyebrow">В фокусе</p>
            <h2><?= !empty($artist['album']) ? 'Релизы и музыка' : 'Звучание артиста' ?></h2>
            <p><?= !empty($artist['album']) ? nl2br(e((string) $artist['album'])) : (!empty($artist['description']) ? e((string) $artist['description']) : 'Подборка релизов появится здесь, когда будет добавлена в каталог.') ?></p>
            <a class="text-link" href="music.php">← Все исполнители</a>
        </aside>
      </section>
    </article>
<?php endif; ?>
<?php renderFooter(); closeDB($db); ?>
