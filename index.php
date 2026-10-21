<?php
require_once __DIR__ . '/function.php';
$db = connectDB();
$currentUser = getCurrentUser($db);
$featuredArtists = $currentUser
    ? getUserFavoriteArtists($db, (int) $currentUser['id'])
    : fetchFeaturedArtists($db);
renderHeader('Главная', $db);
?>
<section class="home-hero" aria-labelledby="home-title">
    <div class="home-hero-inner">
        <figure class="home-hero-portrait">
            <img src="theweeknd-cutout.png" alt="The Weeknd на прозрачном фоне" fetchpriority="high">
            <figcaption><span>The Weeknd</span><span>Избранный артист</span></figcaption>
        </figure>
        <div class="home-hero-copy">
            <p class="eyebrow">SoundFusion · музыкальный каталог</p>
            <h1 id="home-title">Слушай внимательнее.</h1>
            <p class="home-hero-lead">Артисты, альбомы и звук, который формирует музыку вокруг нас.</p>
            <p class="home-hero-note">Истории любимых исполнителей и новые поводы нажать на play — в одном месте.</p>
        </div>
    </div>
</section>

<section id="favorites" class="featured-section" aria-labelledby="featured-title">
    <div class="section-heading">
        <div><p class="eyebrow"><?= $currentUser ? 'Ваш личный выбор' : 'Выбор автора' ?></p><h2 id="featured-title">Избранные артисты</h2></div>
        <p><?= $currentUser ? count($featuredArtists) . ' в вашем списке' : count($featuredArtists) . ' из 4 мест в избранном' ?></p>
    </div>
    <?php if (!$db): ?>
        <div class="empty-state"><p>Избранное временно недоступно: проверьте подключение к базе данных.</p></div>
    <?php elseif (!$featuredArtists): ?>
        <?php if ($currentUser): ?>
            <div class="empty-state"><p>Вы пока не добавили любимых артистов.</p><p><a class="text-link" href="profile.php?tab=favorites">Выбрать артистов в профиле ↗</a></p></div>
        <?php else: ?>
            <div class="empty-state"><p>Пока нет избранных артистов. Отметьте до четырёх записей в базе данных как избранные.</p><p>Все исполнители по-прежнему доступны на странице «Музыка».</p></div>
        <?php endif; ?>
    <?php else: ?>
        <div class="artist-grid featured-grid"><?php foreach ($featuredArtists as $artist) { renderArtistCard($artist); } ?></div>
    <?php endif; ?>
</section>

<section class="home-story" aria-labelledby="home-story-title">
    <figure class="home-story-image">
        <img src="kendrick.jpg" alt="Портрет Kendrick Lamar в чёрно-белой обработке" loading="lazy">
        <figcaption>Хип-хоп · R&amp;B · личный взгляд</figcaption>
    </figure>
    <div class="home-story-copy">
        <p class="eyebrow">Музыка и авторский выбор</p>
        <h2 id="home-story-title">Сцена меняется. Вкус остаётся личным.</h2>
        <p class="home-story-lead">Хип-хоп, R&amp;B и рэп постоянно меняются: артисты переосмысляют звучание, соединяют жанры и задают свой темп.</p>
        <p>SoundFusion собирает исполнителей, релизы и истории, которые помогают услышать эту музыку внимательнее. Это мой личный музыкальный маршрут — любимые артисты, собственные открытия и отправная точка для новых имён.</p>
        <a class="text-link home-story-link" href="music.php">Исследовать каталог <span aria-hidden="true">↗</span></a>
    </div>
</section>
<?php renderFooter(); closeDB($db); ?>
