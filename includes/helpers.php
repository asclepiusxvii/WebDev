<?php
declare(strict_types=1);

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

function pullFlash(string $type): string {
    $key = 'flash_' . $type;
    $message = (string)($_SESSION[$key] ?? '');
    unset($_SESSION[$key]);
    return $message;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool {
    $submitted = (string)($_POST['csrf_token'] ?? '');
    $stored = (string)($_SESSION['csrf_token'] ?? '');

    return $stored !== '' && $submitted !== '' && hash_equals($stored, $submitted);
}

function requireCsrf(): void {
    if (!verifyCsrf()) {
        http_response_code(400);
        die(
            '<div style="font-family:Arial,sans-serif;max-width:720px;margin:60px auto;padding:24px">' .
            '<h2>Invalid form request</h2>' .
            '<p>The form expired or could not be verified. Go back, refresh the page, and try again.</p>' .
            '</div>'
        );
    }
}

function statusClass(string $status): string {
    return match ($status) {
        'Pending' => 'bg-yellow-100 text-yellow-800',
        'Accepted' => 'bg-blue-100 text-blue-800',
        'Device Received' => 'bg-indigo-100 text-indigo-800',
        'Diagnosing' => 'bg-purple-100 text-purple-800',
        'In Progress' => 'bg-orange-100 text-orange-800',
        'Ready for Pickup' => 'bg-cyan-100 text-cyan-800',
        'Completed' => 'bg-green-100 text-green-800',
        'Cancelled' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

function requestCode(int $id): string {
    return '#RT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

function allowedStatuses(): array {
    return [
        'Pending', 'Accepted', 'Device Received', 'Diagnosing',
        'In Progress', 'Ready for Pickup', 'Completed', 'Cancelled'
    ];
}

function allowedServiceTypes(): array {
    return ['Device Repair', 'Hardware Solutions', 'Software Support', 'IT Services'];
}

function allowedDeviceTypes(): array {
    return ['Laptop', 'Desktop PC', 'Smartphone', 'Tablet', 'Printer', 'Other'];
}

function allowedServiceMethods(): array {
    return ['Drop-off', 'On-site'];
}

function validDateOrEmpty(string $date): bool {
    if ($date === '') {
        return true;
    }

    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function logDatabaseError(string $context, mysqli $conn): void {
    error_log('RamTech DB error [' . $context . ']: ' . $conn->error);
}
