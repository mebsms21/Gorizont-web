/* ГОРИЗОНТ: единая система движения восхода. Без внешних библиотек. */
(() => {
  'use strict';
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const intro = document.querySelector('.horizon-intro');
  let seen = false;
  try { seen = sessionStorage.getItem('gorizont-dawn-v2') === 'seen'; sessionStorage.setItem('gorizont-dawn-v2', 'seen'); } catch (_) {}
  const dismiss = () => { if (intro) intro.classList.add('dismissed'); };
  if (seen || reduce) dismiss();
  else { intro?.querySelector('.intro-skip')?.removeAttribute('tabindex'); setTimeout(dismiss, 2400); }
  intro?.querySelector('.intro-skip')?.addEventListener('click', dismiss);
  let active = null, trigger = null, closeTimer;
  const finishClose = () => {
    if (!active) return;
    active.classList.remove('is-menu-open', 'has-modal-open', 'is-menu-closing');
    active.removeAttribute('role'); active.removeAttribute('aria-modal');
    document.documentElement.classList.remove('has-modal-open');
    trigger?.setAttribute('aria-expanded', 'false');
    trigger?.focus(); active = null;
  };
  const close = () => {
    if (!active) return;
    active.classList.add('is-menu-closing');
    clearTimeout(closeTimer); closeTimer = setTimeout(finishClose, reduce ? 0 : 450);
  };
  document.querySelectorAll('.wp-block-navigation__responsive-container-open').forEach(button => button.setAttribute('aria-expanded', 'false'));
  window.addEventListener('click', event => {
    const open = event.target.closest('.wp-block-navigation__responsive-container-open');
    const shut = event.target.closest('.wp-block-navigation__responsive-container-close');
    if (!open && !shut) return;
    event.preventDefault(); event.stopImmediatePropagation();
    if (shut) { close(); return; }
    clearTimeout(closeTimer); dismiss(); trigger = open;
    active = open.closest('nav').querySelector('.wp-block-navigation__responsive-container');
    if (!active) return;
    active.classList.remove('is-menu-closing'); active.classList.add('is-menu-open', 'has-modal-open');
    active.setAttribute('role', 'dialog'); active.setAttribute('aria-modal', 'true'); active.setAttribute('aria-label', 'Меню сайта');
    document.documentElement.classList.add('has-modal-open'); open.setAttribute('aria-expanded', 'true');
    if (!active.querySelector('.menu-caption')) { const label = document.createElement('p'); label.className = 'menu-caption'; label.textContent = 'ГОРИЗОНТ · За пределами привычного'; active.append(label); }
    active.querySelector('button, a')?.focus();
  }, true);
  window.addEventListener('keydown', event => {
    if (!active) return;
    if (event.key === 'Escape') { event.preventDefault(); event.stopImmediatePropagation(); close(); }
    if (event.key === 'Tab') {
      const items = [...active.querySelectorAll('a[href],button')].filter(item => item.getClientRects().length);
      const first = items[0], last = items.at(-1);
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
  }, true);
  if (!reduce && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('g-rise-ready');
    const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.remove('waiting'); entry.target.classList.add('visible'); observer.unobserve(entry.target); } }), {threshold: .08});
    document.querySelectorAll('.section-v2 > .wp-block-heading, .section-v2 > .eyebrow, .g-card, .benefit-grid > .wp-block-column, .contact-band').forEach((item, index) => {
      item.dataset.horizonReveal = ''; item.style.setProperty('--reveal-delay', `${(index % 3) * 70}ms`); item.classList.add('waiting'); observer.observe(item);
    });
  }
  if (!reduce && matchMedia('(hover: hover) and (pointer: fine)').matches) {
    const art = document.querySelector('.hero-art');
    const hero = document.querySelector('.hero-v2');
    hero?.addEventListener('pointermove', event => { const bounds = hero.getBoundingClientRect(); art?.style.setProperty('--pointer-x', `${((event.clientX-bounds.left)/bounds.width-.5)*7}deg`); art?.style.setProperty('--pointer-y', `${-((event.clientY-bounds.top)/bounds.height-.5)*4}deg`); }, {passive:true});
    hero?.addEventListener('pointerleave', () => { art?.style.setProperty('--pointer-x','0deg'); art?.style.setProperty('--pointer-y','0deg'); });
  }
  document.querySelectorAll('.g-brief-form').forEach(form => form.addEventListener('submit', event => {
    event.preventDefault(); if (!form.reportValidity()) return;
    const data = new FormData(form);
    const brief = `Бриф проекта — ГОРИЗОНТ\n\nИмя: ${data.get('name')}\nПочта: ${data.get('email')}\nТип проекта: ${data.get('service')}\n\nЗадача:\n${data.get('message')}`;
    const a = document.createElement('a'); const url = URL.createObjectURL(new Blob([brief],{type:'text/plain;charset=utf-8'})); a.href=url; a.download='Бриф-проекта.txt'; a.click(); setTimeout(()=>URL.revokeObjectURL(url),1000);
    form.querySelector('[role="status"]').textContent='Бриф сохранён в файл на вашем устройстве. Данные никуда не отправлялись.';
  }));
})();
