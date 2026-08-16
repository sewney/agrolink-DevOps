(function () {
  'use strict';

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
  }

  function ensureStyles() {
    if (document.getElementById('system-dialog-styles')) return;
    const style = document.createElement('style');
    style.id = 'system-dialog-styles';
    style.textContent = `
      .system-dialog-overlay{
        position:fixed; inset:0; background:rgba(0,0,0,.45);
        display:none; align-items:center; justify-content:center;
        z-index:9999; padding:18px;
      }
      .system-dialog{
        width:min(520px, 95vw); background:#fff; border-radius:10px;
        box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden;
        font-family:inherit;
      }
      .system-dialog__header{
        display:flex; justify-content:space-between; align-items:center;
        padding:14px 16px; border-bottom:1px solid #e5e7eb;
      }
      .system-dialog__title{ margin:0; font-size:16px; font-weight:700; color:#111827; }
      .system-dialog__close{
        border:0; background:transparent; font-size:20px; cursor:pointer; color:#6b7280;
      }
      .system-dialog__body{ padding:16px; color:#111827; font-size:14px; line-height:1.5; }
      .system-dialog__footer{
        padding:12px 16px; border-top:1px solid #e5e7eb;
        display:flex; justify-content:flex-end; gap:10px;
      }
      .system-dialog__btn{
        border:1px solid #d1d5db; background:#fff; color:#111827;
        padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;
      }
      .system-dialog__btn.primary{
        background:#16a34a; border-color:#16a34a; color:#fff;
      }
      .system-dialog__input{
        width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px;
        font-size:14px;
      }
      .system-dialog__label{ display:block; margin:10px 0 6px; font-weight:700; color:#111827; }
    `;
    document.head.appendChild(style);
  }

  function ensureDialog() {
    ensureStyles();
    let overlay = document.getElementById('systemDialogOverlay');
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.id = 'systemDialogOverlay';
    overlay.className = 'system-dialog-overlay';
    overlay.innerHTML = `
      <div class="system-dialog" role="dialog" aria-modal="true" aria-labelledby="systemDialogTitle">
        <div class="system-dialog__header">
          <h3 class="system-dialog__title" id="systemDialogTitle">Notice</h3>
          <button type="button" class="system-dialog__close" id="systemDialogCloseBtn">✕</button>
        </div>
        <div class="system-dialog__body" id="systemDialogBody"></div>
        <div class="system-dialog__footer" id="systemDialogFooter"></div>
      </div>
    `;
    document.body.appendChild(overlay);
    return overlay;
  }

  let resolver = null;

  function hide(val) {
    const overlay = document.getElementById('systemDialogOverlay');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
    const r = resolver;
    resolver = null;
    if (typeof r === 'function') r(val);
  }

  function show({ title = 'Notice', bodyHtml = '', actions = [], onClose = null } = {}) {
    const overlay = ensureDialog();
    const titleEl = document.getElementById('systemDialogTitle');
    const bodyEl = document.getElementById('systemDialogBody');
    const footerEl = document.getElementById('systemDialogFooter');
    const closeBtn = document.getElementById('systemDialogCloseBtn');

    titleEl.textContent = String(title || 'Notice');
    bodyEl.innerHTML = bodyHtml || '';
    footerEl.innerHTML = '';

    const cleanup = () => {
      closeBtn.onclick = null;
      overlay.onclick = null;
      document.removeEventListener('keydown', onKeyDown);
    };

    const resolveWith = (val) => {
      cleanup();
      hide(val);
    };

    const onKeyDown = (e) => {
      if (e.key === 'Escape') {
        resolveWith(typeof onClose === 'function' ? onClose() : null);
      }
    };

    closeBtn.onclick = () => resolveWith(typeof onClose === 'function' ? onClose() : null);
    overlay.onclick = (e) => {
      if (e.target === overlay) resolveWith(typeof onClose === 'function' ? onClose() : null);
    };

    actions.forEach(a => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = `system-dialog__btn${a.primary ? ' primary' : ''}`;
      btn.textContent = a.label || 'OK';
      btn.onclick = () => resolveWith(a.value);
      footerEl.appendChild(btn);
    });

    document.addEventListener('keydown', onKeyDown);
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    return new Promise((resolve) => { resolver = resolve; });
  }

  async function systemAlert(message, title = 'Notice') {
    const bodyHtml = `<p style="margin:0;">${escapeHtml(message)}</p>`;
    await show({
      title,
      bodyHtml,
      actions: [{ label: 'OK', value: true, primary: true }],
      onClose: () => true,
    });
  }

  async function systemConfirm(message, title = 'Confirm') {
    const bodyHtml = `<p style="margin:0;">${escapeHtml(message)}</p>`;
    const ok = await show({
      title,
      bodyHtml,
      actions: [
        { label: 'Cancel', value: false },
        { label: 'Confirm', value: true, primary: true },
      ],
      onClose: () => false,
    });
    return Boolean(ok);
  }

  async function systemPrompt({ title = 'Input', message = '', label = '', placeholder = '', defaultValue = '', required = false, multiline = false } = {}) {
    const inputId = 'systemDialogPromptInput';
    const bodyHtml = `
      ${message ? `<p style="margin:0 0 10px;">${escapeHtml(message)}</p>` : ''}
      ${label ? `<label class="system-dialog__label" for="${inputId}">${escapeHtml(label)}</label>` : ''}
      ${multiline
        ? `<textarea id="${inputId}" class="system-dialog__input" rows="3" placeholder="${escapeHtml(placeholder)}">${escapeHtml(defaultValue)}</textarea>`
        : `<input id="${inputId}" class="system-dialog__input" type="text" value="${escapeHtml(defaultValue)}" placeholder="${escapeHtml(placeholder)}" />`
      }
      ${required ? `<div style="margin-top:6px;font-size:12px;color:#6b7280;">This field is required.</div>` : ''}
    `;

    const result = await show({
      title,
      bodyHtml,
      actions: [
        { label: 'Cancel', value: null },
        { label: 'OK', value: '__ok__', primary: true },
      ],
      onClose: () => null,
    });

    if (result !== '__ok__') return null;
    const input = document.getElementById(inputId);
    const value = input ? String(input.value || '').trim() : '';
    if (required && !value) return null;
    return value;
  }

  function systemConfirmNavigate(e, message, title = 'Confirm') {
    try { if (e && typeof e.preventDefault === 'function') e.preventDefault(); } catch (_) {}
    const link = e && e.currentTarget ? e.currentTarget : null;
    const href = link && link.getAttribute ? link.getAttribute('href') : null;
    systemConfirm(message, title).then(ok => {
      if (ok && href) window.location.href = href;
    });
    return false;
  }

  window.systemAlert = systemAlert;
  window.systemConfirm = systemConfirm;
  window.systemPrompt = systemPrompt;
  window.systemConfirmNavigate = systemConfirmNavigate;
})();

