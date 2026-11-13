<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('LOGBOOK_DB_HOST') ?: 'localhost';
$user = getenv('LOGBOOK_DB_USER') ?: 'root';
$pass = getenv('LOGBOOK_DB_PASSWORD') ?: '';
$db = getenv('LOGBOOK_DB_NAME') ?: 'db_logbook_magang';
$port = (int) (getenv('LOGBOOK_DB_PORT') ?: 3306);

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset('utf8mb4');

$adminPassword = (string) (getenv('LOGBOOK_ADMIN_PASSWORD') ?: '');
