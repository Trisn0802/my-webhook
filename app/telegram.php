<?php
/** Kirim pesan ke Telegram Bot API. */

function telegram_send(string $botToken, string $chatId, string $text): array
{
    $botToken = trim($botToken);
    $chatId = trim($chatId);

    if ($botToken === '' || $chatId === '') {
        return [false, 'Bot Token atau Chat ID belum diisi.'];
    }
    if (!preg_match('/^\d+:[A-Za-z0-9_-]{20,}$/', $botToken)) {
        return [false, 'Format Bot Token tidak valid.'];
    }

    // Batas pesan Telegram adalah 4096 karakter.
    if (mb_strlen($text) > 4096) {
        $text = mb_substr($text, 0, 4093) . '...';
    }

    $url = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $payload = [
        'chat_id' => $chatId,
        'text'    => $text,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            return [false, 'Koneksi ke Telegram gagal: ' . $err];
        }
    } else {
        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 10,
        ]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return [false, 'Koneksi ke Telegram gagal.'];
        }
    }

    $res = json_decode((string)$body, true);
    if (!is_array($res) || empty($res['ok'])) {
        $desc = $res['description'] ?? ('Respons tidak valid: ' . mb_substr((string)$body, 0, 200));
        return [false, $desc];
    }

    return [true, 'Terkirim'];
}

/** Test kredensial tanpa mengirim pesan (getMe). */
function telegram_verify(string $botToken): array
{
    $botToken = trim($botToken);
    if ($botToken === '') {
        return [false, 'Bot Token belum diisi.'];
    }
    $url = 'https://api.telegram.org/bot' . $botToken . '/getMe';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $body = @file_get_contents($url);
    }
    $res = json_decode((string)$body, true);
    if (!is_array($res) || empty($res['ok'])) {
        return [false, is_array($res) ? ($res['description'] ?? 'Gagal memeriksa bot.') : 'Tidak bisa menghubungi Telegram.'];
    }
    return [true, '@' . ($res['result']['username'] ?? '?')];
}
