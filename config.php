<?php
/**
 * Konfigurasi aplikasi. Edit sesuai kebutuhan.
 */

// Registrasi terbuka: true = siapa pun bisa mendaftar.
// Isi string (misal 'rahasia123') = registrasi wajib memakai kode tersebut.
const REGISTRATION_OPEN = true;

// Tautan di logo akan mengarah ke sini.
const APP_NAME = 'My Webhook';

// Jumlah log pengiriman yang disimpan per hook.
const LOG_RETENTION = 200;

// Zona waktu untuk menampilkan waktu (misal 'Asia/Jakarta').
const TIMEZONE = 'Asia/Jakarta';

// URL publik yang tampil di panel (untuk disalin ke layanan eksternal).
// Kosongkan '' = deteksi otomatis dari host permintaan (aman di balik Nginx Proxy
// Manager). Isi eksplisit bila di belakang proxy yang mengganti header Host:
//   const PUBLIC_BASE_URL = 'https://hook.contoh.com';
const PUBLIC_BASE_URL = '';
