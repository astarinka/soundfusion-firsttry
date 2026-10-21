<?php
    //$companyid = htmlspecialchars($_GET['industry'], ENT_QUOTES, 'UTF-8');
    $companyind = htmlspecialchars($_GET['desc'], ENT_QUOTES, 'UTF-8');
    require_once('function.php');
    
    echo '<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Программа лояльности: Бонусный клуб "Все Тип-Топ"</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>';

    echo '<header><a href="index.php">
            <img src="SoundFusion white.png" class="logopos" /></a>
            <div><h1 class="center-text" style="color: azure;"><strong>'.description($companyind).'</strong></h1>
        </div>
        <div style="color: azure;">
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
    
    $db = connectDB();
    $sql = "SELECT c.* FROM content AS c LEFT JOIN industry AS i ON i.id = c.industry WHERE i.id = ?";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('i', $companyind);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo '<div class="container">';
                while ($row = $result->fetch_assoc()) {
                    $companyId = htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8');
                    $companyName = htmlspecialchars($row["companyname"], ENT_QUOTES, 'UTF-8');
                    $companyinfo = htmlspecialchars($row["about"], ENT_QUOTES, 'UTF-8');
                    $companyLogo = htmlspecialchars($row["logo"], ENT_QUOTES, 'UTF-8');
                    $companyind = htmlspecialchars($row["industry"], ENT_QUOTES, 'UTF-8');
                    $companyStatus = htmlspecialchars($row["active"], ENT_QUOTES, 'UTF-8');
                    $companylink = htmlspecialchars($row["link"], ENT_QUOTES, 'UTF-8');
                    $companydesc = htmlspecialchars($row["description"], ENT_QUOTES, 'UTF-8');  
                    echo '<div class="main-div"><img src='.$companyLogo.' />
                    <div class="card-content">
                        <h2>' . $companyName . '</h2>
                        <a href="f.php?companyid='.$companyId.'">Подробнее</a>
                    </div>
                    </div>';                    
                }
                echo '</div>';
            } 
        } 
    } 
    closeDB();
    echo '<div style="height: 100%; margin: 0; padding: 0;"></div>';
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
