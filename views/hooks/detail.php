<?php
$title = $hook['name'];
$endpoint = absolute_url('/hook/' . $hook['token']);
$logs = $paged['logs'];
$totalLogs = (int)$paged['total'];
$currentPage = (int)$paged['page'];
$totalPages = (int)$paged['pages'];
$perPage = (int)$paged['per_page'];
$filter = $filter ?? ['q' => '', 'status' => 'all', 'date_from' => '', 'date_to' => '', 'page' => 1, 'per_page' => 10];
$fromItem = $totalLogs ? (($currentPage - 1) * $perPage + 1) : 0;
$toItem = min($currentPage * $perPage, $totalLogs);
$logQuery = static function (int $page) use ($hook, $filter, $perPage): string {
    return http_build_query([
        'q' => $filter['q'],
        'status' => $filter['status'],
        'from' => $filter['date_from'],
        'to' => $filter['date_to'],
        'per_page' => $perPage,
        'page' => $page,
    ]);
};
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4" data-live-hook="<?= (int)$hook['id'] ?>">
  <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_path("/dashboard")) ?>"><span class="bi bi-arrow-left me-1" aria-hidden="true"></span>Kembali</a>
  <h1 class="h3 mb-0 me-auto"><?= e($hook['name']) ?></h1>
  <span class="badge <?= ((int)$hook['is_active'] === 1) ? 'text-bg-success' : 'text-bg-secondary' ?>"
        data-live-status><?= ((int)$hook['is_active'] === 1) ? 'Aktif' : 'Nonaktif' ?></span>
  <form method="post" action="<?= e(base_path('/hooks/' . $hook['id'] . '/clone')) ?>" class="m-0">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" type="submit"
            title="Clone hook">
      <i class="bi bi-copy" aria-hidden="true"></i>Clone
    </button>
  </form>
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

<?php
$hasFilter = ($filter['q'] !== '') || $filter['status'] !== 'all'
    || $filter['date_from'] !== '' || $filter['date_to'] !== '';
?>
<h2 class="h5 mb-3">Log Pengiriman</h2>

<!-- Filter log -->
<form method="get" action="<?= e(base_path('/hooks/' . $hook['id'])) ?>" class="card mb-3" id="logFilterForm">
  <div class="card-body">
    <input type="hidden" name="page" value="1">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-3">
        <label class="form-label small mb-1" for="log_q">Cari</label>
        <input class="form-control form-control-sm" id="log_q" name="q" type="search"
               value="<?= e($filter['q']) ?>" placeholder="Payload / detail…" autocomplete="off">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="log_status">Status</label>
        <select class="form-select form-select-sm" id="log_status" name="status">
          <option value="all" <?= $filter['status'] === 'all' ? 'selected' : '' ?>>Semua</option>
          <option value="sent" <?= $filter['status'] === 'sent' ? 'selected' : '' ?>>Terkirim</option>
          <option value="failed" <?= $filter['status'] === 'failed' ? 'selected' : '' ?>>Gagal</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="log_from">Dari tanggal</label>
        <input class="form-control form-control-sm" id="log_from" name="from" type="date"
               value="<?= e($filter['date_from']) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="log_to">Sampai</label>
        <input class="form-control form-control-sm" id="log_to" name="to" type="date"
               value="<?= e($filter['date_to']) ?>">
      </div>
      <div class="col-6 col-md-1">
        <label class="form-label small mb-1" for="log_per">Per halaman</label>
        <select class="form-select form-select-sm" id="log_per" name="per_page">
          <?php foreach (HOOK_LOG_PER_PAGE as $n): ?>
            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2 d-grid">
        <button class="btn btn-sm btn-primary" type="submit">Cari</button>
      </div>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
      <small class="text-body-secondary" id="logSummary">
        Menampilkan <?= $fromItem ?>–<?= $toItem ?> dari <?= $totalLogs ?> log
      </small>
      <?php if ($hasFilter || $currentPage > 1): ?>
        <a class="btn btn-sm btn-outline-secondary"
           href="<?= e(base_path('/hooks/' . $hook['id'])) ?>">Reset</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if (!$logs && !$hasFilter): ?>
  <div class="card mb-3" id="logEmpty">
    <div class="card-body text-center text-body-secondary py-5">
      Belum ada request masuk ke hook ini.
    </div>
  </div>
<?php elseif (!$logs): ?>
  <div class="card mb-3" id="logEmpty">
    <div class="card-body text-center text-body-secondary py-5">
      Tidak ada log yang cocok dengan filter saat ini.
    </div>
  </div>
<?php else: ?>
  <div class="card mb-3 d-none" id="logEmpty">
    <div class="card-body text-center text-body-secondary py-5"></div>
  </div>
<?php endif; ?>

<div class="d-flex flex-column gap-3 <?= $logs ? '' : 'd-none' ?>" id="logList">
  <?php foreach ($logs as $log): ?>
    <?php require __DIR__ . '/_log_item.php'; ?>
  <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
  <nav class="mt-3" aria-label="Halaman log pengiriman" id="logPager">
    <ul class="pagination pagination-sm flex-wrap justify-content-center mb-0">
      <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(base_path('/hooks/' . $hook['id'] . '?' . $logQuery($currentPage - 1))) ?>"
           aria-label="Sebelumnya">&lsaquo; Sebelumnya</a>
      </li>

      <?php
      $window = range(max(1, $currentPage - 1), min($totalPages, $currentPage + 1));
      $printed = 0;
      foreach ($window as $n):
          if ($printed > 0 && $n > $window[0] + 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif;
          $printed++; ?>
        <li class="page-item <?= $n === $currentPage ? 'active' : '' ?>">
          <a class="page-link" href="<?= e(base_path('/hooks/' . $hook['id'] . '?' . $logQuery($n))) ?>"><?= $n ?></a>
        </li>
      <?php endforeach; ?>

      <?php if ($totalPages > $window[count($window) - 1]): ?>
        <li class="page-item disabled"><span class="page-link">…</span></li>
        <li class="page-item">
          <a class="page-link" href="<?= e(base_path('/hooks/' . $hook['id'] . '?' . $logQuery($totalPages))) ?>"><?= $totalPages ?></a>
        </li>
      <?php endif; ?>

      <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(base_path('/hooks/' . $hook['id'] . '?' . $logQuery($currentPage + 1))) ?>"
           aria-label="Berikutnya">Berikutnya &rsaquo;</a>
      </li>
    </ul>
  </nav>
<?php else: ?>
  <nav class="mt-3" aria-label="Halaman log pengiriman" class="d-none" id="logPager"></nav>
<?php endif; ?>
