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

  // --- Konfirmasi hapus ---
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    var msg = form.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
      ev.preventDefault();
    }
  });

  // =====================================================================
  // Live update — polling /api/live ringan tiap POLL_MS.
  // Memperbarui statistik, kartu hook, badge aktif, dan log pengiriman.
  // =====================================================================
  var POLL_MS = 5000;
  var liveTimer = null;
  var liveBusy = false;

  // Indikator "live" kecil di navbar agar jelas data diperbarui otomatis.
  (function addLiveDot() {
    var nav = document.querySelector('nav .navbar-nav');
    if (!nav) return;
    var li = document.createElement('li');
    li.className = 'nav-item d-flex align-items-center';
    li.innerHTML =
      '<span class="navbar-text small text-body-secondary d-flex align-items-center gap-1" ' +
      'title="Diperbarui otomatis tiap ' + (POLL_MS / 1000) + ' detik">' +
      '<span class="live-dot" aria-hidden="true"></span>Live</span>';
    nav.appendChild(li);
  })();

  function setBadge(el, active) {
    if (!el) return;
    var on = active === 1;
    el.textContent = on ? 'Aktif' : 'Nonaktif';
    el.classList.toggle('text-bg-success', on);
    el.classList.toggle('text-bg-secondary', !on);
  }

  // Markup item log — dicerminkan dari views/hooks/_log_item.php.
  function logItemHtml(l) {
    var ok = l.status === 'sent';
    var esc = function (s) {
      return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    };
    var detail = l.detail
      ? '<p class="small mb-2 ' + (ok ? 'text-body-secondary' : 'text-danger') + '">' +
        esc(l.detail) + '</p>'
      : '';
    return '' +
      '<div class="card log-card">' +
        '<div class="card-body">' +
          '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">' +
            '<div class="d-flex align-items-center gap-2">' +
              '<span class="badge ' + (ok ? 'text-bg-success' : 'text-bg-danger') + '">' +
                (ok ? 'Terkirim' : 'Gagal') + '</span>' +
              '<small class="text-body-secondary">' + esc(l.created_at) + '</small>' +
              '<small class="text-body-secondary">· ' + esc(l.ago) + '</small>' +
            '</div>' +
            '<button class="btn btn-sm btn-outline-secondary" type="button" ' +
                    'data-bs-toggle="collapse" data-bs-target="#payload-' + l.id + '" ' +
                    'aria-expanded="false">Payload</button>' +
          '</div>' +
          detail +
          '<div class="collapse" id="payload-' + l.id + '">' +
            '<pre class="payload-pre mb-0">' + esc(l.payload) + '</pre>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function renderLogs(logs) {
    var list = document.getElementById('logList');
    var empty = document.getElementById('logEmpty');
    if (!list) return;

    // Jangan tutup payload yang sedang dibuka user saat polling berjalan.
    var expanded = {};
    list.querySelectorAll('.collapse.show').forEach(function (el) {
      expanded[el.id] = true;
    });

    list.innerHTML = logs.map(logItemHtml).join('');
    Object.keys(expanded).forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.classList.add('show');
      var toggle = list.querySelector('[data-bs-target="#' + id + '"]');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
    });

    if (empty) empty.classList.toggle('d-none', logs.length > 0);
    list.classList.toggle('d-none', logs.length === 0);
  }

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

    // 2) Kartu hook: status aktif, jumlah kirim, waktu terakhir.
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

    // 3) Halaman detail: badge aktif + log pengiriman.
    if (data.detail) {
      var head = document.querySelector('[data-live-hook]');
      if (head) setBadge(head.querySelector('[data-live-status]'), data.detail.is_active);
      if (data.detail.logs) renderLogs(data.detail.logs);
    }
  }

  function pollLive() {
    if (liveBusy) return;
    liveBusy = true;

    var detail = document.querySelector('[data-live-hook]');
    var url = '/api/live';
    if (detail) url += '?hook=' + encodeURIComponent(detail.getAttribute('data-live-hook'));

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
