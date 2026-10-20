/* Arkamas Gas — skrip bersama (tanpa dependensi) */
(function () {
    'use strict';
    var root = document.documentElement;
    var isMobile = function () { return window.matchMedia('(max-width: 900px)').matches; };


    /* ---------- Sidebar ---------- */
    window.toggleSidebar = function () {
        if (isMobile()) { root.classList.toggle('nav-open'); return; }
        var collapsed = root.dataset.sidebar === 'collapsed';
        if (collapsed) { delete root.dataset.sidebar; } else { root.dataset.sidebar = 'collapsed'; }
        try { localStorage.setItem('arkamas_sidebar', collapsed ? 'open' : 'collapsed'); } catch (e) {}
    };

    /* ---------- Modal ---------- */
    window.openModal = function (id) {
        var el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.classList.add('active');
        root.classList.add('modal-open');
        var first = el.querySelector('input:not([type=hidden]):not([disabled]), select, textarea');
        if (first) setTimeout(function () { first.focus(); }, 30);
    };
    window.closeModal = function (id) {
        var el = typeof id === 'string' ? document.getElementById(id) : id;
        if (el) el.classList.remove('active');
        if (!document.querySelector('.modal-overlay.active')) root.classList.remove('modal-open');
    };

    function snake(s) { return s.replace(/[A-Z]/g, function (c) { return '_' + c.toLowerCase(); }); }

    /* [data-modal-open="id"] membuka modal. Atribut data-* lain (mis. data-name, data-customer-type)
       otomatis mengisi field form bernama sama (name / customer_type); data-action mengatur action form. */
    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-modal-open]');
        if (opener) {
            var modal = document.getElementById(opener.dataset.modalOpen);
            if (!modal) return;
            var form = modal.querySelector('form');
            if (form && opener.dataset.action) form.action = opener.dataset.action;
            Object.keys(opener.dataset).forEach(function (key) {
                if (key === 'modalOpen' || key === 'action') return;
                var field = modal.querySelector('[name="' + snake(key) + '"]');
                if (field) field.value = opener.dataset[key];
            });
            var title = modal.querySelector('[data-modal-subject]');
            if (title && opener.dataset.subject) title.textContent = opener.dataset.subject;
            window.openModal(modal);
            return;
        }
        var closer = e.target.closest('[data-modal-close]');
        if (closer) { window.closeModal(closer.closest('.modal-overlay')); return; }
        if (e.target.classList && e.target.classList.contains('modal-overlay')) { window.closeModal(e.target); return; }

        var dismiss = e.target.closest('[data-dismiss]');
        if (dismiss) { var box = dismiss.closest('.alert'); if (box) box.remove(); }

        if (e.target.closest('[data-sidebar-backdrop]')) root.classList.remove('nav-open');
        if (isMobile() && e.target.closest('.sidebar .nav-link')) root.classList.remove('nav-open');
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var open = document.querySelectorAll('.modal-overlay.active');
        if (open.length) { window.closeModal(open[open.length - 1]); return; }
        root.classList.remove('nav-open');
    });

    /* Konfirmasi: <form data-confirm="Yakin?"> */
    document.addEventListener('submit', function (e) {
        var msg = e.target.dataset ? e.target.dataset.confirm : null;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    /* ---------- Toast ---------- */
    window.toast = function (message, type) {
        var wrap = document.getElementById('toastWrap');
        if (!wrap) return;
        var el = document.createElement('div');
        el.className = 'toast ' + (type || '');
        el.setAttribute('role', 'status');
        el.textContent = message;
        wrap.appendChild(el);
        setTimeout(function () { el.remove(); }, 4200);
    };
    window.toastAfterReload = function (message, type) {
        try { sessionStorage.setItem('arkamas_toast', JSON.stringify({ m: message, t: type || 'success' })); } catch (e) {}
    };

    /* ---------- Fetch JSON (CSRF otomatis) ---------- */
    window.postJSON = function (url, data, method) {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return fetch(url, {
            method: method || 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': meta ? meta.content : ''
            },
            body: JSON.stringify(data || {})
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                if (!res.ok) {
                    var err = body || {};
                    if (!err.message) err.message = 'Terjadi kesalahan pada sistem (' + res.status + '). Silakan coba lagi.';
                    if (res.status === 419) err.message = 'Sesi Anda telah berakhir. Muat ulang halaman lalu coba lagi.';
                    throw err;
                }
                return body;
            });
        });
    };
    window.errorText = function (err) {
        if (err && err.errors) return Object.keys(err.errors).map(function (k) { return err.errors[k][0]; }).join('\n');
        return (err && err.message) || 'Terjadi kesalahan.';
    };

    /* ---------- Format & escape ---------- */
    window.rupiah = function (n) { return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID'); };
    window.esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('.modal-overlay.active')) root.classList.add('modal-open');
        try {
            var t = sessionStorage.getItem('arkamas_toast');
            if (t) { sessionStorage.removeItem('arkamas_toast'); t = JSON.parse(t); window.toast(t.m, t.t); }
        } catch (e) {}
    });
})();
