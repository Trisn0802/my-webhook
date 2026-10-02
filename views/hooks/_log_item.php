<?php
/**
 * Satu item log pengiriman.
 * Varsional: $log — baris tabel deliveries.
 * Markup ini harus dicerminkan oleh template JS di app.js (logItemHtml).
 */
$status = $log['status'] ?? '';
$ok = $status === 'sent';
?>
<div class="card log-card">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
      <div class="d-flex align-items-center gap-2">
        <span class="badge <?= $ok ? 'text-bg-success' : 'text-bg-danger' ?>">
          <?= $ok ? 'Terkirim' : 'Gagal' ?>
        </span>
        <small class="text-body-secondary"><?= e($log['created_at'] ?? '') ?></small>
        <small class="text-body-secondary">· <?= e(time_ago($log['created_at'] ?? '')) ?></small>
      </div>
      <button class="btn btn-sm btn-outline-secondary" type="button"
              data-bs-toggle="collapse" data-bs-target="#payload-<?= (int)($log['id'] ?? 0) ?>"
              aria-expanded="false">
        Payload
      </button>
    </div>

    <?php if (!empty($log['detail'])): ?>
      <p class="small mb-2 <?= $ok ? 'text-body-secondary' : 'text-danger' ?>">
        <?= e($log['detail']) ?>
      </p>
    <?php endif; ?>

    <div class="collapse" id="payload-<?= (int)($log['id'] ?? 0) ?>">
      <pre class="payload-pre mb-0"><?= e($log['payload'] ?? '') ?></pre>
    </div>
  </div>
</div>
