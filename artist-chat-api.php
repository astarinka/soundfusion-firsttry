<?php
declare(strict_types=1);

require_once __DIR__ . '/function.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function chatResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$db = connectDB();
if (!$db) {
    chatResponse(['error' => 'Чаты временно недоступны: проверьте подключение к базе данных.'], 503);
}
$user = getCurrentUser($db);
if (!$user) {
    chatResponse(['error' => 'Войдите в аккаунт, чтобы пользоваться чатами.', 'login' => true], 401);
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');
$userId = (int) $user['id'];
$isRoomMember = static function (mysqli $db, int $memberId, int $roomId): bool {
    $check = $db->prepare('SELECT 1 FROM artist_chat_members WHERE user_id = ? AND artist_id = ? LIMIT 1');
    if (!$check) {
        return false;
    }
    $check->bind_param('ii', $memberId, $roomId);
    $check->execute();
    $isMember = ($check->get_result()?->num_rows ?? 0) > 0;
    $check->close();
    return $isMember;
};

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $statement = $db->prepare(
        'SELECT c.id, c.companyname, c.logo, m.joined_at,
                (SELECT COUNT(*) FROM artist_chat_messages msg WHERE msg.artist_id = c.id) AS message_count,
                (SELECT COUNT(*) FROM artist_chat_members room_members WHERE room_members.artist_id = c.id) AS member_count
         FROM artist_chat_members m
         JOIN content c ON c.id = m.artist_id
         WHERE m.user_id = ? AND c.active = 1
         ORDER BY m.joined_at DESC, c.companyname'
    );
    if (!$statement) {
        chatResponse(['error' => 'Не удалось загрузить список чатов. Импортируйте database/artist-chats.sql.'], 503);
    }
    $statement->bind_param('i', $userId);
    if (!$statement->execute()) {
        $statement->close();
        chatResponse(['error' => 'Не удалось загрузить список чатов. Импортируйте database/artist-chats.sql.'], 503);
    }
    $result = $statement->get_result();
    $rooms = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();
    foreach ($rooms as &$room) {
        $room['id'] = (int) $room['id'];
        $room['message_count'] = (int) $room['message_count'];
        $room['member_count'] = (int) $room['member_count'];
        $room['image'] = artistImagePath((string) ($room['logo'] ?? ''));
        unset($room['logo']);
    }
    unset($room);
    chatResponse(['rooms' => $rooms]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'members') {
    $artistId = filter_input(INPUT_GET, 'artist_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    if ($artistId < 1 || !$isRoomMember($db, $userId, $artistId)) {
        chatResponse(['error' => 'Вступите в групповой чат, чтобы просмотреть его участников.'], 403);
    }
    $statement = $db->prepare(
        'SELECT u.id, u.display_name, u.username, u.avatar_path
         FROM artist_chat_members m JOIN users u ON u.id = m.user_id
         WHERE m.artist_id = ? ORDER BY u.display_name, u.username'
    );
    if (!$statement) {
        chatResponse(['error' => 'Не удалось загрузить участников.'], 503);
    }
    $statement->bind_param('i', $artistId);
    if (!$statement->execute()) {
        $statement->close();
        chatResponse(['error' => 'Не удалось загрузить участников.'], 503);
    }
    $result = $statement->get_result();
    $members = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();
    foreach ($members as &$member) {
        $member['id'] = (int) $member['id'];
        $member['mine'] = $member['id'] === $userId;
        $avatar = (string) ($member['avatar_path'] ?? '');
        $member['avatar'] = preg_match('~^uploads/avatars/[A-Za-z0-9._-]+$~D', $avatar) === 1 && is_file(__DIR__ . '/' . $avatar) ? $avatar : '';
        unset($member['avatar_path']);
    }
    unset($member);
    chatResponse(['members' => $members, 'count' => count($members)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && in_array($action, ['messages', 'private_messages'], true)) {
    $artistId = filter_input(INPUT_GET, 'artist_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    $afterId = filter_input(INPUT_GET, 'after', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
    if ($artistId < 1) {
        chatResponse(['error' => 'Не выбрана комната чата.'], 400);
    }
    if (!$isRoomMember($db, $userId, $artistId)) {
        chatResponse(['error' => 'Сначала вступите в этот чат.'], 403);
    }

    if ($action === 'private_messages') {
        $recipientId = filter_input(INPUT_GET, 'recipient_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
        if ($recipientId < 1 || $recipientId === $userId || !$isRoomMember($db, $recipientId, $artistId)) {
            chatResponse(['error' => 'Личные сообщения доступны только участникам этого группового чата.'], 403);
        }
        if ($afterId > 0) {
            $statement = $db->prepare(
                'SELECT pm.id, pm.message, pm.created_at, sender.id AS sender_id, sender.display_name, sender.username
                 FROM artist_private_messages pm JOIN users sender ON sender.id = pm.sender_id
                 WHERE pm.artist_id = ? AND ((pm.sender_id = ? AND pm.recipient_id = ?) OR (pm.sender_id = ? AND pm.recipient_id = ?)) AND pm.id > ?
                 ORDER BY pm.id ASC LIMIT 60'
            );
            if ($statement) {
                $statement->bind_param('iiiiii', $artistId, $userId, $recipientId, $recipientId, $userId, $afterId);
            }
        } else {
            $statement = $db->prepare(
                'SELECT recent.id, recent.message, recent.created_at, recent.sender_id, recent.display_name, recent.username
                 FROM (
                     SELECT pm.id, pm.message, pm.created_at, sender.id AS sender_id, sender.display_name, sender.username
                     FROM artist_private_messages pm JOIN users sender ON sender.id = pm.sender_id
                     WHERE pm.artist_id = ? AND ((pm.sender_id = ? AND pm.recipient_id = ?) OR (pm.sender_id = ? AND pm.recipient_id = ?))
                     ORDER BY pm.id DESC LIMIT 50
                 ) AS recent ORDER BY recent.id ASC'
            );
            if ($statement) {
                $statement->bind_param('iiiii', $artistId, $userId, $recipientId, $recipientId, $userId);
            }
        }
        if (!$statement || !$statement->execute()) {
            if ($statement) {
                $statement->close();
            }
            chatResponse(['error' => 'Не удалось загрузить личную переписку. Проверьте database/artist-chats.sql.'], 503);
        }
        $result = $statement->get_result();
        $messages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $statement->close();
        foreach ($messages as &$message) {
            $message['id'] = (int) $message['id'];
            $message['sender_id'] = (int) $message['sender_id'];
            $message['mine'] = (int) ($message['sender_id'] === $userId);
        }
        unset($message);
        chatResponse(['messages' => $messages]);
    }

    if ($afterId > 0) {
        $statement = $db->prepare(
            'SELECT msg.id, msg.message, msg.created_at, u.id AS sender_id, u.display_name, u.username
             FROM artist_chat_messages msg JOIN users u ON u.id = msg.user_id
             WHERE msg.artist_id = ? AND msg.id > ? ORDER BY msg.id ASC LIMIT 60'
        );
        if ($statement) {
            $statement->bind_param('ii', $artistId, $afterId);
        }
    } else {
        $statement = $db->prepare(
            'SELECT recent.id, recent.message, recent.created_at, recent.sender_id, recent.display_name, recent.username
             FROM (
                 SELECT msg.id, msg.message, msg.created_at, u.id AS sender_id, u.display_name, u.username
                 FROM artist_chat_messages msg JOIN users u ON u.id = msg.user_id
                 WHERE msg.artist_id = ? ORDER BY msg.id DESC LIMIT 50
             ) AS recent ORDER BY recent.id ASC'
        );
        if ($statement) {
            $statement->bind_param('i', $artistId);
        }
    }
    if (!$statement || !$statement->execute()) {
        if ($statement) {
            $statement->close();
        }
        chatResponse(['error' => 'Не удалось загрузить сообщения.'], 503);
    }
    $result = $statement->get_result();
    $messages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();
    foreach ($messages as &$message) {
        $message['id'] = (int) $message['id'];
        $message['sender_id'] = (int) $message['sender_id'];
        $message['mine'] = (int) ($message['sender_id'] === $userId);
    }
    unset($message);
    chatResponse(['messages' => $messages]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chatResponse(['error' => 'Неподдерживаемый запрос.'], 405);
}
if (!validCsrfToken()) {
    chatResponse(['error' => 'Защита формы устарела. Обновите страницу и повторите попытку.'], 403);
}

$artistId = filter_input(INPUT_POST, 'artist_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
if ($artistId < 1) {
    chatResponse(['error' => 'Не выбран артист.'], 400);
}

if ($action === 'join') {
    $artist = $db->prepare('SELECT id FROM content WHERE id = ? AND active = 1 LIMIT 1');
    if (!$artist) {
        chatResponse(['error' => 'Не удалось найти артиста.'], 503);
    }
    $artist->bind_param('i', $artistId);
    $artist->execute();
    $found = $artist->get_result()?->num_rows > 0;
    $artist->close();
    if (!$found) {
        chatResponse(['error' => 'Артист не найден или скрыт из каталога.'], 404);
    }
    $statement = $db->prepare('INSERT IGNORE INTO artist_chat_members (user_id, artist_id) VALUES (?, ?)');
    if (!$statement) {
        chatResponse(['error' => 'Чаты ещё не настроены. Импортируйте database/artist-chats.sql.'], 503);
    }
    $statement->bind_param('ii', $userId, $artistId);
    if (!$statement->execute()) {
        $statement->close();
        chatResponse(['error' => 'Не удалось вступить в чат.'], 503);
    }
    $statement->close();
    chatResponse(['ok' => true, 'artist_id' => $artistId]);
}

if ($action === 'leave') {
    $statement = $db->prepare('DELETE FROM artist_chat_members WHERE user_id = ? AND artist_id = ?');
    if (!$statement) {
        chatResponse(['error' => 'Не удалось выйти из чата.'], 503);
    }
    $statement->bind_param('ii', $userId, $artistId);
    $ok = $statement->execute();
    $statement->close();
    chatResponse($ok ? ['ok' => true] : ['error' => 'Не удалось выйти из чата.'], $ok ? 200 : 503);
}

if ($action === 'send') {
    $text = trim((string) ($_POST['message'] ?? ''));
    if ($text === '' || mb_strlen($text) > 1000) {
        chatResponse(['error' => 'Сообщение должно содержать от 1 до 1000 символов.'], 400);
    }
    if (!$isRoomMember($db, $userId, $artistId)) {
        chatResponse(['error' => 'Вступите в чат, прежде чем отправлять сообщение.'], 403);
    }
    $statement = $db->prepare('INSERT INTO artist_chat_messages (artist_id, user_id, message) VALUES (?, ?, ?)');
    if (!$statement) {
        chatResponse(['error' => 'Не удалось сохранить сообщение.'], 503);
    }
    $statement->bind_param('iis', $artistId, $userId, $text);
    if (!$statement->execute()) {
        $statement->close();
        chatResponse(['error' => 'Не удалось сохранить сообщение.'], 503);
    }
    $statement->close();
    chatResponse(['ok' => true]);
}

if ($action === 'private_send') {
    $recipientId = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    $text = trim((string) ($_POST['message'] ?? ''));
    if ($recipientId < 1 || $recipientId === $userId || $text === '' || mb_strlen($text) > 1000) {
        chatResponse(['error' => 'Проверьте получателя и текст сообщения (до 1000 символов).'], 400);
    }
    if (!$isRoomMember($db, $userId, $artistId) || !$isRoomMember($db, $recipientId, $artistId)) {
        chatResponse(['error' => 'Личное сообщение можно отправить только участнику того же группового чата.'], 403);
    }
    $statement = $db->prepare('INSERT INTO artist_private_messages (artist_id, sender_id, recipient_id, message) VALUES (?, ?, ?, ?)');
    if (!$statement) {
        chatResponse(['error' => 'Личные сообщения ещё не настроены. Импортируйте database/artist-chats.sql.'], 503);
    }
    $statement->bind_param('iiis', $artistId, $userId, $recipientId, $text);
    if (!$statement->execute()) {
        $statement->close();
        chatResponse(['error' => 'Не удалось сохранить личное сообщение.'], 503);
    }
    $statement->close();
    chatResponse(['ok' => true]);
}

chatResponse(['error' => 'Неизвестное действие.'], 400);
