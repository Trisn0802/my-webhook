<?php $title = 'Login'; ?>
<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

    <div class="card shadow-sm">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <center>
            <span class="bi bi-box-arrow-in-right text-primary d-block mb-3" style="font-size:2.75rem" aria-hidden="true"></span>
          </center>
          <h1 class="h4 mb-1">Masuk ke <?= e(APP_NAME) ?></h1>
          <p class="text-body-secondary small mb-0">Webhook manager untukTelegram Anda.</p>
        </div>

        <form method="post" action="<?= e(base_path('/login')) ?>" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" id="username" name="username" type="text"
                   autocomplete="username" required autofocus>
          </div>

          <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password"
                   autocomplete="current-password" required>
          </div>

          <button class="btn btn-primary w-100" type="submit">Login</button>
        </form>

        <p class="text-center small text-body-secondary mt-4 mb-0">
          <?php if (registration_state()['open']): ?>
            Belum punya akun?
            <a href="<?= e(base_path('/register')) ?>">Daftar</a>
          <?php endif; ?>
        </p>
      </div>
    </div>

  </div>
</div>
