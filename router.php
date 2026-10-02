<?php
/**
 * Router untuk PHP built-in server:
 *   php -S 127.0.0.1:3010 router.php
 *
 * Urutan penting:
 *  1) Path terlarang ditolak dulu — `return false` akan menyerahkan file mentah.
 *  2) Hanya aset statik non-PHP yang diserahkan ke server bawaan.
 *  3) Sisanya (termasuk .php) dijalankan lewat front controller.
 */

$root = __DIR__;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . ltrim(str_replace('\\', '/', $path), '/');

// 1) Folder internal & file sensitif → tolak (cocok di sub-folder juga).
if (
    preg_match('#/(app|data|views)(/|$)#', $path)
    || preg_match('#\.(sqlite|sqlite-wal|sqlite-shm|ini|md)$#i', $path)
    || preg_match('#/(config|router|bootstrap|helpers|db|auth|hooks|telegram|webhook)\.php$#', $path)
) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    return true;
}

// 2) Aset statik (CSS/JS/gambar) → server bawaan.
//    File .php TIDAK boleh jatuh ke sini agar tak dieksekusi sembarangan.
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if ($path !== '/' && $ext !== 'php' && is_file($root . $path)) {
    return false;
}

// 3) Sisanya (termasuk index.php & /hook/{token}) → front controller.
require $root . '/index.php';
