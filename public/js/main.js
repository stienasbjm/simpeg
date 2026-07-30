/**
 * e-Arsip Digital — Application JS v2.0
 * Features: theme, sidebar toggle, auto-dismiss alerts,
 *           page transitions, number counter animation, tooltips
 */
(function () {
  'use strict';

  const html = document.documentElement;
  const KEY  = 'earsip-theme';

  /* ── Theme ──────────────────────────────────────────────────── */
  function getTheme() {
    return localStorage.getItem(KEY) ||
      (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  }

  function setTheme(t) {
    html.setAttribute('data-bs-theme', t);
    localStorage.setItem(KEY, t);
    const ico = document.getElementById('themeIco');
    if (ico) {
      ico.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
    }
  }

  // Apply immediately (before DOMContentLoaded avoids FOUC)
  setTheme(getTheme());

  document.addEventListener('DOMContentLoaded', function () {

    /* ── Theme toggle ─────────────────────────────────────────── */
    const themeBtn = document.getElementById('themeBtn');
    if (themeBtn) {
      themeBtn.addEventListener('click', function () {
        const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        setTheme(next);
        // Ripple effect
        this.style.transform = 'scale(0.85) rotate(20deg)';
        setTimeout(() => { this.style.transform = ''; }, 200);
      });
    }

    /* ── Sidebar (mobile) ────────────────────────────────────── */
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    function openSidebar() {
      sidebar && sidebar.classList.add('is-open');
      overlay && overlay.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      sidebar && sidebar.classList.remove('is-open');
      overlay && overlay.classList.remove('is-open');
      document.body.style.overflow = '';
    }

    menuBtn && menuBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      sidebar && sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
    });

    overlay && overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function () {
      if (innerWidth >= 992) closeSidebar();
    });

    /* ── Auto-dismiss alerts ─────────────────────────────────── */
    document.querySelectorAll('.e-notice[data-dismiss]').forEach(function (el) {
      // Add close button
      const closeBtn = document.createElement('button');
      closeBtn.innerHTML = '<i class="bi bi-x"></i>';
      closeBtn.style.cssText = 'margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;opacity:.6;font-size:.9rem;flex-shrink:0;padding:0;line-height:1;';
      closeBtn.addEventListener('click', () => dismissEl(el));
      el.appendChild(closeBtn);

      // Auto-dismiss after 5s
      setTimeout(() => dismissEl(el), 5000);
    });

    function dismissEl(el) {
      el.style.transition = 'opacity .35s ease, max-height .35s ease, margin .35s ease, padding .35s ease';
      el.style.opacity    = '0';
      el.style.maxHeight  = '0';
      el.style.padding    = '0';
      el.style.margin     = '0';
      setTimeout(() => el.remove(), 360);
    }

    /* ── Animated stat counters ──────────────────────────────── */
    function animateCounter(el) {
      const target = parseInt(el.textContent.replace(/,/g, ''), 10);
      if (isNaN(target) || target === 0) return;
      const duration = 900;
      const start    = performance.now();
      function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        // Ease-out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(eased * target).toLocaleString('id-ID');
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    // Intersection Observer for stat numbers
    const statNums = document.querySelectorAll('.e-stat-num');
    if (statNums.length && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });

      statNums.forEach(el => observer.observe(el));
    }

    /* ── Active nav highlight (precise) ─────────────────────── */
    const activeLinks = document.querySelectorAll('.e-nav-link.is-active');
    activeLinks.forEach(link => {
      // Smooth scroll into view on mobile
      if (window.innerWidth < 992) {
        link.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    });

    /* ── Confirm dialogs with SweetAlert2 ───────────────────── */
    function attachConfirmListeners() {
      document.querySelectorAll('[onclick*="confirm"], .e-act-btn.del, [data-confirm]').forEach(el => {
        const onclickStr = el.getAttribute('onclick') || '';
        let msgText = el.getAttribute('data-confirm') || '';
        
        if (!msgText && onclickStr) {
          const match = onclickStr.match(/confirm\(['"](.+?)['"]\)/);
          if (match) msgText = match[1];
        }

        if (!msgText) msgText = 'Apakah Anda yakin ingin menghapus data ini?';

        // Strip inline onclick to avoid browser native confirm popup
        el.removeAttribute('onclick');

        // Prevent attaching multiple listeners
        if (el.dataset.swalAttached) return;
        el.dataset.swalAttached = 'true';

        el.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();

          // Deteksi konteks teks (misal: Pegawai, Surat, SK, Kas, Akun)
          let titleText = 'Konfirmasi Hapus Data';
          let bodyText  = msgText;

          if (msgText.toLowerCase().includes('pegawai')) {
            titleText = 'Hapus Data Pegawai?';
          } else if (msgText.toLowerCase().includes('surat masuk')) {
            titleText = 'Hapus Surat Masuk?';
          } else if (msgText.toLowerCase().includes('surat keluar')) {
            titleText = 'Hapus Surat Keluar?';
          } else if (msgText.toLowerCase().includes('sk')) {
            titleText = 'Hapus Surat Keputusan (SK)?';
          } else if (msgText.toLowerCase().includes('kas')) {
            titleText = 'Hapus Transaksi Kas?';
          } else if (msgText.toLowerCase().includes('gaji')) {
            titleText = 'Hapus Penggajian?';
          } else if (msgText.toLowerCase().includes('akun')) {
            titleText = 'Hapus Akun Pengguna?';
          }

          Swal.fire({
            title: `<span style="font-weight:800; font-size:1.3rem;">${titleText}</span>`,
            html: `
              <div style="font-size:0.92rem; color:var(--text-muted, #64748b); margin-top:0.4rem;">
                ${bodyText}
              </div>
              <div style="margin-top:0.75rem; padding:0.5rem 0.75rem; background:rgba(239,68,68,0.08); border-radius:8px; border:1px solid rgba(239,68,68,0.2); color:#dc2626; font-size:0.78rem; font-weight:600; display:inline-flex; align-items:center; gap:0.4rem;">
                <i class="bi bi-exclamation-triangle-fill"></i> Data yang telah dihapus tidak dapat dikembalikan lagi.
              </div>
            `,
            icon: 'warning',
            iconColor: '#ef4444',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-trash3-fill" style="margin-right:4px;"></i> Ya, Hapus Data',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            focusCancel: true,
            reverseButtons: true,
            background: 'var(--bg-card, #ffffff)',
            color: 'var(--text-primary, #0f172a)',
            customClass: {
              popup: 'e-swal-popup',
              confirmButton: 'e-btn e-btn-danger',
              cancelButton: 'e-btn e-btn-ghost'
            }
          }).then((result) => {
            if (result.isConfirmed) {
              if (el.tagName.toLowerCase() === 'a' && el.href) {
                window.location.href = el.href;
              } else {
                const form = el.closest('form');
                if (form) form.submit();
              }
            }
          });
        });
      });
    }

    attachConfirmListeners();

    /* ── Topbar shadow on scroll ─────────────────────────────── */
    const topbar = document.querySelector('.e-topbar');
    if (topbar) {
      window.addEventListener('scroll', function() {
        topbar.style.boxShadow = scrollY > 10
          ? '0 4px 20px rgba(0,0,0,.1)'
          : 'none';
      }, { passive: true });
    }

    /* ── Table row click (detail) ────────────────────────────── */
    document.querySelectorAll('.e-table tbody tr[data-href]').forEach(row => {
      row.style.cursor = 'pointer';
      row.addEventListener('click', function() {
        window.location.href = this.dataset.href;
      });
    });

    /* ── Input focus animation ────────────────────────────────── */
    document.querySelectorAll('.e-input, .e-select, .e-textarea').forEach(el => {
      const wrapper = el.parentElement;
      el.addEventListener('focus', () => {
        if (wrapper) wrapper.style.transform = 'scale(1.005)';
      });
      el.addEventListener('blur', () => {
        if (wrapper) wrapper.style.transform = '';
      });
    });

  }); // DOMContentLoaded
})();