<?php
/** Menangani webhook masuk: log → format → kirim ke Telegram. */

/** Baca body request apa pun (JSON, form, atau teks mentah). */
function webhook_raw_body(): string
{
    static $raw = null;
    if ($raw === null) {
        $raw = (string)file_get_contents('php://input', false, null, 0, 65536);
    }
    return $raw;
}

/** Payload terstruktur untuk disimpan (dipotong agar hemat ruang). */
function webhook_payload_summary(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        $data = $_POST;
        if ($data) {
            $raw = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        } else {
            $raw = '(kosong)';
        }
    }

    // Coba rapikan JSON agar log enak dibaca.
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $raw = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $raw;
    }

    if (mb_strlen($raw) > 4096) {
        $raw = mb_substr($raw, 0, 4096) . "\n… (dipotong)";
    }
    return $raw;
}

/**
 * Blok custom field (info statik per service) untuk awal pesan.
 * @return string '' bila tidak ada.
 */
function webhook_custom_block(array $hook): string
{
    $fields = hook_custom_fields($hook['custom_fields'] ?? '');
    if (!$fields) {
        return '';
    }
    $lines = [];
    foreach ($fields as $f) {
        $lines[] = $f['label'] . ': ' . $f['value'];
    }
    return implode("\n", $lines) . "\n\n";
}

/**
 * Susun teks pesan sesuai format hook.
 * 'json'  = tampilkan JSON penuh
 * 'text'  = ekstrak field umum agar ringkas
 * Custom field selalu tampil di awal pesan.
 */
function webhook_format_message(array $hook, string $raw): string
{
    $header = '[' . $hook['name'] . ']';
    $custom = webhook_custom_block($hook);

    if ($hook['format'] !== 'text') {
        return $header . "\n" . $custom . $raw;
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $fields = ['title', 'subject', 'message', 'text', 'body', 'event', 'status', 'host', 'service', 'description'];
        $lines = [];
        foreach ($fields as $f) {
            if (isset($decoded[$f]) && is_scalar($decoded[$f])) {
                $lines[] = $f . ': ' . (string)$decoded[$f];
            }
        }
        if ($lines) {
            return $header . "\n" . $custom . implode("\n", $lines);
        }
        // Fallback: JSON miring satu baris.
        $flat = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $header . "\n" . $custom . ($flat ?: $raw);
    }

    return $header . "\n" . $custom . $raw;
}

/**
 * Catat pengiriman ke log.
 * @return int id log
 */
function webhook_log(int $hookId, string $status, ?int $httpStatus, string $detail, string $payload): int
{
    db()->prepare(
        'INSERT INTO deliveries (hook_id, status, http_status, detail, payload, created_at)
         VALUES (?,?,?,?,?,?)'
    )->execute([$hookId, $status, $httpStatus, mb_substr($detail, 0, 500), $payload, now()]);
    prune_deliveries($hookId);
    return (int)db()->lastInsertId();
}

/**
 * Proses webhook masuk.
 * Selalu membalas 200 setelah logging agar pengirim eksternal tidak retry
 * tanpa henti; kegagalan Telegram tetap tercatat di log.
 */
function webhook_process(string $token): never
{
    $hook = hook_find_by_token($token);
    if (!$hook) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    if ((int)$hook['is_active'] !== 1) {
        json_out(['ok' => false, 'error' => 'disabled'], 410);
    }

    $raw = webhook_payload_summary(webhook_raw_body());
    $message = webhook_format_message($hook, $raw);

    [$ok, $info] = telegram_send($hook['bot_token'], $hook['chat_id'], $message);

    webhook_log(
        (int)$hook['id'],
        $ok ? 'sent' : 'failed',
        200,
        $ok ? 'Terkirim ke Telegram' : $info,
        $raw
    );

    json_out([
        'ok'     => $ok,
        'status' => $ok ? 'sent' : 'failed',
        'error'  => $ok ? null : $info,
    ]);
}

/**
 * Kirim pesan percobaan dari panel.
 * @return array [bool ok, string message]
 */
function webhook_test(array $hook): array
{
    $text = "[Test] Hook \"" . $hook['name'] . "\" terhubung.\n"
          . webhook_custom_block($hook)
          . "Waktu: " . date('Y-m-d H:i:s') . " (" . TIMEZONE . ")";
    [$ok, $info] = telegram_send($hook['bot_token'], $hook['chat_id'], $text);
    webhook_log((int)$hook['id'], $ok ? 'sent' : 'failed', 200, 'Pesan test: ' . $info, '(pesan test)');
    return [$ok, $info];
}
