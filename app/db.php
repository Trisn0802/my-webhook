<?php
/** Koneksi PDO SQLite + skema database. */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/app.sqlite';

    try {
        $pdo = new PDO('sqlite:' . $file, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Gagal membuka database: ' . htmlspecialchars($e->getMessage());
        exit;
    }

    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = ON');
    db_migrate($pdo);
    db_add_missing_columns($pdo);

    return $pdo;
}

/**
 * Tambah kolom baru ke tabel lama tanpa menghapus data.
 * Idempoten — hanya menambah bila kolom belum ada.
 */
function db_add_missing_columns(PDO $pdo): void
{
    $wanted = [
        // tabel => [kolom => definisi]
        'hooks' => [
            // Custom field per service (JSON: [{"label":"","value":""}, ...]), maks 6 pasang.
            'custom_fields' => "TEXT NOT NULL DEFAULT '[]'",
        ],
    ];

    foreach ($wanted as $table => $columns) {
        foreach ($columns as $column => $definition) {
            $existing = [];
            foreach ($pdo->query("PRAGMA table_info(" . $table . ")") as $col) {
                $existing[] = $col['name'];
            }
            if (!in_array($column, $existing, true)) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        }
    }
}

function db_migrate(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS hooks (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name       TEXT NOT NULL,
    token      TEXT NOT NULL UNIQUE,
    bot_token  TEXT NOT NULL,
    chat_id    TEXT NOT NULL,
    format     TEXT NOT NULL DEFAULT 'json',
    is_active  INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL
);CREATE TABLE IF NOT EXISTS deliveries (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    hook_id     INTEGER NOT NULL REFERENCES hooks(id) ON DELETE CASCADE,
    status      TEXT NOT NULL,
    http_status INTEGER,
    detail      TEXT,
    payload     TEXT,
    created_at  TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS failed_logins (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    atk_key    TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_hooks_token   ON hooks(token);
CREATE INDEX IF NOT EXISTS idx_hooks_user    ON hooks(user_id);
CREATE INDEX IF NOT EXISTS idx_deliveries_hook ON deliveries(hook_id, id DESC);
SQL);
}

/** Batasi jumlah log per hook (tanpa cron). */
function prune_deliveries(int $hookId): void
{
    $keep = (int)LOG_RETENTION;
    db()->prepare(
        'DELETE FROM deliveries
          WHERE hook_id = ?
            AND id NOT IN (
                SELECT id FROM deliveries WHERE hook_id = ? ORDER BY id DESC LIMIT ?
            )'
    )->execute([$hookId, $hookId, $keep]);
}
