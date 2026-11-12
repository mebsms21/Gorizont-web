/* Мобильное меню статической демоверсии; WordPress остаётся в исходном проекте. */
(() => {
  let active = null;
  let trigger = null;
  const close = () => {
    if (!active) return;
    active.classList.remove('is-menu-open', 'has-modal-open');
    active.removeAttribute('role');
    active.removeAttribute('aria-modal');
    document.documentElement.classList.remove('has-modal-open');
    trigger?.setAttribute('aria-expanded', 'false');
    trigger?.focus();
    active = null;
  };
  document.querySelectorAll('.wp-block-navigation__responsive-container-open').forEach(button => {
    button.setAttribute('aria-expanded', 'false');
    button.addEventListener('click', () => {
      trigger = button;
      active = button.closest('nav').querySelector('.wp-block-navigation__responsive-container');
      if (!active) return;
      active.classList.add('is-menu-open', 'has-modal-open');
      active.setAttribute('role', 'dialog');
      active.setAttribute('aria-modal', 'true');
      active.setAttribute('aria-label', 'Меню сайта');
      document.documentElement.classList.add('has-modal-open');
      button.setAttribute('aria-expanded', 'true');
      active.querySelector('button, a')?.focus();
    });
  });
  document.querySelectorAll('.wp-block-navigation__responsive-container-close').forEach(button => button.addEventListener('click', close));
  document.addEventListener('keydown', event => {
    if (!active) return;
    if (event.key === 'Escape') { event.preventDefault(); close(); }
    if (event.key === 'Tab') {
      const items = [...active.querySelectorAll('a[href],button')].filter(item => item.getClientRects().length);
      const first = items[0], last = items.at(-1);
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
  });
})();
