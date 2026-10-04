/* My Webhook — perilaku ringan tanpa dependensi. */
(function () {
  'use strict';

  var root = document.documentElement;

  function currentTheme() {
    return root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
  }

  function setTheme(theme) {
    root.setAttribute('data-bs-theme', theme);
    try { localStorage.setItem('theme', theme); } catch (e) {}
    refreshIcons(theme);
  }

  function toggleTheme() {
    setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
  }

  // Ikon Bootstrap lokal via CSS mask; warnanya mengikuti currentColor.
  var ICONS = {
    light: '<span class="bi bi-sun-fill" aria-hidden="true"></span>',
    dark: '<span class="bi bi-moon-fill" aria-hidden="true"></span>'
  };

  function refreshIcons(theme) {
    var btn = document.getElementById('themeToggle');
    if (!btn) return;
    // Tampilkan ikon tema yang belum aktif (klik = beralih).
    btn.innerHTML = theme === 'dark' ? ICONS.light : ICONS.dark;
    btn.setAttribute('title', theme === 'dark' ? 'Mode terang' : 'Mode gelap');
    btn.setAttribute('aria-label', theme === 'dark' ? 'Beralih ke mode terang' : 'Beralih ke mode gelap');
  }

  refreshIcons(currentTheme());

  document.addEventListener('click', function (ev) {
    // --- Ganti tema ---
    var themeBtn = ev.target.closest('#themeToggle, #themeToggleSettings');
    if (themeBtn) {
      ev.preventDefault();
      toggleTheme();
      return;
    }

    // --- Salin URL ---
    var copyBtn = ev.target.closest('.btn-copy');
    if (copyBtn) {
      ev.preventDefault();
      var text = copyBtn.getAttribute('data-copy') || '';
      var done = function () {
        var old = copyBtn.textContent;
        copyBtn.textContent = 'Tersalin ✓';
        copyBtn.classList.add('copied');
        setTimeout(function () {
          copyBtn.textContent = old;
          copyBtn.classList.remove('copied');
        }, 1500);
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done, fallback);
      } else {
        fallback();
      }
      function fallback() {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(ta);
      }
      return;
    }

    // --- Kirim pesan test ---
    var testBtn = ev.target.closest('[data-test-url]');
    if (testBtn) {
      ev.preventDefault();
      var url = testBtn.getAttribute('data-test-url');
      var out = document.getElementById('testResult');
      testBtn.disabled = true;
      if (out) out.textContent = 'Mengirim…';

      var fd = new FormData();
      var csrf = document.querySelector('input[name="_csrf"]');
      if (csrf) fd.append('_csrf', csrf.value);

      fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (out) {
            var icon = d.ok
              ? '<i class="bi bi-check-circle-fill text-success me-1" aria-hidden="true"></i>'
              : '<i class="bi bi-x-circle-fill text-danger me-1" aria-hidden="true"></i>';
            out.innerHTML = icon + '<span>' + String(d.message || '').replace(/[&<>"']/g, function (c) {
              return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
            }) + '</span>';
            out.className = 'align-self-center small d-inline-flex align-items-center ' + (d.ok ? 'text-success' : 'text-danger');
          }
        })
        .catch(function () {
          if (out) {
            out.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1" aria-hidden="true"></i>' +
              '<span>Gagal menghubungi server</span>';
            out.className = 'align-self-center small d-inline-flex align-items-center text-danger';
          }
        })
        .finally(function () { testBtn.disabled = false; });
      return;
    }
    // --- Custom field: tambah baris ---
    var addBtn = ev.target.closest('[data-add-field]');
    if (addBtn) {
      ev.preventDefault();
      var wrap = addBtn.parentElement;
      var rows = wrap.querySelectorAll('.custom-field-row');
      var max = parseInt(addBtn.getAttribute('data-max'), 10) || 6;
      if (rows.length >= max) {
        addBtn.disabled = true;
        addBtn.textContent = 'Maksimal ' + max + ' field';
        return;
      }
      var col = document.createElement('div');
      col.className = 'row g-2 mb-2 custom-field-row';
      col.innerHTML =
        '<div class="col-12 col-md-5">' +
          '<label class="visually-hidden">Nama custom field</label>' +
          '<input class="form-control form-control-sm" name="custom_label[]" type="text" ' +
                 'maxlength="40" placeholder="Nama (mis. Lokasi)">' +
        '</div>' +
        '<div class="col-12 col-md-6">' +
          '<label class="visually-hidden">Nilai custom field</label>' +
          '<input class="form-control form-control-sm" name="custom_value[]" type="text" ' +
                 'maxlength="200" placeholder="Nilai (mis. Jakarta)">' +
        '</div>' +
        '<div class="col-12 col-md-1 d-grid">' +
          '<button class="btn btn-sm btn-outline-danger" type="button" data-remove-field ' +
                  'title="Hapus baris" aria-label="Hapus baris custom field">✕</button>' +
        '</div>';
      addBtn.insertAdjacentElement('beforebegin', col);

      var newCount = wrap.querySelectorAll('.custom-field-row').length;
      if (newCount >= max) {
        addBtn.disabled = true;
        addBtn.textContent = 'Maksimal ' + max + ' field';
      }
      var first = col.querySelector('input');
      if (first) first.focus();
      return;
    }

    // --- Custom field: hapus baris ---
    var rmBtn = ev.target.closest('[data-remove-field]');
    if (rmBtn) {
      ev.preventDefault();
      var row = rmBtn.closest('.custom-field-row');
      var scope = row ? row.parentElement : null;
      if (row) row.remove();
      var add = scope ? scope.querySelector('[data-add-field]') : null;
      if (add) {
        var limit = parseInt(add.getAttribute('data-max'), 10) || 6;
        var count = scope.querySelectorAll('.custom-field-row').length;
        if (count < limit) {
          add.disabled = false;
          add.textContent = '+ Tambah Field';
        }
      }
      return;
    }
  });

  // --- Konfirmasi dengan modal Bootstrap (logout, hapus hook) ---
  // Form dengan data-confirm-title / data-confirm-message menampilkan modal
  // alih-alih window.confirm bawaan peramban.
  var pendingForm = null;    // form yang menunggu persetujuan user
  var approvedForm = null;   // form yang sudah disetujui → submit berikutnya dilewati
  var confirmModal = null;

  function getConfirmModal() {
    if (confirmModal) return confirmModal;
    var el = document.getElementById('confirmModal');
    if (!el || typeof bootstrap === 'undefined') return null;
    confirmModal = new bootstrap.Modal(el);
    el.addEventListener('show.bs.modal', function () {
      var title = (pendingForm && pendingForm.getAttribute('data-confirm-title')) || 'Konfirmasi';
      var msg = (pendingForm && pendingForm.getAttribute('data-confirm-message')) || 'Apakah Anda yakin?';
      document.getElementById('confirmModalLabel').textContent = title;
      document.getElementById('confirmModalMessage').textContent = msg;
    });
    // Ditolak (tombol Batal / klik luar / Esc): batalkan pengiriman.
    el.addEventListener('hidden.bs.modal', function () {
      pendingForm = null;
    });
    return confirmModal;
  }

  document.addEventListener('click', function (ev) {
    var okBtn = ev.target.closest('#confirmModalOk');
    if (!okBtn) return;
    ev.preventDefault();
    var form = pendingForm;
    if (!form) return;
    pendingForm = null;
    approvedForm = form;
    if (confirmModal) confirmModal.hide();
    // Submit via native submit() to avoid re-triggering the modal's submit listener.
    // The form already contains its CSRF token and its action/method are unchanged.
    setTimeout(function () {
      form.submit();
      approvedForm = null;
    }, 200);
  });

  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    var msg = form.getAttribute('data-confirm-message');
    if (!msg) return;

    // Sudah disetujui lewat modal → lanjutkan pengiriman.
    if (approvedForm === form) {
      approvedForm = null;
      return;
    }

    ev.preventDefault();
    var modal = getConfirmModal();
    if (!modal) {
      // Fallback bila Bootstrap JS belum termuat: dialog bawaan peramban.
      var title = form.getAttribute('data-confirm-title') || 'Konfirmasi';
      if (window.confirm(title + '\n' + msg)) form.submit();
      return;
    }
    pendingForm = form;
    modal.show();
  });

  // =====================================================================
  // Live update — polling /api/live ringan tiap POLL_MS.
  // Memperbarui statistik, kartu hook, badge aktif, dan log pengiriman.
  // =====================================================================
  var POLL_MS = 5000;
  var liveTimer = null;
  var liveBusy = false;

  function setBadge(el, active) {
    if (!el) return;
    var on = active === 1;
    el.textContent = on ? 'Aktif' : 'Nonaktif';
    el.classList.toggle('text-bg-success', on);
    el.classList.toggle('text-bg-secondary', !on);
  }

  // --- State filter/query agar polling memakai pilihan yang sedang tampil ---
  function filterParams(formId) {
    var form = document.getElementById(formId);
    var out = {};
    if (!form) return out;
    new FormData(form).forEach(function (value, key) {
      if (key !== 'page' && value !== '') out[key] = value;
    });
    return out;
  }

  function currentPage(formId) {
    var form = document.getElementById(formId);
    var input = form && form.querySelector('[name="page"]');
    return Math.max(1, parseInt(input && input.value || '1', 10) || 1);
  }

  function setPage(formId, page) {
    var form = document.getElementById(formId);
    var input = form && form.querySelector('[name="page"]');
    if (input) input.value = String(Math.max(1, page));
  }

  function queryString(values) {
    var p = new URLSearchParams();
    Object.keys(values).forEach(function (k) {
      if (values[k] !== '' && values[k] != null) p.set(k, String(values[k]));
    });
    return p.toString();
  }

  function safeText(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  function logItemHtml(l) {
    var ok = l.status === 'sent';
    var detail = l.detail
      ? '<p class="small mb-2 ' + (ok ? 'text-body-secondary' : 'text-danger') + '">' + safeText(l.detail) + '</p>'
      : '';
    return '<div class="card log-card"><div class="card-body">' +
      '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">' +
        '<div class="d-flex align-items-center gap-2">' +
          '<span class="badge ' + (ok ? 'text-bg-success' : 'text-bg-danger') + '">' + (ok ? 'Terkirim' : 'Gagal') + '</span>' +
          '<small class="text-body-secondary">' + safeText(l.created_at) + '</small>' +
          '<small class="text-body-secondary">· ' + safeText(l.ago) + '</small>' +
        '</div>' +
        '<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" ' +
          'data-bs-target="#payload-' + Number(l.id) + '" aria-expanded="false">Payload</button>' +
      '</div>' + detail +
      '<div class="collapse" id="payload-' + Number(l.id) + '"><pre class="payload-pre mb-0">' + safeText(l.payload) + '</pre></div>' +
      '</div></div>';
  }

  function hookCardHtml(h) {
    var active = Number(h.is_active) === 1;
    var csrf = document.querySelector('input[name="_csrf"]');
    var token = csrf ? csrf.value : '';
    var base = '/hooks/' + Number(h.id);
    var endpoint = safeText(h.endpoint);
    var name = safeText(h.name);
    var count = Number(h.total || 0);
    var last = h.last_at ? 'terakhir ' + safeText(h.last_ago || '') : 'belum ada';
    return '<div class="col-12 col-md-6 col-xl-4"><div class="card h-100 hook-card" data-hook-card="' + Number(h.id) + '">' +
      '<div class="card-body"><div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
        '<h3 class="h6 mb-0 text-truncate" title="' + name + '">' + name + '</h3>' +
        '<span class="badge flex-shrink-0 ' + (active ? 'text-bg-success' : 'text-bg-secondary') + '" data-live-status>' + (active ? 'Aktif' : 'Nonaktif') + '</span>' +
      '</div><div class="input-group input-group-sm mb-3">' +
        '<input class="form-control form-control-sm font-monospace endpoint-input" type="text" readonly value="' + endpoint + '" aria-label="URL endpoint">' +
        '<button class="btn btn-outline-secondary btn-copy" type="button" data-copy="' + endpoint + '" title="Salin URL">Salin</button>' +
      '</div><div class="d-flex justify-content-between small text-body-secondary mb-3">' +
        '<span data-live-total="' + count + '"><strong>' + count + '</strong> kirim</span>' +
        '<span data-live-last="' + safeText(h.last_at || '') + '">' + last + '</span>' +
      '</div><div class="d-flex gap-2">' +
        '<a class="btn btn-sm btn-outline-primary flex-grow-1" href="' + base + '">Detail</a>' +
        '<form method="post" action="' + base + '/clone" class="m-0"><input type="hidden" name="_csrf" value="' + safeText(token) + '">' +
          '<button class="btn btn-sm btn-outline-secondary" type="submit" title="Clone hook"><i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Clone</span></button></form>' +
        '<a class="btn btn-sm btn-outline-secondary" href="' + base + '/edit">Edit</a>' +
        '<form method="post" action="' + base + '/delete" class="m-0" data-confirm-title="Hapus Hook" data-confirm-message="Hapus hook &quot;' + name + '&quot; beserta semua log pengirimannya? Tindakan ini tidak dapat dibatalkan.">' +
          '<input type="hidden" name="_csrf" value="' + safeText(token) + '">' +
          '<button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form>' +
      '</div></div></div></div>';
  }

  function setEmptyState(el, show, message) {
    if (!el) return;
    el.classList.toggle('d-none', !show);
    var text = el.querySelector('#hookEmptyText');
    if (text && message) text.textContent = message;
    // Sembunyikan ajakan "buat hook" saat hasil kosong karena filter.
    var cta = el.querySelector('a.btn.btn-primary');
    if (cta) cta.classList.toggle('d-none', /tidak ada/i.test(message || ''));
  }

  function hookPagerHtml(page, pages, total, filter) {
    var el = document.getElementById('hookPager');
    if (!el) return;
    if (pages <= 1) {
      el.innerHTML = '';
      el.classList.add('d-none');
      return;
    }
    el.classList.remove('d-none');
    var prev = page > 1
      ? '<a class="btn btn-sm btn-outline-secondary" href="/dashboard?' + queryString(Object.assign({}, filter, {page: page - 1})) + '">&lsaquo; Sebelumnya</a>'
      : '';
    var next = page < pages
      ? '<a class="btn btn-sm btn-outline-secondary" href="/dashboard?' + queryString(Object.assign({}, filter, {page: page + 1})) + '">Berikutnya &rsaquo;</a>'
      : '';
    el.innerHTML = '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-1"><div class="btn-group" role="group" aria-label="Navigasi halaman">' + prev + next + '</div><small class="text-body-secondary">Halaman ' + page + ' / ' + pages + '</small></div>';
  }

  function logPagerHtml(page, pages, filter, hookId) {
    var el = document.getElementById('logPager');
    if (!el) return;
    if (pages <= 1) {
      el.innerHTML = '';
      el.classList.add('d-none');
      return;
    }
    el.classList.remove('d-none');
    function link(n, label, cls, disabled) {
      if (disabled) return '<li class="page-item disabled"><span class="page-link">' + label + '</span></li>';
      return '<li class="page-item ' + (cls || '') + '"><a class="page-link" href="/hooks/' + hookId + '?' + queryString(Object.assign({}, filter, {page:n})) + '" data-live-page="' + n + '">' + label + '</a></li>';
    }
    var html = '<ul class="pagination pagination-sm flex-wrap justify-content-center mb-0">';
    html += link(page - 1, '&lsaquo; Sebelumnya', '', page <= 1);
    var start = Math.max(1, page - 1), end = Math.min(pages, page + 1);
    if (start > 1) html += link(1, '1', '') + (start > 2 ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : '');
    for (var i = start; i <= end; i++) html += link(i, String(i), i === page ? 'active' : '');
    if (end < pages) html += (end < pages - 1 ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : '') + link(pages, String(pages), '');
    html += link(page + 1, 'Berikutnya &rsaquo;', '', page >= pages) + '</ul>';
    el.innerHTML = html;
  }

  function renderHooks(data, filter) {
    var list = document.getElementById('hookList');
    var empty = document.getElementById('hookEmpty');
    if (!list) return;
    var hooks = data.hooks || [];
    var total = Number(data.hooks_total || 0);
    var page = Number(data.hooks_page || 1);
    var pages = Number(data.hooks_pages || 1);
    list.innerHTML = hooks.map(hookCardHtml).join('');
    list.classList.toggle('d-none', hooks.length === 0);
    setEmptyState(empty, hooks.length === 0, total === 0 && !filter.q && filter.status === 'all'
      ? 'Belum ada hook. Buat satu untuk mulai menerima webhook.'
      : 'Tidak ada hook yang cocok dengan filter saat ini.');
    var summary = document.getElementById('hookSummary');
    var from = total ? ((page - 1) * 6 + 1) : 0;
    var to = Math.min(page * 6, total);
    if (summary) summary.textContent = 'Menampilkan ' + from + '–' + to + ' dari ' + total + ' hook';
    hookPagerHtml(page, pages, total, filter);
  }

  function renderLogs(data, filter, hookId) {
    var list = document.getElementById('logList');
    var empty = document.getElementById('logEmpty');
    if (!list) return;
    var logs = data.logs || [];
    var total = Number(data.logs_total || 0);
    var page = Number(data.logs_page || 1);
    var pages = Number(data.logs_pages || 1);
    var expanded = {};
    list.querySelectorAll('.collapse.show').forEach(function (el) { expanded[el.id] = true; });
    list.innerHTML = logs.map(logItemHtml).join('');
    Object.keys(expanded).forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.classList.add('show');
      var toggle = list.querySelector('[data-bs-target="#' + id + '"]');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
    });
    list.classList.toggle('d-none', logs.length === 0);
    setEmptyState(empty, logs.length === 0, total === 0 && !filter.q && filter.status === 'all' && !filter.from && !filter.to
      ? 'Belum ada request masuk ke hook ini.'
      : 'Tidak ada log yang cocok dengan filter saat ini.');
    var summary = document.getElementById('logSummary');
    var per = Number(filter.per_page || 10);
    var from = total ? ((page - 1) * per + 1) : 0;
    var to = Math.min(page * per, total);
    if (summary) summary.textContent = 'Menampilkan ' + from + '–' + to + ' dari ' + total + ' log';
    logPagerHtml(page, pages, filter, hookId);
  }

  function liveFilterChanged(resetPage) {
    if (resetPage) {
      setPage('hookFilterForm', 1);
      setPage('logFilterForm', 1);
    }
    pollLive();
  }

  var searchDebounce = null;
  document.addEventListener('input', function (ev) {
    if (!ev.target.matches('#hook_q, #log_q')) return;
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(function () { liveFilterChanged(true); }, 350);
  });
  document.addEventListener('change', function (ev) {
    if (ev.target.matches('#hook_status, #log_status, #log_from, #log_to, #log_per')) {
      liveFilterChanged(true);
    }
  });
  document.addEventListener('click', function (ev) {
    var hookPager = ev.target.closest('#hookPager a[href]');
    if (hookPager) {
      ev.preventDefault();
      var url = new URL(hookPager.href, window.location.href);
      setPage('hookFilterForm', Number(url.searchParams.get('page')) || 1);
      pollLive();
      return;
    }
    var link = ev.target.closest('#logPager a[data-live-page]');
    if (!link) return;
    ev.preventDefault();
    setPage('logFilterForm', Number(link.getAttribute('data-live-page')));
    pollLive();
  });

  function applyLive(data) {
    // 1) Statistik dashboard.
    var s = data.stats || {};
    ['hooks', 'today', 'failed'].forEach(function (key) {
      var el = document.querySelector('[data-live-stat="' + key + '"]');
      if (!el) return;
      var val = Number(s[key] || 0);
      if (el.textContent.trim() !== String(val)) el.textContent = String(val);
      if (key === 'failed') el.classList.toggle('text-danger', val > 0);
    });

    // 2) Kartu hook: dirender ulang sesuai filter & halaman aktif.
    if (data.hooks && document.getElementById('hookList')) {
      renderHooks(data, filterParams('hookFilterForm'));
    } else {
      // Fallback bila grid tidak ada: perbarui badge jumlah yang tampak.
      (data.hooks || []).forEach(function (h) {
        var card = document.querySelector('[data-hook-card="' + h.id + '"]');
        if (!card) return;
        setBadge(card.querySelector('[data-live-status]'), h.is_active);
        var total = card.querySelector('[data-live-total]');
        if (total && total.getAttribute('data-live-total') !== String(h.total)) {
          total.setAttribute('data-live-total', String(h.total));
          total.innerHTML = '<strong>' + h.total + '</strong> kirim';
        }
        var last = card.querySelector('[data-live-last]');
        if (last) {
          var text = h.last_at ? 'terakhir ' + (h.last_ago || '') : 'belum ada';
          if (last.textContent.trim() !== text) last.textContent = text;
        }
      });
    }

    // 3) Halaman detail: badge aktif + log terfilter & paginasi.
    if (data.detail) {
      var head = document.querySelector('[data-live-hook]');
      if (head) {
        setBadge(head.querySelector('[data-live-status]'), data.detail.is_active);
        var hookId = head.getAttribute('data-live-hook');
        if (document.getElementById('logList')) {
          renderLogs(data.detail, filterParams('logFilterForm'), hookId);
        }
      }
    }
  }

  function pollLive() {
    if (liveBusy) return;
    liveBusy = true;

    var detail = document.querySelector('[data-live-hook]');
    var params = {};
    // Halaman dashboard & detail tidak pernah tampil bersamaan, jadi `page`
    // boleh dipakai untuk kedua konteks tanpa bentrok.
    if (document.getElementById('hookList')) {
      params = Object.assign(params, filterParams('hookFilterForm'));
      params.page = currentPage('hookFilterForm');
    }
    if (detail) {
      params.hook = detail.getAttribute('data-live-hook');
      var logFilters = filterParams('logFilterForm');
      params.q = logFilters.q || '';
      params.status = logFilters.status || 'all';
      params.from = logFilters.from || '';
      params.to = logFilters.to || '';
      params.per_page = logFilters.per_page || '10';
      params.page = currentPage('logFilterForm');
    }
    var qs = queryString(params);
    var url = '/api/live' + (qs ? '?' + qs : '');

    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) {
        var type = (r.headers.get('content-type') || '');
        // Sesi habis: server redirect ke halaman login (HTML, bukan JSON).
        if (!r.ok || r.redirected || type.indexOf('json') === -1) {
          stopLive();
          return null;
        }
        return r.json();
      })
      .then(function (d) { if (d && d.ok) applyLive(d); })
      .catch(function () { /* gangguan sesaat: coba lagi di tick berikutnya */ })
      .finally(function () { liveBusy = false; });
  }

  function startLive() {
    if (liveTimer) return;
    liveTimer = setInterval(function () {
      if (document.hidden) return;      // jangan polling saat tab disembunyikan
      pollLive();
    }, POLL_MS);
    document.querySelectorAll('.live-dot').forEach(function (d) {
      d.classList.add('is-on');
    });
  }

  function stopLive() {
    if (!liveTimer) return;
    clearInterval(liveTimer);
    liveTimer = null;
    document.querySelectorAll('.live-dot').forEach(function (d) {
      d.classList.remove('is-on');
    });
  }

  // Hanya di halaman yang punya elemen live (dashboard/detail/settings).
  if (document.querySelector('[data-live-stat], [data-live-hook]')) {
    startLive();

    // Poll segera saat tab kembali aktif, tanpa menunggu jadwal.
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) pollLive();
    });
    // Sinkronkan sesegera mungkin setelah halaman selesai dimuat.
    window.addEventListener('load', pollLive);
  }
})();
