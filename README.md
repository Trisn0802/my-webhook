# My Webhook 🪝

Aplikasi web **ringan** untuk home server: menerima webhook dari layanan mana pun
(GitHub, Uptime Kuma, monitoring, router, dsb) lalu meneruskannya ke **Telegram**.

- **PHP murni** — tanpa Composer, tanpa framework, tanpa proses antrian
- **SQLite** — satu file, tanpa layanan database terpisah
- **Bootstrap 5.3 lokal** — bekerja offline, tema **terang & gelap**, responsif
- **Login & register** — CSRF, rate-limit login, hash password

Berjalan di **Laragon**, **`php -S`**, maupun **Docker**.

---

## Cara Kerja

```
Layanan eksternal ──POST JSON──▶ /hook/{token} ──▶ Telegram Bot API
                                    │
                                    └──▶ log pengiriman (SQLite)
```

1. Buat bot Telegram lewat [@BotFather](https://t.me/BotFather) → salin **Bot Token**.
2. Dapatkan **Chat ID**: kirim pesan ke bot Anda, lalu buka
   `https://api.telegram.org/bot<TOKEN>/getUpdates` — `result[*].message.chat.id`
   adalah Chat ID Anda (untuk grup/channel berawalan `-100…`).
3. Di panel, buat **Hook** dengan token dan chat id tersebut.
4. Arahkan webhook layanan eksternal ke URL endpoint yang muncul, misal:
   `http://alamat-server/hook/1a2b3c…`

---

## Menjalankan

### A. Laragon (Windows)

Letakkan folder ini di `C:\laragon\www\my-webhook`, nyalakan **Apache**, lalu buka
`http://my-webhook.test` atau `http://localhost/my-webhook`.
`.htaccess` sudah dikonfigurasi.

### B. PHP built-in server (port 3010)

```bash
php -S 127.0.0.1:3010 router.php
```

Lalu buka `http://127.0.0.1:3010`.

### C. Docker

```bash
docker compose up --build
```

Aplikasi tersedia di `http://localhost:3010`. Database tersimpan di folder `./data`,
sehingga bertahan saat container diganti (rebuild).

---

## Balik Proxy (Nginx Proxy Manager)

Aplikasi ini tidak mengelola sertifikat sendiri — biar NPM yang menangani TLS.
NPM memetakan `https://domain-anda` ke container ini.

| NPM — Advanced | Nilai |
|---|---|
| `client_max_body_size` | `1m` (payload webhook biasanya kecil) |
| `proxy_hide_header` | *(tidak perlu)* |

Catatan penting untuk NPM:

- **Domain tidak perlu di-hardcode.** Tombol Salin otomatis membaca host &
  protokol dari header proxy, jadi endpoint tampil sebagai
  `https://domain-anda/hook/…`. Isi `PUBLIC_BASE_URL` di `config.php` hanya bila
  proxy Anda mengganti header Host.
- NPM otomatis mengirim `X-Forwarded-Proto: https`, dan aplikasi menggunakannya
  untuk menandai cookie session sebagai `Secure`.
- Webhook eksternal memanggil **POST `/hook/{token}`** — pastikan path `/hook/*`
  tidak di-blokir oleh custom location mana pun di NPM.
- Dalam Docker, gunakan **`http://my-webhook`** (nama service) sebagai Forward Host
  dengan **Forward Port 80**, bukan `localhost`.

Contoh: `https://hook.example.com` → `http://my-webhook:80`.

---

## Struktur

```
index.php      front controller + tabel rute
router.php     router untuk php -S
config.php     pengaturan aplikasi
app/           bootstrap, db, auth, hooks, telegram, webhook, helpers
views/         template (layout + halaman + partial _custom_fields, _suggestions)
assets/        Bootstrap CSS/JS lokal, Bootstrap Icons (npm/font), CSS, JS, dan logo
data/          app.sqlite (dibuat otomatis, jangan ikut di-commit)
```

## Fitur Panel

- **Tambah Service** — kartu form di atas dashboard: nama, bot token, chat id,
  format pesan, status aktif.
- **Custom Field** maks. **6 pasang** per service (nama + nilai). Ditampilkan di
  awal setiap pesan Telegram; baris kosong diabaikan, validasi dilakukan server.
- **Saran nama** — daftar layanan umum (Uptime Kuma, Grafana, GitHub, dsb) muncul
  saat mengetik di field Nama.
- **Tema terang/gelap** tersimpan di peramban dan dipakai ulang saat muat ulang
  (tanpa kilatan terang). Ikon memakai **Bootstrap Icons lokal** melalui font
  `woff2` dari npm — semua kelas resmi `bi bi-...` langsung tersedia tanpa CDN.
- Untuk mengubah ikon, buka [Bootstrap Icons](https://icons.getbootstrap.com), cari
  nama ikon, lalu pakai misalnya `<i class="bi bi-house-door"></i>` di view.
  Jalankan `npm install` setelah clone, lalu `npm run icons` untuk menyalin font/CSS
  paket ke `assets/vendor` (aplikasi tetap bekerja offline setelahnya).
  Sumber resmi di `node_modules/bootstrap-icons/` (2.078 ikon SVG tersedia).
- Logo yang dikirim pengguna disimpan di `assets/img/logo.webp` dan dipakai sebagai
  identitas navbar serta favicon.
- **Log pengiriman** — status tiap request masuk, payload dapat dibuka-tutup.

## Konfigurasi (`config.php`)

| Konstanta | Fungsi |
|---|---|
| `REGISTRATION_OPEN` | `true` = registrasi terbuka; isi **string** (mis. `'rahasia123'`) = registrasi butuh kode; `false` = ditutup. |
| `APP_NAME` | Nama tampilan. |
| `LOG_RETENTION` | Jumlah log per hook yang disimpan (default 200). |
| `TIMEZONE` | Zona waktu tampilan (default `Asia/Jakarta`). |
| `PUBLIC_BASE_URL` | URL absolut dasar untuk endpoint. Kosong `''` = deteksi otomatis dari host permintaan (sudah benar di balik Nginx Proxy Manager). |

> **Keamanan:** bila port diekspos ke internet, set `REGISTRATION_OPEN` ke kode
> agar akun asing tidak bisa dibuat sembarangan.

## Keamanan yang sudah terpasang

- Password di-hash dengan `password_hash()` (bcrypt)
- CSRF token di seluruh POST internal (endpoint `/hook/{token}` sengaja publik)
- Rate-limit login: 10 percobaan gagal per username+IP dalam 15 menit
- Token endpoint 32 karakter acak (`random_bytes`) → tidak bisa ditebak
- Output di-escape; folder `app/`, `data/`, `views/` diblokir dari akses web
- Session cookie `HttpOnly` + `SameSite=Lax` (+ `Secure` bila HTTPS)

## Catatan Telegram

- Batas kirim Telegram: **20 pesan/detik per bot, ~30 pesan/menit per chat**.
  Untuk webhook yang sangat deras, beri jeda / gabungkan pesan di pengirimnya.
- Pesan dikirim sebagai **teks biasa** (tanpa `parse_mode`) agar karakter
  khusus dari payload webhook tidak merusak format.

## Uji Cepat

```bash
# buat hook lewat panel, salin URL-nya, lalu:
curl -X POST "http://127.0.0.1:3010/hook/TOKEN_ANDA" \
  -H "Content-Type: application/json" \
  -d '{"message":"halo dari curl"}'
```

Harus membalas `{"ok":true,...}` dan pesan muncul di Telegram.
