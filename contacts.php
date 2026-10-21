<?php
	require_once('./function.php');
echo '<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Программа лояльности: Бонусный клуб "Все Тип-Топ"</title>
    <link rel="stylesheet" href="style.css">
	</head>';
	echo '<body>
    <div class="block0">
        <div><h1 style="color: azure;" class="center-text"><strong>'.page(basename($_SERVER['SCRIPT_NAME'])).'</strong></h1>
        </div>
		<div style="color: azure;" class="center-text">';

		
		
		
	$mysqli = false;

	$db = connectDB();
	
	$sql = "SELECT * FROM menu order by id";
		if ($result = $db->query($sql)) {

		if ($result->num_rows > 0) {
       
			while ($row = $result->fetch_assoc()) {
				$userid = $row["id"];
				$username = $row["name"];
				$useralt = $row["alt"];
				$userlink = $row["link"];
				$userenable = $row["enable"];
            
            
	$activeClass = isActive(basename($userlink)) ? 'active' : '';

                        echo '<div class="block1">';
                        echo '<a href="' . $userlink . '">';
                        echo '<button type="button" class="' . $activeClass . '">' . $username . '</button>';
                        echo '</a>';
                        echo '</div>';
        }
    } else {
        echo "Нет данных в таблице menu.";
    }
} else {
    echo "Ошибка запроса: " . $db->error;
}

$db->close();
?>
