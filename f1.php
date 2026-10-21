<?php
    $companyid = htmlspecialchars($_GET['industry'], ENT_QUOTES, 'UTF-8');
    require_once('function.php');

    echo '<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Программа лояльности: Бонусный клуб "Все Тип-Топ"</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>';

    echo '<div class="block0">
        <div><h1 style="color: azure;" class="center-text"><strong>'.htmlspecialchars(getPageTitle(basename($_SERVER['SCRIPT_NAME'])), ENT_QUOTES, 'UTF-8').'</strong></h1>
        </div>
        <div style="color: azure;" class="center-text">';

    $db = connectDB();
    
    $sql = "SELECT c.*, i.description FROM content AS c LEFT JOIN industry AS i ON c.industry = i.id WHERE c.industry = ?";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('i', $companyid);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $i = 1;
                while ($row = $result->fetch_assoc()) {
                    $companyId = htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8');
                    $companyName = htmlspecialchars($row["companyname"], ENT_QUOTES, 'UTF-8');
                    $companyinfo = htmlspecialchars($row["about"], ENT_QUOTES, 'UTF-8');
                    $companyLogo = htmlspecialchars($row["logo"], ENT_QUOTES, 'UTF-8');
                    $companyind = htmlspecialchars($row["industry"], ENT_QUOTES, 'UTF-8');
                    $companyStatus = htmlspecialchars($row["active"], ENT_QUOTES, 'UTF-8');
                    $companylink = htmlspecialchars($row["link"], ENT_QUOTES, 'UTF-8');
                    $companydesc = htmlspecialchars($row["description"], ENT_QUOTES, 'UTF-8');

                    if ($i == 1) {
                        echo '<p class="fullscreen-centered-text">Отрасль: <a href="desc.php?desc='.$companyind.'">'.htmlspecialchars($companydesc, ENT_QUOTES, 'UTF-8').'</a></p>';       
                    }
                    $i++;
                break;
                }
            }
        } else {
            echo '<p>Ошибка выполнения запроса: ' . htmlspecialchars($db->error, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    } else {
        echo '<p>Ошибка подготовки запроса: ' . htmlspecialchars($db->error, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    closeDB();
    
    echo '</div></div></body></html>';
?>