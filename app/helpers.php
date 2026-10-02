<?php
/** Fungsi bantuan kecil untuk seluruh aplikasi. */

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(string $suffix = ''): string
{
    static $base = null;
    if ($base === null) {
        // Simpan jalur dasar dari script utama (berguna di sub-folder).
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($script));
        $base = $dir === '/' || $dir === '\\' ? '' : rtrim($dir, '/');
    }
    return $base . $suffix;
}

function redirect(string $path): never
{
    header('Location: ' . base_path($path));
    exit;
}

/**
 * URL absolut untuk endpoint webhook — yang harus disalin ke layanan eksternal.
 * Menghormati PUBLIC_BASE_URL, lalu header X-Forwarded-* dari reverse proxy (NPM).
 */
function absolute_url(string $path): string
{
    $configured = rtrim((string)PUBLIC_BASE_URL, '/');
    if ($configured !== '') {
        return $configured . base_path($path);
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    $host = (string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? '');
    $host = $host !== '' ? trim(explode(',', $host)[0]) : (string)($_SERVER['HTTP_HOST'] ?? 'localhost');

    return ($https ? 'https' : 'http') . '://' . $host . base_path($path);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Render view dengan $data tersedia sebagai variabel. */
function view(string $name, array $data = [], ?string $layout = 'layout'): void
{
    extract($data, EXTR_SKIP);
    $viewFile = dirname(__DIR__) . '/views/' . $name . '.php';
    if (!is_file($viewFile)) {
        http_response_code(500);
        echo 'View tidak ditemukan: ' . e($name);
        exit;
    }
    if ($layout === null) {
        require $viewFile;
        return;
    }
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require dirname(__DIR__) . '/views/' . $layout . '.php';
}

function json_out(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return $diff . ' dtk lalu';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' mnt lalu';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    }
    return floor($diff / 86400) . ' hari lalu';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        echo 'Token CSRF tidak valid. Muat ulang halaman lalu coba lagi.';
        exit;
    }
}
