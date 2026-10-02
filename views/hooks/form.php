<?php
$title = $hook ? 'Edit Hook' : 'Hook Baru';
$action = $hook ? base_path('/hooks/' . $hook['id'] . '/edit') : base_path('/hooks/new');
$endpoint = $hook ? absolute_url('/hook/' . $hook['token']) : null;
?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-9 col-xl-8">

    <div class="d-flex align-items-center gap-2 mb-4">
      <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_path("/dashboard")) ?>"><span class="bi bi-arrow-left me-1" aria-hidden="true"></span>Kembali</a>
      <h1 class="h3 mb-0"><?= $hook ? 'Edit Hook' : 'Hook Baru' ?></h1>
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <form method="post" action="<?= e($action) ?>" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label" for="name">Nama Hook</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="60"
                   placeholder="mis. Server Uptime Kuma" required
                   list="serviceSuggestions" autocomplete="off"
                   value="<?= e($hook['name'] ?? '') ?>">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-7">
              <label class="form-label" for="bot_token">Bot Token</label>
              <input class="form-control font-monospace" id="bot_token" name="bot_token"
                     type="text" placeholder="123456789:AA..." required
                     value="<?= e($hook['bot_token'] ?? '') ?>">
              <div class="form-text">
                Dari <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a>.
              </div>
            </div>
            <div class="col-12 col-md-5">
              <label class="form-label" for="chat_id">Chat ID</label>
              <input class="form-control font-monospace" id="chat_id" name="chat_id"
                     type="text" inputmode="numeric" placeholder="-1001234567890" required
                     value="<?= e($hook['chat_id'] ?? '') ?>">
              <div class="form-text">Angka, boleh negatif (grup/channel).</div>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label class="form-label" for="format">Format Pesan</label>
              <select class="form-select" id="format" name="format">
                <option value="json" <?= (($hook['format'] ?? 'json') === 'json') ? 'selected' : '' ?>>
                  JSON penuh (detail)
                </option>
                <option value="text" <?= (($hook['format'] ?? '') === 'text') ? 'selected' : '' ?>>
                  Teks ringkas (field utama saja)
                </option>
              </select>
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                       name="is_active" value="1" <?= ((int)($hook['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Hook aktif</label>
              </div>
            </div>
          </div>

          <?php
            $customFields = \hook_custom_fields($hook['custom_fields'] ?? '');
            $customPrefix = 'e_';
            require __DIR__ . '/_custom_fields.php';
          ?>

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-primary" type="submit">
              <?= $hook ? 'Simpan Perubahan' : 'Buat Hook' ?>
            </button>
            <a class="btn btn-outline-secondary" href="<?= e(base_path('/dashboard')) ?>">Batal</a>
          </div>
        </form>

        <?php require __DIR__ . '/_suggestions.php'; ?>
      </div>
    </div>

    <?php if ($hook): ?>
    <!-- Endpoint + test -->
    <div class="card mt-4">
      <div class="card-header fw-semibold">Endpoint Webhook</div>
      <div class="card-body">
        <p class="small text-body-secondary mb-3">
          Kirim request <code>POST</code> dengan body JSON/teks ke URL berikut:
        </p>

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

        <hr>
        <p class="small text-body-secondary mb-1">Contoh dengan curl:</p>
        <pre class="bg-body-tertiary border rounded p-3 small mb-0 overflow-auto"><code>curl -X POST "<?= e($endpoint) ?>" \
  -H "Content-Type: application/json" \
  -d '{"message":"halo dari webhook"}'</code></pre>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
