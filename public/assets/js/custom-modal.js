/**
 * Custom Modal System
 * Replaces all browser-native alert() and confirm() dialogs with
 * styled, consistent modals that match the MediCare UI.
 *
 * Public API:
 *   customAlert(message, title, type)          — type: 'info'|'success'|'warning'|'error'
 *   customConfirm(message, title, onConfirm, onCancel)
 *
 * Auto-feature:
 *   Any element with data-confirm="question" will automatically intercept
 *   clicks and show a confirm modal before proceeding.
 *   Optional: data-confirm-title="Custom Title"
 */
(function () {
    'use strict';

    var overlay, iconEl, titleEl, msgEl, btnsEl;

    /* ─── Build modal DOM once ─────────────────────────────────── */
    function build() {
        if (document.getElementById('_mc_overlay')) return;

        var css = document.createElement('style');
        css.textContent =
            '#_mc_overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999999;' +
            'display:none;align-items:center;justify-content:center;padding:16px;}' +
            '#_mc_overlay.open{display:flex;}' +
            '@keyframes _mc_in{from{opacity:0;transform:translateY(-18px)}to{opacity:1;transform:translateY(0)}}' +
            '#_mc_box{background:#fff;border-radius:16px;padding:32px 28px 24px;max-width:420px;width:100%;' +
            'box-shadow:0 24px 64px rgba(0,0,0,.22);animation:_mc_in .2s ease;text-align:center;}' +
            '#_mc_icon{margin-bottom:14px;}' +
            '#_mc_title{font-size:18px;font-weight:700;color:#111827;margin:0 0 8px;font-family:inherit;}' +
            '#_mc_msg{font-size:14px;color:#6B7280;margin:0 0 24px;line-height:1.6;font-family:inherit;}' +
            '#_mc_btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}' +
            '#_mc_btns button{padding:10px 28px;border:none;border-radius:8px;font-size:14px;font-weight:600;' +
            'cursor:pointer;transition:opacity .15s;font-family:inherit;}' +
            '#_mc_btns button:hover{opacity:.82;}' +
            '#_mc_btn_cancel{background:#F3F4F6;color:#374151;}' +
            '#_mc_btn_ok{background:#3B82F6;color:#fff;}' +
            '#_mc_btn_confirm{background:#EF4444;color:#fff;}';
        document.head.appendChild(css);

        overlay = document.createElement('div');
        overlay.id = '_mc_overlay';
        overlay.innerHTML =
            '<div id="_mc_box">' +
            '<div id="_mc_icon"></div>' +
            '<h3 id="_mc_title"></h3>' +
            '<p id="_mc_msg"></p>' +
            '<div id="_mc_btns"></div>' +
            '</div>';
        document.body.appendChild(overlay);

        iconEl  = document.getElementById('_mc_icon');
        titleEl = document.getElementById('_mc_title');
        msgEl   = document.getElementById('_mc_msg');
        btnsEl  = document.getElementById('_mc_btns');

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('open')) close();
        });
    }

    function close() { overlay && overlay.classList.remove('open'); }

    /* ─── SVG icons ────────────────────────────────────────────── */
    function icon(color, d) {
        return '<svg xmlns="http://www.w3.org/2000/svg" style="width:52px;height:52px;color:' + color +
            '" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="' + d + '"/></svg>';
    }
    var ICONS = {
        error:   icon('#EF4444', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'),
        success: icon('#10B981', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
        warning: icon('#F59E0B', 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'),
        info:    icon('#3B82F6', 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'),
        confirm: icon('#F59E0B', 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'),
    };

    /* ─── Core open ────────────────────────────────────────────── */
    function open(title, msg, type, buttons) {
        if (!overlay) build();
        titleEl.textContent = title;
        msgEl.textContent   = msg;
        iconEl.innerHTML    = ICONS[type] || ICONS.info;
        btnsEl.innerHTML    = '';
        buttons.forEach(function (def) {
            var btn = document.createElement('button');
            btn.textContent = def.label;
            btn.id = def.id || '';
            btn.onclick = function () { close(); if (def.cb) def.cb(); };
            btnsEl.appendChild(btn);
        });
        overlay.classList.add('open');
        var first = btnsEl.querySelector('button');
        if (first) setTimeout(function () { first.focus(); }, 60);
    }

    /* ─── Public API ───────────────────────────────────────────── */
    window.customAlert = function (msg, title, type) {
        var t = title || ({ error: 'Error', success: 'Success', warning: 'Warning', info: 'Notice' }[type] || 'Notice');
        open(t, msg, type || 'info', [{ label: 'OK', id: '_mc_btn_ok' }]);
    };

    window.customConfirm = function (msg, title, onConfirm, onCancel) {
        open(title || 'Confirm Action', msg, 'confirm', [
            { label: 'Cancel',  id: '_mc_btn_cancel',  cb: onCancel  || null },
            { label: 'Confirm', id: '_mc_btn_confirm', cb: onConfirm || null },
        ]);
    };

    /* ─── Auto-intercept [data-confirm] elements ───────────────── */
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        e.preventDefault();
        e.stopImmediatePropagation();

        var msg   = el.getAttribute('data-confirm');
        var title = el.getAttribute('data-confirm-title') || 'Confirm Action';
        var form  = el.closest('form');

        window.customConfirm(msg, title, function () {
            if (form) {
                // Carry the button's name/value (form.submit() skips submit-button values)
                if (el.name && el.value) {
                    var existing = form.querySelector('input[data-mc-inject]');
                    if (!existing) {
                        existing = document.createElement('input');
                        existing.type = 'hidden';
                        existing.setAttribute('data-mc-inject', '1');
                        form.appendChild(existing);
                    }
                    existing.name  = el.name;
                    existing.value = el.value;
                }
                form.submit();
            } else if (el.tagName === 'A' && el.href) {
                window.location.href = el.href;
            }
        });
    }, true);

    /* ─── Initialise ───────────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', build);
    } else {
        build();
    }
}());
