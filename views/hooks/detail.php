<?php
$title = $hook['name'];
$endpoint = absolute_url('/hook/' . $hook['token']);
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4" data-live-hook="<?= (int)$hook['id'] ?>">
  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_path("/dashboard")) ?>"><span class="bi bi-arrow-left me-1" aria-hidden="true"></span>Kembali</a>
  <h1 class="h3 mb-0 me-auto"><?= e($hook['name']) ?></h1>
  <span class="badge <?= ((int)$hook['is_active'] === 1) ? 'text-bg-success' : 'text-bg-secondary' ?>"
        data-live-status><?= ((int)$hook['is_active'] === 1) ? 'Aktif' : 'Nonaktif' ?></span>
  <a class="btn btn-sm btn-outline-primary" href="<?= e(base_path('/hooks/' . $hook['id'] . '/edit')) ?>">Edit</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div class="card-header fw-semibold">Endpoint Webhook</div>
      <div class="card-body">
        <div class="input-group mb-3">
          <input class="form-control font-monospace" type="text" readonly
                 value="<?= e($endpoint) ?>" aria-label="URL endpoint">
          <button class="btn btn-outline-secondary btn-copy" type="button"
                  data-copy="<?= e($endpoint) ?>">Salin</button>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-outline-primary" type="button"
                  data-test-url="<?= e(base_path('/hooks/' . $hook['id'] . '/test')) ?>">
            Kirim Pesan Test
          </button>
          <span class="align-self-center small text-body-secondary" id="testResult"></span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold">Informasi</div>
      <div class="card-body small">
        <dl class="row mb-0">
          <dt class="col-5">Format</dt>
          <dd class="col-7"><?= $hook['format'] === 'text' ? 'Teks ringkas' : 'JSON penuh' ?></dd>
          <dt class="col-5">Dibuat</dt>
          <dd class="col-7"><?= e($hook['created_at']) ?></dd>
          <dt class="col-5">Chat ID</dt>
          <dd class="col-7 font-monospace text-truncate" title="<?= e($hook['chat_id']) ?>">
            <?= e($hook['chat_id']) ?>
          </dd>
        </dl>
      </div>
    </div>
  </div>
</div>

<h2 class="h5 mb-3">Log Pengiriman</h2>

<?php if (!$logs): ?>
  <div class="card" id="logEmpty">
    <div class="card-body text-center text-body-secondary py-5">
      Belum ada request masuk ke hook ini.
    </div>
  </div>
  <div class="d-flex flex-column gap-3 d-none" id="logList"></div>
<?php else: ?>
  <div class="card d-none mb-3" id="logEmpty">
    <div class="card-body text-center text-body-secondary py-5">
      Belum ada request masuk ke hook ini.
    </div>
  </div>
  <div class="d-flex flex-column gap-3" id="logList">
    <?php foreach ($logs as $log): ?>
      <?php require __DIR__ . '/_log_item.php'; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
