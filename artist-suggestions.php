<?php
declare(strict_types=1);

require_once __DIR__ . '/function.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$query = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
if ($query === '') {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$db = connectDB();
if (!$db) {
    http_response_code(503);
    echo json_encode(['error' => 'Поиск временно недоступен.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$statement = $db->prepare(
    'SELECT c.id, c.companyname, c.logo, i.description AS category_name
     FROM content c
     LEFT JOIN industry i ON i.id = c.industry
     WHERE c.active = 1 AND (c.companyname LIKE ? OR i.description LIKE ? OR c.about LIKE ?)
     ORDER BY CASE WHEN c.companyname LIKE ? THEN 0 ELSE 1 END, c.companyname
     LIMIT 8'
);

if (!$statement) {
    closeDB($db);
    http_response_code(500);
    echo json_encode(['error' => 'Не удалось выполнить поиск.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$containsQuery = '%' . $query . '%';
$startsWithQuery = $query . '%';
$statement->bind_param('ssss', $containsQuery, $containsQuery, $containsQuery, $startsWithQuery);
if (!$statement->execute()) {
    $statement->close();
    closeDB($db);
    http_response_code(500);
    echo json_encode(['error' => 'Не удалось выполнить поиск.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = [];
$result = $statement->get_result();
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => (int) $row['id'],
        'name' => (string) $row['companyname'],
        'category' => (string) ($row['category_name'] ?? 'Исполнитель'),
        'image' => artistImagePath((string) ($row['logo'] ?? '')),
    ];
}

$statement->close();
closeDB($db);
echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
