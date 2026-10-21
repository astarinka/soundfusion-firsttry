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
                echo '<a href="' . $userlink . '"><button class="' . $activeClass . '">' . $username . '</button></a>';
            }
        } else {
            echo "<p>Нет данных в таблице menu.</p>";
        }
    } else {
        echo "<p>Ошибка запроса: " . $db->error . "</p>";
    }
}

echo '</nav><nav class="center-text"></header>';
echo '<h1 style="font-size: 72px; font-weight: 600; text-align: center; margin: 0; margin-top: 40px;">Last Albums</h1>';

$sql = "SELECT * FROM db_web.content";
if ($stmt = $db->prepare($sql)) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo '<div class="container">';
            while ($row = $result->fetch_assoc()) {
                $companyName = htmlspecialchars($row["companyname"], ENT_QUOTES, 'UTF-8');
                $companylink = htmlspecialchars($row["link"], ENT_QUOTES, 'UTF-8');
                $companyid = htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8');
                $companylogo = htmlspecialchars($row["logo"], ENT_QUOTES, 'UTF-8');
                $companyind = htmlspecialchars($row["industry"], ENT_QUOTES, 'UTF-8');
                $album = $row["album"];
                echo '<div class="main-div" style="display: flex; flex-direction: column; align-items: center; justify-content: space-between; padding: 10px; background-color: #1e1e1e; border-radius: 12px; width: 550px; height: 450px; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    <div style="width: 100%; height: 90%; border-radius: 10px; overflow: hidden;">
                        '.$album.'
                    </div>
                    <div style="color: white; font-size: 18px; font-family: Gilroy, sans-serif; font-weight: 600; text-align: center;">
                        '.$companyName.'
                    </div>
                </div>';
            }      
            echo '</div>';
        }
    }
}

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
    <div class="footer-social" style="font-size: 20px; display: inline-flex; margin-left: 50%; font-weight: 600;">
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
        <p>Представленная на данном сайте информация носит исключительно справочный и образовательный характер. Проект создан в рамках изучения веб-разработки и посвящён музыкальной тематике, включая информацию об исполнителях, являющихся объектом личного интереса автора. Все права на упомянутые материалы принадлежат их законным правообладателям.</p>
    </div>
</footer>
</body></html>';
?>
