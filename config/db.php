<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'hostelcare');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
$_base = getenv('BASE_URL');
define('BASE_URL', rtrim($_base !== false ? $_base : '/HostelCare', '/'));
unset($_base);
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('ESCALATION_HOURS', 48);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            die('<!DOCTYPE html><html><head><title>Service Unavailable</title></head><body style="font-family:sans-serif;text-align:center;padding:80px"><h2>Database Unavailable</h2><p>Could not connect to the database. Please check environment variables or try again later.</p></body></html>');
        }
    }
    return $pdo;
}