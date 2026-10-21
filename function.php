<?php

function connectDB() 
{
    global $mysqli;
    $mysqli = new mysqli("localhost", "root", "", "db_web");
    if ($mysqli->connect_error) 
    {
        die("Ошибка подключения: " . $mysqli->connect_error);
    }
    return $mysqli;
}

function closeDB() {
    global $mysqli;
    if ($mysqli) {
        $mysqli->close();
    }
}

function page()
{
    $currentFile = basename($_SERVER['SCRIPT_NAME']);
    switch ($currentFile) {
        case 'index.php':
            $pageTitle = 'SoundFusion';
            break;
        case 'about.php':
            $pageTitle = "О проекте";
            break;
        case 'music.php':
            $pageTitle = "Музыка";
            break;
        case 'news.php':
            $pageTitle = "Новости";
            break;
        case 'events.php':
            $pageTitle = "События";
            break;
        default:
            $pageTitle = "Неизвестная Фирма";
            break;
    }
    return htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
}

function isActive($pageName) {
    return basename($_SERVER['SCRIPT_NAME']) === $pageName ? 'active' : '';
}

function getPageTitle($companyid) {
    switch ($companyid) {
        case '1':
            $title = 'The Weeknd';
            break;
        case '2':
            $title = 'Playboi Carti';
            break;
        case '3':
            $title = 'Kendrick Lamar';
            break;
        case '4':
            $title = 'Future';
            break;
        case '5':
            $title = 'Metro Boomin';
            break;
        case '6':
            $title = 'Drake';
            break;
        case '7':
            $title = 'Yeat';
            break;
        case '8':
            $title = 'Don Toliver';
            break;
        case '9':
            $title = 'Tyler The Creator';
            break;
        case '10':
            $title = 'Kanye West';
            break;
        case '11':
            $title = 'A$ap Rocky';
            break;
        case '12':
            $title = 'Radiohead';
            break;
        default:
            $title = 'Неизвестная Фирма';
            break;
    }
    return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
}

function description($name) {
    switch ($name) {
        case '1':
            $desctitle = 'Поп-Артисты';
            break;
        case '2':
            $desctitle = 'Реперы';
            break;
        case '3':
            $desctitle = 'Продюсеры';
            break;
        case '4':
            $desctitle = 'Рокеры';
            break;
        default:
            $desctitle = 'Неизвестная Отрасль';
            break;
    }
    return htmlspecialchars($desctitle, ENT_QUOTES, 'UTF-8');
}
/*
session_start();*/

    $full_name = $_POST['full_name'] ?? '';
    $login = $_POST['login'] ?? '';
    $email = $_POST['email'] ?? '';
    $number = $_POST['number'] ?? '';
    $password = $_POST['password'] ?? '';

    /*$hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $db = connectDB();

    $sql = "INSERT INTO users (name, login, email, number, password) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('sssss', $full_name, $login, $email, $number, $hashed_password);
        $stmt->execute();
        $stmt->close();
    }

    $db->close();


exit();*/
?>
