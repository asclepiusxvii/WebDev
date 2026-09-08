<?php
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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
?>
