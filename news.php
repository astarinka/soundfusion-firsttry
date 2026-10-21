<?php
    require_once('./function.php');
    echo '<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SoundFusion - my music</title>
        <link rel="stylesheet" href="style.css">
    </head>';
    echo '<body>
        <header><a href="index.php">
            <img src="SoundFusion white.png" class="logopos" /></a>
        <nav class="menu-button">';
    
    $db = connectDB();
    
    $sql = "SELECT * FROM main ORDER BY id";
    if ($stmt = $db->prepare($sql)) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $userid = htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8');
                    $username = htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8');
                    $userlink = htmlspecialchars($row["link"], ENT_QUOTES, 'UTF-8');
                    $activeClass = isActive(basename($userlink)) ? 'active' : '';
                    echo '<a href="' . $userlink . '"><button>' . $username . '</button></a>';
                }
            } else {
                echo "<p>Нет данных в таблице menu.</p>";
            }
        } else {
            echo "<p>Ошибка запроса: " . $db->error . "</p>";
        }
    }
    echo '</nav><nav class = "center-text"></header>';
    echo '<div style="height: 100%; margin: 0; padding: 0;"></div>';
    closeDB();
    echo '
    <footer>
    <div class="footer-container">

        <div class="footer-links">
            <a href="music.php">Музыка</a>
            <a href="events.php">События</a>
            <a href="news.php">Новости</a>
            <a href="about.php">О нас</a>
        </div>

    </div>
        <div class="footer-social" style="font-size: 20px;  display: inline-flex; margin-left: 50%; font-weight: 600;">
        Контакты
        </div>
        <div class="footer-socials">
            <a href="https://t.me/astarinokk" class="social-icon">Telegram</a>
            <a href="https://www.instagram.com/astarinokk/" class="social-icon">Instagram</a>
            <a href="#" class="social-icon">TG channel</a>
        </div>
    <div class="footer-info">
            <p>&copy; 2025 SoundFusion. Все права защищены.</p>
        </div>
        <div class="number">
            <p>+7 (900) 000-00-00
            </p>
        </div>
        <div class="footer-info2">
            <p>Представленная на данном сайте информация носит исключительно справочный и образовательный характер. Проект создан в рамках изучения веб-разработки и посвящён музыкальной тематике, включая информацию об исполнителях, являющихся объектом личного интереса автора. Все права на упомянутые материалы принадлежат их законным правообладателям.
            </p>
        </div>
</footer>    
    </nav></body></html>';
?>

