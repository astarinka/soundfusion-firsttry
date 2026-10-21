<?php
require_once __DIR__ . '/function.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrfToken()) {
    http_response_code(400);
    exit('Запрос не прошёл проверку. Обновите страницу и попробуйте ещё раз.');
}
unset($_SESSION['user_id']);
session_regenerate_id(true);
setFlashMessage('Вы вышли из аккаунта.');
redirectTo('login.php');
