<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_name('soundfusion_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function validCsrfToken(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted) && isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $submitted);
}

function getCurrentUser(?mysqli $connection): ?array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if (!$connection || $userId < 1) {
        return null;
    }

    $statement = $connection->prepare('SELECT id, email, username, display_name, avatar_path, created_at FROM users WHERE id = ? LIMIT 1');
    if (!$statement) {
        return null;
    }
    $statement->bind_param('i', $userId);
    if (!$statement->execute()) {
        $statement->close();
        return null;
    }
    $result = $statement->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $statement->close();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }
    $user['role'] = 'user';
    $roleStatement = $connection->prepare('SELECT role FROM user_roles WHERE user_id = ? LIMIT 1');
    if ($roleStatement) {
        $roleStatement->bind_param('i', $userId);
        if ($roleStatement->execute()) {
            $roleResult = $roleStatement->get_result();
            $roleRow = $roleResult ? $roleResult->fetch_assoc() : null;
            $role = $roleRow['role'] ?? null;
            if ($role === 'moderator') {
                $user['role'] = 'moderator';
            }
        }
        $roleStatement->close();
    }
    return $user;
}

function setFlashMessage(string $message, string $type = 'success'): void
{
    $_SESSION['flash_message'] = ['text' => $message, 'type' => $type];
}

function takeFlashMessage(): ?array
{
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return is_array($message) ? $message : null;
}

function redirectTo(string $location): never
{
    header('Location: ' . $location);
    exit;
}
