<?php
/** CRUD hook + pengambilan log. */

function hook_new_token(): string
{
    return bin2hex(random_bytes(16));
}

function hook_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM hooks WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function hook_find_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT * FROM hooks WHERE token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function hooks_for_user(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT h.*,
                (SELECT COUNT(*) FROM deliveries d WHERE d.hook_id = h.id) AS total,
                (SELECT created_at FROM deliveries d WHERE d.hook_id = h.id ORDER BY d.id DESC LIMIT 1) AS last_at
           FROM hooks h
          WHERE h.user_id = ?
          ORDER BY h.id DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function hook_owned(int $id, int $userId): ?array
{
    $hook = hook_find($id);
    if (!$hook || (int)$hook['user_id'] !== $userId) {
        return null;
    }
    return $hook;
}

/**
 * Maksimum pasang custom field per service.
 */
const HOOK_MAX_CUSTOM_FIELDS = 6;

/**
 * Normalisasi custom field dari input form.
 * Input: custom_label[] dan custom_value[] (array, maks 6 pasang).
 * Output: array of ['label' => string, 'value' => string] yang sudah bersih.
 *
 * @return array [array fields, string|null error]
 */
function hook_normalize_custom_fields(array $in): array
{
    $labels = (array)($in['custom_label'] ?? []);
    $values = (array)($in['custom_value'] ?? []);

    $fields = [];
    $error = null;

    foreach ($labels as $i => $label) {
        $label = trim((string)$label);
        $value = trim((string)($values[$i] ?? ''));

        // Baris yang dikosongkan sepenuhnya diabaikan.
        if ($label === '' && $value === '') {
            continue;
        }
        if (count($fields) >= HOOK_MAX_CUSTOM_FIELDS) {
            $error = 'Custom field maksimal ' . HOOK_MAX_CUSTOM_FIELDS . ' pasang.';
            break;
        }
        if ($label === '') {
            $error = 'Nama custom field tidak boleh kosong.';
            break;
        }
        if (mb_strlen($label) > 40 || mb_strlen($value) > 200) {
            $error = 'Custom field: nama maks 40, nilai maks 200 karakter.';
            break;
        }

        $fields[] = ['label' => $label, 'value' => $value];
    }

    return [$fields, $error];
}

/** Decode kolom JSON custom field jadi array aman. */
function hook_custom_fields(?string $json): array
{
    if (!$json) {
        return [];
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }
    $out = [];
    foreach ($decoded as $row) {
        if (is_array($row) && isset($row['label'], $row['value'])) {
            $out[] = ['label' => (string)$row['label'], 'value' => (string)$row['value']];
        }
    }
    return $out;
}

/**
 * @return array [bool ok, string message]
 */
function hook_save(array $in, int $userId, ?int $id = null): array
{
    $name = trim($in['name'] ?? '');
    $botToken = trim($in['bot_token'] ?? '');
    $chatId = trim($in['chat_id'] ?? '');
    $format = ($in['format'] ?? 'json') === 'text' ? 'text' : 'json';
    $active = !empty($in['is_active']) ? 1 : 0;

    if ($name === '' || mb_strlen($name) > 60) {
        return [false, 'Nama hook wajib diisi (maks. 60 karakter).'];
    }
    if ($botToken === '') {
        return [false, 'Bot Token wajib diisi.'];
    }
    if (!preg_match('/^\d+:[A-Za-z0-9_-]{20,}$/', $botToken)) {
        return [false, 'Format Bot Token tidak valid (contoh: 123456789:AA...).'];
    }
    if ($chatId === '' || !preg_match('/^-?\d{1,20}$/', $chatId)) {
        return [false, 'Chat ID harus berupa angka.'];
    }

    [$customFields, $customError] = hook_normalize_custom_fields($in);
    if ($customError !== null) {
        return [false, $customError];
    }
    $customJson = json_encode($customFields, JSON_UNESCAPED_UNICODE) ?: '[]';

    if ($id === null) {
        db()->prepare(
            'INSERT INTO hooks (user_id, name, token, bot_token, chat_id, format, is_active, custom_fields, created_at)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([
            $userId, $name, hook_new_token(), $botToken, $chatId, $format, $active, $customJson, now(),
        ]);
        return [true, 'Hook berhasil dibuat.'];
    }

    db()->prepare(
        'UPDATE hooks SET name = ?, bot_token = ?, chat_id = ?, format = ?, is_active = ?, custom_fields = ?
          WHERE id = ?'
    )->execute([$name, $botToken, $chatId, $format, $active, $customJson, $id]);
    return [true, 'Hook berhasil diperbarui.'];
}

function hook_delete(int $id): void
{
    db()->prepare('DELETE FROM hooks WHERE id = ?')->execute([$id]);
}

function hook_log(int $hookId, int $limit = 50): array
{
    $stmt = db()->prepare(
        'SELECT * FROM deliveries WHERE hook_id = ? ORDER BY id DESC LIMIT ?'
    );
    $stmt->execute([$hookId, $limit]);
    return $stmt->fetchAll();
}

function hook_stats(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT
            (SELECT COUNT(*) FROM hooks WHERE user_id = ?) AS hooks,
            (SELECT COUNT(*) FROM deliveries d
               JOIN hooks h ON h.id = d.hook_id
              WHERE h.user_id = ? AND d.created_at >= ?) AS today,
            (SELECT COUNT(*) FROM deliveries d
               JOIN hooks h ON h.id = d.hook_id
              WHERE h.user_id = ? AND d.status = ? AND d.created_at >= ?) AS failed'
    );
    $stmt->execute([
        $userId,
        $userId, date('Y-m-d 00:00:00'),
        $userId, 'failed', date('Y-m-d 00:00:00'),
    ]);
    return $stmt->fetch() ?: ['hooks' => 0, 'today' => 0, 'failed' => 0];
}
