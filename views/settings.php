<?php $title = 'Pengaturan'; ?>
<div class="row justify-content-center">
  <div class="col-12 col-lg-8 col-xl-6">

    <h1 class="h3 mb-4">Pengaturan</h1>

    <div class="card mb-4">
      <div class="card-header fw-semibold">Akun</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Username</dt>
          <dd class="col-sm-8"><?= e($user['username']) ?></dd>
          <dt class="col-sm-4">Terdaftar</dt>
          <dd class="col-sm-8"><?= e($user['created_at']) ?></dd>
        </dl>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header fw-semibold">Ubah Password</div>
      <div class="card-body">
        <form method="post" action="<?= e(base_path('/settings')) ?>" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label" for="current_password">Password Saat Ini</label>
            <input class="form-control" id="current_password" name="current_password"
                   type="password" autocomplete="current-password" required>
          </div>

          <div class="mb-4">
            <label class="form-label" for="new_password">Password Baru</label>
            <input class="form-control" id="new_password" name="new_password"
                   type="password" autocomplete="new-password" minlength="8" required>
            <div class="form-text">Minimal 8 karakter.</div>
          </div>

          <button class="btn btn-primary" type="submit">Simpan Password</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header fw-semibold">Tema</div>
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="small text-body-secondary">
          Tampilan terang / gelap. Pilihan disimpan di peramban ini.
        </div>
        <button class="btn btn-outline-secondary" type="button" id="themeToggleSettings">
          Ganti Tema
        </button>
      </div>
    </div>

  </div>
</div>
