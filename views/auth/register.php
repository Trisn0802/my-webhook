<?php $title = 'Register'; $state = $state ?? ['open' => false, 'code_required' => false]; ?>
<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

    <div class="card shadow-sm">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <span class="bi bi-person-plus text-primary d-block mb-3" style="font-size:2.75rem" aria-hidden="true"></span>
          <h1 class="h4 mb-1">Buat Akun</h1>
          <p class="text-body-secondary small mb-0">Satu akun untuk semua webhook Anda.</p>
        </div>

        <?php if (!$state['open']): ?>
          <div class="alert alert-warning mb-0">
            Registrasi sedang ditutup oleh administrator.
            <a href="<?= e(base_path('/login')) ?>">Kembali ke login</a>.
          </div>
        <?php else: ?>
          <form method="post" action="<?= e(base_path('/register')) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
              <label class="form-label" for="username">Username</label>
              <input class="form-control" id="username" name="username" type="text"
                     autocomplete="username" minlength="3" maxlength="32"
                     pattern="[a-z0-9_]{3,32}" required autofocus>
              <div class="form-text">3–32 karakter: huruf kecil, angka, underscore.</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="password">Password</label>
              <input class="form-control" id="password" name="password" type="password"
                     autocomplete="new-password" minlength="8" required>
              <div class="form-text">Minimal 8 karakter.</div>
            </div>

            <?php if ($state['code_required']): ?>
            <div class="mb-4">
              <label class="form-label" for="code">Kode Registrasi</label>
              <input class="form-control" id="code" name="code" type="text" required>
            </div>
            <?php else: ?>
            <div class="mb-4"></div>
            <?php endif; ?>

            <button class="btn btn-primary w-100" type="submit">Daftar</button>
          </form>
        <?php endif; ?>

        <p class="text-center small text-body-secondary mt-4 mb-0">
          Sudah punya akun?
          <a href="<?= e(base_path('/login')) ?>">Login</a>
        </p>
      </div>
    </div>

  </div>
</div>
