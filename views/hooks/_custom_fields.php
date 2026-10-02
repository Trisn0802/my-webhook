<?php
/**
 * Form Custom Field (maks 6 pasang).
 * Varsional:
 *   $customFields — array existing saat edit (opsional, default [])
 *   $customPrefix — prefix id unik per form ('s_' untuk dashboard, '' untuk form edit)
 *
 * Dipakai di views/dashboard.php dan views/hooks/form.php.
 */
$customFields = $customFields ?? [];
$customPrefix = $customPrefix ?? '';
$max = HOOK_MAX_CUSTOM_FIELDS;
// Render hanya field terisi + satu baris kosong sebagai awalan.
$slots = max(count($customFields), 1);
?>
<div class="border-top pt-3 mt-3">
  <div class="d-flex justify-content-between align-items-center mb-1">
    <label class="form-label fw-semibold mb-0">Custom Field</label>
    <span class="badge text-bg-secondary">maks <?= $max ?> pasang</span>
  </div>
  <p class="small text-body-secondary mb-2">
    Informasi tambahan yang selalu ikut di setiap pesan (mis. Lokasi: Jakarta).
    Baris yang dikosongkan akan diabaikan.
  </p>

  <?php for ($i = 0; $i < $slots; $i++): ?>
    <?php
      $label = $customFields[$i]['label'] ?? '';
      $value = $customFields[$i]['value'] ?? '';
      $id = $customPrefix . 'cf_' . $i;
    ?>
    <div class="row g-2 mb-2 custom-field-row">
      <div class="col-12 col-md-5">
        <label class="visually-hidden" for="<?= e($id) ?>_label">Nama custom field <?= $i + 1 ?></label>
        <input class="form-control form-control-sm" id="<?= e($id) ?>_label"
               name="custom_label[]" type="text" maxlength="40"
               placeholder="Nama (mis. Lokasi)" value="<?= e($label) ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="visually-hidden" for="<?= e($id) ?>_value">Nilai custom field <?= $i + 1 ?></label>
        <input class="form-control form-control-sm" id="<?= e($id) ?>_value"
               name="custom_value[]" type="text" maxlength="200"
               placeholder="Nilai (mis. Jakarta)" value="<?= e($value) ?>">
      </div>
      <div class="col-12 col-md-1 d-grid">
        <button class="btn btn-sm btn-outline-danger" type="button"
                data-remove-field title="Hapus baris" aria-label="Hapus baris custom field <?= $i + 1 ?>">✕</button>
      </div>
    </div>
  <?php endfor; ?>

  <button class="btn btn-sm btn-outline-secondary" type="button"
          data-add-field data-max="<?= $max ?>">
    + Tambah Field
  </button>
</div>
