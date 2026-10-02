<?php
/** Inisialisasi aplikasi: konfigurasi, session, dan pemuatan modul. */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';

date_default_timezone_set(TIMEZONE);

// Session yang aman.
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/hooks.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/webhook.php';
