<?php
/**
 * Layout utama. Variabel yang tersedia:
 *   $content  — isi halaman (dari view)
 *   $user     — (opsional) data user login / null
 */
$u = $user ?? null;
$pageTitle = isset($title) ? $title . ' · ' . APP_NAME : APP_NAME;
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= e($pageTitle) ?></title>
<link rel="icon" href="<?= e(base_path('/assets/img/logo.webp')) ?>" type="image/webp">
<link rel="stylesheet" href="<?= e(base_path('/assets/vendor/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(base_path('/assets/vendor/bootstrap-icons-font/bootstrap-icons.min.css')) ?>">
<link rel="stylesheet" href="<?= e(base_path('/assets/css/app.css?v=' . filemtime('assets/css/app.css'))) ?>">
<script>
// Terapkan tema sebelum paint agar tidak ada kilatan terang.
(function () {
  try {
    var t = localStorage.getItem('theme');
    if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', t);
  } catch (e) {}
})();
</script>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg border-bottom bg-body-tertiary sticky-top">
  <div class="container">
    <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?= e(base_path($u ? '/dashboard' : '/')) ?>">
      <img src="<?= e(base_path('/assets/img/logo.webp')) ?>" alt="" width="28" height="28"
           class="brand-logo" aria-hidden="true">
      <?= e(APP_NAME) ?>
    </a>

    <div class="d-flex align-items-center gap-2 order-lg-3">
      <button class="btn btn-sm btn-outline-secondary" id="themeToggle" type="button"
              title="Ganti tema" aria-label="Ganti tema terang/gelap">
        <span class="bi bi-moon-fill" aria-hidden="true"></span>
      </button>

      <?php if ($u): ?>
      <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button"
              data-bs-toggle="collapse" data-bs-target="#mainNav" aria-expanded="false" aria-controls="mainNav">
        <span class="bi bi-list" aria-hidden="true"></span>
      </button>
      <?php endif; ?>
    </div>

    <?php if ($u): ?>
    <div class="collapse navbar-collapse order-lg-2" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?= e(base_path('/dashboard')) ?>">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= e(base_path('/hooks/new')) ?>">Hook Baru</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= e(base_path('/settings')) ?>">Pengaturan</a>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <span class="navbar-text small text-body-secondary"><?= e($u['username']) ?></span>
        <form method="post" action="<?= e(base_path('/logout')) ?>" class="m-0" data-confirm-title="Logout" data-confirm-message="Apakah Anda yakin ingin logout?">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" type="submit" style="margin-right: 7px;"
                  title="Logout" aria-label="Logout">
            <span class="bi bi-box-arrow-right" aria-hidden="true"></span>
            <span class="d-none d-sm-inline">Logout</span>
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</nav>

<main class="container py-4 flex-grow-1">
  <?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
      <?= e($f['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endforeach; ?>

  <?= $content ?>
</main>

<footer class="border-top py-3 text-body-secondary small">

  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <div class="align-item-center gap-2">
      <?= e(APP_NAME) ?> · webhook ringan untuk Telegram
    </div>
    
    <div class="align-item-center gap-2">
      Made by <a class="link-body-emphasis link-offset-2 link-underline-opacity-25 link-underline-opacity-75-hover" href="https://trisna-info.pages.dev" target="_blank" rel="noopener noreferrer">Trisna Almuti</a>
    </div>
  </div>
  
</footer>

<?php if ($u): ?>
<!-- Modal konfirmasi generik (logout / hapus hook) -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-6" id="confirmModalLabel">Konfirmasi</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body" id="confirmModalMessage">
        Apakah Anda yakin?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="confirmModalOk">Ya, lanjutkan</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="<?= e(base_path('/assets/vendor/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(base_path('/assets/js/app.js?v=' . filemtime('assets/js/app.js'))) ?>"></script>
</body>
</html>
