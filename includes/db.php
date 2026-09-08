<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_OFF);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ramtech_db';

$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_errno) {
    error_log('RamTech database connection error: ' . $conn->connect_error);
    http_response_code(500);
    die(
        '<div style="font-family:Arial,sans-serif;max-width:720px;margin:60px auto;padding:24px">' .
        '<h2>RamTech is temporarily unavailable</h2>' .
        '<p>The website could not connect to its database. Please make sure MySQL is running in XAMPP and try again.</p>' .
        '</div>'
    );
}

if (!$conn->set_charset('utf8mb4')) {
    error_log('RamTech charset error: ' . $conn->error);
}
