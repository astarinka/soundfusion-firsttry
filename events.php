<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
renderHeader('События', $db);
?>
<section class="page-intro">
    <p class="eyebrow">События</p>
    <h1>Музыка звучит и за пределами наушников.</h1>
    <p>Здесь будет афиша концертов и музыкальных событий.</p>
</section>
<section class="notice-card"><h2>Афиша пока не подключена</h2><p>В текущей версии проекта нет источника актуальных событий, поэтому здесь не публикуются непроверенные даты и места. Пока можно открыть каталог артистов.</p><a class="button" href="music.php">Исследовать артистов</a></section>
<?php renderFooter(); closeDB($db); ?>
