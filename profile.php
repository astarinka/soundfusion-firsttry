<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: authorization.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все Тип-Топ: Профиль пользователя</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #2e2e2e;
        }

        .profile-card {
            background-color: #333;
            color: azure;
            padding: 40px; /* Увеличен отступ */
            border: 4px solid #666; /* Увеличена толщина границы */
            border-radius: 16px; /* Увеличен радиус закругления */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4); /* Увеличена тень */
            text-align: center;
            max-width: 600px; /* Увеличена максимальная ширина */
            width: 100%;
        }

        .profile-card img {
            border-radius: 50%;
            height: 150px; /* Увеличен размер аватарки */
            width: 150px; /* Увеличен размер аватарки */
            object-fit: cover;
        }

        .profile-card h2 {
            margin: 25px 0; /* Увеличены отступы для заголовка */
            font-size: 24px; /* Увеличен размер шрифта */
        }

        .profile-card a {
            display: block;
            color: azure;
            text-decoration: none;
            margin-top: 20px; /* Увеличен отступ для ссылки */
            font-size: 20px; /* Увеличен размер шрифта */
        }

        .profile-card a:hover {
            color: #4a7ebB;
        }

        .profile-card .logout-button {
            display: inline-block;
            padding: 15px 25px; /* Увеличены отступы для кнопки */
            margin-top: 25px;
            border: none;
            border-radius: 10px; /* Увеличен радиус закругления */
            background-color: #333;
            color: azure;
            font-weight: 600;
            font-size: 20px; /* Увеличен размер шрифта */
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .profile-card .logout-button:hover {
            background-color: #4a7ebB;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-card">
            <img src="userlogo.jpg" alt="Аватарка">
            <h2><?= htmlspecialchars($_SESSION['user']['name']) ?></h2>
            <a href="mailto:<?= htmlspecialchars($_SESSION['user']['email']) ?>"><?= htmlspecialchars($_SESSION['user']['email']) ?></a>
            <a href="index.php" class="logout-button">Выход</a>
        </div>
    </div>
</body>
</html>
