<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$industryId = filter_input(INPUT_GET, 'desc', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$category = null;
if ($db && $industryId) {
    $statement = $db->prepare('SELECT id, description FROM industry WHERE id = ? LIMIT 1');
    if ($statement) {
        $statement->bind_param('i', $industryId);
        if ($statement->execute()) {
            $result = $statement->get_result();
            $category = $result ? $result->fetch_assoc() : null;
        }
        $statement->close();
    }
}
$artists = $category ? fetchArtists($db, $industryId) : [];
if (!$db || !$category) {
    http_response_code($db ? 404 : 503);
}
renderHeader($category ? (string) $category['description'] : 'Категория не найдена', $db);
?>
<?php if (!$db): ?>
    <div class="empty-state"><h1>Каталог временно недоступен</h1><p>Проверьте подключение к MySQL.</p></div>
<?php elseif (!$category): ?>
    <div class="empty-state"><h1>Категория не найдена</h1><p>Проверьте ссылку или вернитесь к каталогу.</p><a class="button" href="music.php">Открыть музыку</a></div>
<?php else: ?>
    <section class="page-intro">
        <p class="eyebrow">Раздел каталога</p>
        <h1><?= e((string) $category['description']) ?></h1>
        <p>Исполнители из этой категории в SoundFusion.</p>
    </section>
    <?php if (!$artists): ?>
        <div class="empty-state"><p>В этой категории пока нет исполнителей.</p></div>
    <?php else: ?>
        <div class="artist-grid"><?php foreach ($artists as $artist) { renderArtistCard($artist); } ?></div>
    <?php endif; ?>
<?php endif; ?>
<?php renderFooter(); closeDB($db); ?>
