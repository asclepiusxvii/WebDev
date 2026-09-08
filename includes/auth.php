<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: /ramtech/login.php");
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        if (($_SESSION['role'] ?? '') === 'admin') {
            header("Location: /ramtech/admin/dashboard.php");
        } else {
            header("Location: /ramtech/client/dashboard.php");
        }
        exit;
    }
}

function redirectByRole(): void {
    if (!isLoggedIn()) return;
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: /ramtech/admin/dashboard.php");
    } else {
        header("Location: /ramtech/client/dashboard.php");
    }
    exit;
}
?>
