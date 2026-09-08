<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);

    session_start();
}

const SESSION_TIMEOUT_SECONDS = 1800; // 30 minutes

if (isset($_SESSION['user_id'])) {
    $lastActivity = (int)($_SESSION['last_activity'] ?? time());

    if ((time() - $lastActivity) > SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your session expired. Please sign in again.';
    } else {
        $_SESSION['last_activity'] = time();

        $lastRegenerated = (int)($_SESSION['last_regenerated'] ?? 0);
        if ($lastRegenerated === 0 || (time() - $lastRegenerated) > 900) {
            session_regenerate_id(true);
            $_SESSION['last_regenerated'] = time();
        }
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /ramtech/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . (($_SESSION['role'] ?? '') === 'admin'
            ? '/ramtech/admin/dashboard.php'
            : '/ramtech/client/dashboard.php'));
        exit;
    }
}

function redirectByRole(): void {
    if (!isLoggedIn()) {
        return;
    }

    header('Location: ' . (($_SESSION['role'] ?? '') === 'admin'
        ? '/ramtech/admin/dashboard.php'
        : '/ramtech/client/dashboard.php'));
    exit;
}
