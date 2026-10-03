<?php
/** Autentikasi: register, login, logout, rate-limit. */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = $_SESSION['user_id'] ?? 0;
    if (!$id) {
        return $user = null;
    }
    $stmt = db()->prepare('SELECT id, username, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $user = $stmt->fetch() ?: null;
}

function require_login(): void
{
    if (!current_user()) {
        flash('warning', 'Silakan login terlebih dahulu.');
        redirect('/login');
    }
}

function registration_state(): array
{
    // Override dari menu Pengaturan ('0' tertutup, '1' terbuka) mengalahkan config.
    $override = settings_get('registration_open');
    if ($override === '0') {
        return ['open' => false, 'code_required' => false];
    }
    if ($override === '1') {
        return ['open' => true, 'code_required' => false];
    }

    if (REGISTRATION_OPEN === true) {
        return ['open' => true, 'code_required' => false];
    }
    if (is_string(REGISTRATION_OPEN) && REGISTRATION_OPEN !== '') {
        return ['open' => true, 'code_required' => true];
    }
    return ['open' => false, 'code_required' => false];
}

function register_user(string $username, string $password, ?string $code = null): array
{
    $username = strtolower(trim($username));
    $state = registration_state();

    if (!$state['open']) {
        return [false, 'Registrasi sedang ditutup.'];
    }
    if ($state['code_required'] && !hash_equals((string)REGISTRATION_OPEN, (string)$code)) {
        return [false, 'Kode registrasi tidak cocok.'];
    }
    if (!preg_match('/^[a-z0-9_]{3,32}$/', $username)) {
        return [false, 'Username: 3–32 karakter, hanya huruf kecil, angka, dan underscore.'];
    }
    if (strlen($password) < 8) {
        return [false, 'Password minimal 8 karakter.'];
    }

    $exists = db()->prepare('SELECT 1 FROM users WHERE username = ?');
    $exists->execute([$username]);
    if ($exists->fetch()) {
        return [false, 'Username sudah dipakai.'];
    }

    db()->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?,?,?)')
        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), now()]);

    return [true, 'Registrasi berhasil. Silakan login.'];
}

function login_attempts_key(string $username): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    return strtolower(trim($username)) . '|' . $ip;
}

function login_is_locked(string $key): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM failed_logins WHERE atk_key = ? AND created_at > ?'
    );
    $stmt->execute([$key, date('Y-m-d H:i:s', time() - 900)]);
    return (int)$stmt->fetchColumn() >= 10;
}

function login_record_failure(string $key): void
{
    db()->prepare('INSERT INTO failed_logins (atk_key, created_at) VALUES (?,?)')
        ->execute([$key, now()]);
    // Bersihkan entri lama agar tabel tidak membengkak.
    db()->exec("DELETE FROM failed_logins WHERE created_at < '" .
        date('Y-m-d H:i:s', time() - 86400) . "'");
}

function attempt_login(string $username, string $password): array
{
    $key = login_attempts_key($username);
    if (login_is_locked($key)) {
        return [false, 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.'];
    }

    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE username = ?');
    $stmt->execute([strtolower(trim($username))]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        login_record_failure($key);
        return [false, 'Username atau password salah.'];
    }

    db()->prepare('DELETE FROM failed_logins WHERE atk_key = ?')->execute([$key]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$row['id'];
    return [true, 'Selamat datang kembali!'];
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
