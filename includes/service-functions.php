<?php
declare(strict_types=1);

function getServiceRequestForClient(mysqli $conn, int $requestId, int $userId): ?array {
    $stmt = $conn->prepare('SELECT * FROM service_requests WHERE id = ? AND user_id = ? LIMIT 1');
    if (!$stmt) {
        logDatabaseError('getServiceRequestForClient prepare', $conn);
        return null;
    }

    $stmt->bind_param('ii', $requestId, $userId);
    if (!$stmt->execute()) {
        error_log('RamTech DB error [getServiceRequestForClient execute]: ' . $stmt->error);
        return null;
    }

    return $stmt->get_result()->fetch_assoc() ?: null;
}

function getServiceRequestForAdmin(mysqli $conn, int $requestId): ?array {
    $stmt = $conn->prepare(
        "SELECT sr.*, CONCAT(u.first_name,' ',u.last_name) AS client_name, u.email, u.phone
         FROM service_requests sr
         JOIN users u ON u.id = sr.user_id
         WHERE sr.id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        logDatabaseError('getServiceRequestForAdmin prepare', $conn);
        return null;
    }

    $stmt->bind_param('i', $requestId);
    if (!$stmt->execute()) {
        error_log('RamTech DB error [getServiceRequestForAdmin execute]: ' . $stmt->error);
        return null;
    }

    return $stmt->get_result()->fetch_assoc() ?: null;
}

function addRequestUpdate(
    mysqli $conn,
    int $requestId,
    string $status,
    string $technicianName,
    string $notes,
    int $updatedBy
): bool {
    $stmt = $conn->prepare(
        'INSERT INTO request_updates
         (request_id, status, technician_name, notes, updated_by)
         VALUES (?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        logDatabaseError('addRequestUpdate prepare', $conn);
        return false;
    }

    $stmt->bind_param('isssi', $requestId, $status, $technicianName, $notes, $updatedBy);

    if (!$stmt->execute()) {
        error_log('RamTech DB error [addRequestUpdate execute]: ' . $stmt->error);
        return false;
    }

    return true;
}

function getRequestUpdates(mysqli $conn, int $requestId) {
    $stmt = $conn->prepare(
        "SELECT ru.*, CONCAT(u.first_name,' ',u.last_name) AS updater
         FROM request_updates ru
         LEFT JOIN users u ON u.id = ru.updated_by
         WHERE ru.request_id = ?
         ORDER BY ru.created_at DESC"
    );

    if (!$stmt) {
        logDatabaseError('getRequestUpdates prepare', $conn);
        return false;
    }

    $stmt->bind_param('i', $requestId);

    if (!$stmt->execute()) {
        error_log('RamTech DB error [getRequestUpdates execute]: ' . $stmt->error);
        return false;
    }

    return $stmt->get_result();
}
