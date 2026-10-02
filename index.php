<?php
/**
 * Front controller — semua request diarahkan ke sini.
 */

require __DIR__ . '/app/bootstrap.php';

// ---------------------------------------------------------------------------
// Jangan izinkan akses langsung ke file internal (pengaman ekstra selain .htaccess).
// ---------------------------------------------------------------------------
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rawPath = '/' . ltrim(str_replace('\\', '/', $rawPath), '/');

if (
    preg_match('#^/(app|data|views)(/|$)#', $rawPath)
    || preg_match('#/(config|router|index)\.php$#', $rawPath)
    || preg_match('#\.(sqlite|sqlite-wal|sqlite-shm|ini|md)$#i', $rawPath)
) {
    http_response_code(403);
    exit('Forbidden');
}

// ---------------------------------------------------------------------------
// Tentukan path rute (sesuaikan dengan lokasi instalasi di sub-folder).
// ---------------------------------------------------------------------------
$base = base_path();
$path = $rawPath;
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
if ($path !== '/' ) {
    $path = rtrim($path, '/') ?: '/';
}
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ---------------------------------------------------------------------------
// Tabel rute: [method, regex, handler]
// ---------------------------------------------------------------------------
$routes = [
    ['GET', '#^/$#', function () {
        redirect(current_user() ? '/dashboard' : '/login');
    }],

    // --- Auth ---
    ['GET', '#^/register$#', function () {
        if (current_user()) {
            redirect('/dashboard');
        }
        view('auth/register', ['state' => registration_state()]);
    }],
    ['POST', '#^/register$#', function () {
        verify_csrf();
        [$ok, $msg] = register_user(
            (string)($_POST['username'] ?? ''),
            (string)($_POST['password'] ?? ''),
            isset($_POST['code']) ? (string)$_POST['code'] : null
        );
        flash($ok ? 'success' : 'danger', $msg);
        redirect($ok ? '/login' : '/register');
    }],

    ['GET', '#^/login$#', function () {
        if (current_user()) {
            redirect('/dashboard');
        }
        view('auth/login');
    }],
    ['POST', '#^/login$#', function () {
        verify_csrf();
        [$ok, $msg] = attempt_login(
            (string)($_POST['username'] ?? ''),
            (string)($_POST['password'] ?? '')
        );
        flash($ok ? 'success' : 'danger', $msg);
        redirect($ok ? '/dashboard' : '/login');
    }],
    ['POST', '#^/logout$#', function () {
        verify_csrf();
        logout();
        redirect('/login');
    }],

    // --- Dashboard ---
    ['GET', '#^/dashboard$#', function () {
        require_login();
        view('dashboard', [
            'user'  => current_user(),
            'hooks' => hooks_for_user((int)$_SESSION['user_id']),
            'stats' => hook_stats((int)$_SESSION['user_id']),
        ]);
    }],

    // --- Hooks ---
    ['GET', '#^/hooks/new$#', function () {
        require_login();
        view('hooks/form', ['hook' => null, 'user' => current_user()]);
    }],
    ['POST', '#^/hooks/new$#', function () {
        require_login();
        verify_csrf();
        [$ok, $msg] = hook_save($_POST, (int)$_SESSION['user_id']);
        flash($ok ? 'success' : 'danger', $msg);
        redirect($ok ? '/dashboard' : '/hooks/new');
    }],
    ['GET', '#^/hooks/(\d+)$#', function (string $id) {
        require_login();
        $hook = hook_owned((int)$id, (int)$_SESSION['user_id']);
        if (!$hook) {
            http_response_code(404);
            view('errors/404', ['user' => current_user()]);
            return;
        }
        view('hooks/detail', [
            'hook' => $hook,
            'logs' => hook_log((int)$hook['id'], 50),
            'user' => current_user(),
        ]);
    }],
    ['GET', '#^/hooks/(\d+)/edit$#', function (string $id) {
        require_login();
        $hook = hook_owned((int)$id, (int)$_SESSION['user_id']);
        if (!$hook) {
            http_response_code(404);
            view('errors/404', ['user' => current_user()]);
            return;
        }
        view('hooks/form', ['hook' => $hook, 'user' => current_user()]);
    }],
    ['POST', '#^/hooks/(\d+)/edit$#', function (string $id) {
        require_login();
        verify_csrf();
        $hook = hook_owned((int)$id, (int)$_SESSION['user_id']);
        if (!$hook) {
            http_response_code(404);
            view('errors/404', ['user' => current_user()]);
            return;
        }
        [$ok, $msg] = hook_save($_POST, (int)$_SESSION['user_id'], (int)$hook['id']);
        flash($ok ? 'success' : 'danger', $msg);
        redirect($ok ? '/hooks/' . $hook['id'] : '/hooks/' . $id . '/edit');
    }],
    ['POST', '#^/hooks/(\d+)/delete$#', function (string $id) {
        require_login();
        verify_csrf();
        $hook = hook_owned((int)$id, (int)$_SESSION['user_id']);
        if ($hook) {
            hook_delete((int)$hook['id']);
            flash('success', 'Hook "' . $hook['name'] . '" dihapus.');
        }
        redirect('/dashboard');
    }],
    ['POST', '#^/hooks/(\d+)/test$#', function (string $id) {
        require_login();
        verify_csrf();
        $hook = hook_owned((int)$id, (int)$_SESSION['user_id']);
        if (!$hook) {
            json_out(['ok' => false, 'error' => 'not_found'], 404);
        }
        [$ok, $info] = webhook_test($hook);
        json_out(['ok' => $ok, 'message' => $info]);
    }],

    // --- API live update (dipolling oleh app.js tiap beberapa detik) ---
    ['GET', '#^/api/live$#', function () {
        require_login();
        $uid = (int)$_SESSION['user_id'];

        // Dashboard: statistik + ringkasan tiap hook.
        $summary = [
            'stats' => hook_stats($uid),
            'hooks' => [],
            'time'  => date('Y-m-d H:i:s'),
        ];
        foreach (hooks_for_user($uid) as $h) {
            $summary['hooks'][] = [
                'id'        => (int)$h['id'],
                'total'     => (int)$h['total'],
                'last_at'   => $h['last_at'],
                'last_ago'  => $h['last_at'] ? time_ago($h['last_at']) : null,
                'is_active' => (int)$h['is_active'],
            ];
        }

        // Detail hook: status aktif + log pengiriman terbaru.
        $detailId = isset($_GET['hook']) ? (int)$_GET['hook'] : 0;
        $detail = null;
        if ($detailId > 0 && ($hook = hook_owned($detailId, $uid))) {
            $detail = [
                'id'        => (int)$hook['id'],
                'is_active' => (int)$hook['is_active'],
                'logs'      => [],
            ];
            foreach (hook_log((int)$hook['id'], 30) as $l) {
                $detail['logs'][] = [
                    'id'         => (int)$l['id'],
                    'status'     => $l['status'],
                    'detail'     => $l['detail'],
                    'payload'    => $l['payload'],
                    'created_at' => $l['created_at'],
                    'ago'        => time_ago($l['created_at']),
                ];
            }
        }

        json_out(['ok' => true, 'stats' => $summary['stats'], 'hooks' => $summary['hooks'],
                  'time' => $summary['time'], 'detail' => $detail]);
    }],

    // --- Endpoint webhook publik (dipanggil layanan eksternal) ---
    ['POST', '#^/hook/([a-f0-9]{32})$#', function (string $token) {
        webhook_process($token);
    }],

    // --- Pengaturan ---
    ['GET', '#^/settings$#', function () {
        require_login();
        view('settings', ['user' => current_user()]);
    }],
    ['POST', '#^/settings$#', function () {
        require_login();
        verify_csrf();
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $user = current_user();

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        if (!password_verify($current, (string)$stmt->fetchColumn())) {
            flash('danger', 'Password saat ini salah.');
            redirect('/settings');
        }
        if (strlen($new) < 8) {
            flash('danger', 'Password baru minimal 8 karakter.');
            redirect('/settings');
        }
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Password berhasil diubah.');
        redirect('/settings');
    }],
];

// ---------------------------------------------------------------------------
// Cocokkan rute
// ---------------------------------------------------------------------------
foreach ($routes as [$rMethod, $pattern, $handler]) {
    if ($rMethod !== $method) {
        continue;
    }
    if (preg_match($pattern, $path, $m)) {
        $handler(...array_slice($m, 1));
        exit;
    }
}

http_response_code(404);
view('errors/404', ['user' => current_user()]);
