<?php $title = 'Tidak Ditemukan'; ?>
<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-6">
    <div class="card text-center">
      <div class="card-body p-5">
        <span class="bi bi-x-circle text-secondary d-block mb-3" style="font-size:3rem" aria-hidden="true"></span>
        <h1 class="h4">404 — Tidak ditemukan</h1>
        <p class="text-body-secondary">
          Halaman atau endpoint yang Anda tuju tidak ada.
        </p>
        <a class="btn btn-primary" href="<?= e(base_path($user ? '/dashboard' : '/login')) ?>">
          Kembali
        </a>
      </div>
    </div>
  </div>
</div>
