// assets/js/notify.js — Toast notification system

(function () {
  const container = (() => {
    let c = document.getElementById('toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'toast-container';
      Object.assign(c.style, {
        position: 'fixed', bottom: '1.5rem', right: '1.5rem',
        zIndex: '9999', display: 'flex', flexDirection: 'column', gap: '.5rem'
      });
      document.body.appendChild(c);
    }
    return c;
  })();

  /**
   * Show a toast notification.
   * @param {string} message
   * @param {'success'|'error'|'info'} type
   * @param {number} duration ms
   */
  window.notify = function (message, type = 'success', duration = 3000) {
    const toast = document.createElement('div');
    const colors = {
      success: { bg: '#d1fae5', color: '#065f46', border: '#a7f3d0' },
      error:   { bg: '#fee2e2', color: '#991b1b', border: '#fca5a5' },
      info:    { bg: '#dbeafe', color: '#1e40af', border: '#93c5fd' }
    };
    const c = colors[type] || colors.info;

    Object.assign(toast.style, {
      background: c.bg, color: c.color, border: `1px solid ${c.border}`,
      padding: '.7rem 1.1rem', borderRadius: '10px',
      fontSize: '.88rem', fontWeight: '500',
      boxShadow: '0 4px 16px rgba(0,0,0,.12)',
      transform: 'translateX(120%)', transition: 'transform .25s ease',
      maxWidth: '320px', lineHeight: '1.4'
    });
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; });
    });

    setTimeout(() => {
      toast.style.transform = 'translateX(120%)';
      toast.addEventListener('transitionend', () => toast.remove());
    }, duration);
  };

  // Dark mode toggle
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('darkModeBtn');
    if (!btn) return;
    if (localStorage.getItem('dark') === '1') document.body.classList.add('dark-mode');
    btn.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('dark', document.body.classList.contains('dark-mode') ? '1' : '0');
    });
  });
})();
