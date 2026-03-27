<?php
/**
 * Connection switcher:
 * - Local XAMPP: set env APP_ENV=local (or local/xampp/dev), it loads conex.local.php
 * - Otherwise: loads production-style conex.php
 */
$appEnv = strtolower((string) getenv('APP_ENV'));

$isLocalRequest = false;
$serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
$serverAddr = (string) ($_SERVER['SERVER_ADDR'] ?? '');

if ($serverName === 'localhost' || $serverName === '127.0.0.1' || $serverAddr === '127.0.0.1' || $serverAddr === '::1') {
    $isLocalRequest = true;
}

if (in_array($appEnv, ['local', 'xampp', 'dev'], true) || $isLocalRequest) {
    require_once __DIR__ . '/conex.local.php';
} else {
    require_once __DIR__ . '/conex.php';
}
