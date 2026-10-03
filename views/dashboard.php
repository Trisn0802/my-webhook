<?php $title = 'Dashboard'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1">Dashboard</h1>
    <p class="text-body-secondary mb-0">Halo, <strong><?= e($user['username']) ?></strong>.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_path('/hooks/new')) ?>">
    + Hook Baru
  </a>
</div>

<!-- Tambah Service -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><span class="bi bi-plus-circle me-2" aria-hidden="true"></span>Tambah Service</span>
    <button class="btn btn-sm btn-outline-primary" type="button"
            data-bs-toggle="collapse" data-bs-target="#addService"
            aria-expanded="<?= $hooks ? 'false' : 'true' ?>" aria-controls="addService">
      <?= $hooks ? 'Buka' : 'Tutup' ?>
    </button>
  </div>

  <div class="collapse <?= $hooks ? '' : 'show' ?>" id="addService">
    <div class="card-body">
      <p class="small text-body-secondary mb-3">
        Satu service = satu webhook menuju Telegram. Isi nama layanan, kredensial bot,
        lalu arahkan layanan tersebut ke URL endpoint yang muncul setelah disimpan.
      </p>

      <form method="post" action="<?= e(base_path('/hooks/new')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="row g-3 mb-3">
          <div class="col-12 col-lg-4">
            <label class="form-label" for="s_name">Nama Service</label>
            <input class="form-control" id="s_name" name="name" type="text" maxlength="60"
                   placeholder="mis. Uptime Kuma" required list="serviceSuggestions"
                   autocomplete="off">
          </div>
          <div class="col-12 col-lg-4">
            <label class="form-label" for="s_bot_token">Bot Token</label>
            <input class="form-control font-monospace" id="s_bot_token" name="bot_token"
                   type="text" placeholder="123456789:AA..." required>
          </div>
          <div class="col-12 col-lg-4">
            <label class="form-label" for="s_chat_id">Chat ID</label>
            <input class="form-control font-monospace" id="s_chat_id" name="chat_id"
                   type="text" inputmode="numeric" placeholder="-1001234567890" required>
          </div>
        </div>

        <div class="row g-3 align-items-end mb-4">
          <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label" for="s_format">Format Pesan</label>
            <select class="form-select" id="s_format" name="format">
              <option value="json">JSON penuh (detail)</option>
              <option value="text">Teks ringkas (field utama saja)</option>
            </select>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="s_active"
                     name="is_active" value="1" checked>
              <label class="form-check-label" for="s_active">Service aktif</label>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <button class="btn btn-primary w-100" type="submit">
              <span class="bi bi-save me-1" aria-hidden="true"></span>Simpan Service
            </button>
          </div>
        </div>

        <?php
          $customPrefix = 's_';
          require __DIR__ . '/hooks/_custom_fields.php';
        ?>

        <div class="small text-body-secondary mb-0 mt-3">
          <span class="bi bi-shield me-1" aria-hidden="true"></span>
          Bot Token &amp; Chat ID hanya untuk service ini, dan disimpan lokal di SQLite.
        </div>
      </form>

      <?php require __DIR__ . '/hooks/_suggestions.php'; ?>
    </div>
  </div>
</div>

<!-- Statistik -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="bi bi-link-45deg text-info p-2 rounded" style="font-size: 24px; background-color: #1a1d20;" aria-hidden="true"></span>
        <div>
          <div class="fs-3 fw-semibold lh-sm" data-live-stat="hooks"><?= (int)$stats['hooks'] ?></div>
          <div class="text-body-secondary small">Total hook</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="bi bi-send-fill text-white p-2 rounded" style="font-size: 24px; background-color: #1a1d20;" aria-hidden="true"></span>
        <div>
          <div class="fs-3 fw-semibold lh-sm" data-live-stat="today"><?= (int)$stats['today'] ?></div>
          <div class="text-body-secondary small">Webhook hari ini</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="bi bi-exclamation-triangle-fill text-warning p-2 rounded" style="font-size: 24px; background-color: #1a1d20;" aria-hidden="true"></span>
        <div>
          <div class="fs-3 fw-semibold lh-sm <?= ((int)$stats['failed'] > 0) ? 'text-danger' : '' ?>"
               data-live-stat="failed"><?= (int)$stats['failed'] ?></div>
          <div class="text-body-secondary small">Gagal hari ini</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Daftar hook -->
<h2 class="h5 mb-3">Hook</h2>

<?php if (!$hooks): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <span class="bi bi-broadcast d-block mb-3 text-secondary" style="font-size:2.75rem" aria-hidden="true"></span>
      <p class="mb-3">Belum ada hook. Buat satu untuk mulai menerima webhook.</p>
      <a class="btn btn-primary" href="<?= e(base_path('/hooks/new')) ?>">Buat hook pertama</a>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($hooks as $h): ?>
      <?php $endpoint = absolute_url('/hook/' . $h['token']); ?>
      <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 hook-card" data-hook-card="<?= (int)$h['id'] ?>">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <h3 class="h6 mb-0 text-truncate" title="<?= e($h['name']) ?>"><?= e($h['name']) ?></h3>
              <span class="badge flex-shrink-0 <?= ((int)$h['is_active'] === 1) ? 'text-bg-success' : 'text-bg-secondary' ?>"
                    data-live-status><?= ((int)$h['is_active'] === 1) ? 'Aktif' : 'Nonaktif' ?></span>
            </div>

            <div class="input-group input-group-sm mb-3">
              <input class="form-control form-control-sm font-monospace endpoint-input"
                     type="text" readonly value="<?= e($endpoint) ?>"
                     aria-label="URL endpoint">
              <button class="btn btn-outline-secondary btn-copy" type="button"
                      data-copy="<?= e($endpoint) ?>" title="Salin URL">Salin</button>
            </div>

            <div class="d-flex justify-content-between small text-body-secondary mb-3">
              <span data-live-total="<?= (int)$h['total'] ?>"><strong><?= (int)$h['total'] ?></strong> kirim</span>
              <span data-live-last="<?= e($h['last_at'] ?? '') ?>">
                <?= $h['last_at'] ? 'terakhir ' . e(time_ago($h['last_at'])) : 'belum ada' ?>
              </span>
            </div>

            <div class="d-flex gap-2">
              <a class="btn btn-sm btn-outline-primary flex-grow-1"
                 href="<?= e(base_path('/hooks/' . $h['id'])) ?>">Detail</a>
              <form method="post" action="<?= e(base_path('/hooks/' . $h['id'] . '/clone')) ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary" type="submit" title="Clone hook">
                  <i class="bi bi-copy" aria-hidden="true"></i>
                  <span class="visually-hidden">Clone</span>
                </button>
              </form>
              <a class="btn btn-sm btn-outline-secondary"
                 href="<?= e(base_path('/hooks/' . $h['id'] . '/edit')) ?>">Edit</a>
              <form method="post" action="<?= e(base_path('/hooks/' . $h['id'] . '/delete')) ?>"
                    class="m-0" data-confirm-title="Hapus Hook"
                    data-confirm-message="Hapus hook &quot;<?= e($h['name']) ?>&quot; beserta semua log pengirimannya? Tindakan ini tidak dapat dibatalkan.">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
