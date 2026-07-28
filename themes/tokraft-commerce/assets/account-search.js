(function () {
  'use strict';

  const overlay = document.getElementById('tokraft-search-overlay');
  if (!overlay) return;

  const openers = Array.from(document.querySelectorAll('[data-search-open]'));
  const closers = Array.from(overlay.querySelectorAll('[data-search-close]'));
  const searchField = overlay.querySelector('input[type="search"]');
  let returnFocus = null;

  const setExpanded = (expanded) => {
    openers.forEach((opener) => opener.setAttribute('aria-expanded', expanded ? 'true' : 'false'));
  };

  const openSearch = (opener) => {
    const sourceDrawer = opener && opener.closest('#site-drawer');
    const menuToggle = document.querySelector('.header-menu-toggle');
    returnFocus = sourceDrawer ? menuToggle : (opener || document.activeElement);
    if (sourceDrawer) sourceDrawer.querySelector('.drawer-close')?.click();
    overlay.hidden = false;
    document.body.classList.add('search-overlay-open');
    setExpanded(true);
    window.requestAnimationFrame(() => searchField && searchField.focus());
  };

  const closeSearch = () => {
    overlay.hidden = true;
    document.body.classList.remove('search-overlay-open');
    setExpanded(false);
    if (returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus();
  };

  openers.forEach((opener) => opener.addEventListener('click', () => openSearch(opener)));
  closers.forEach((closer) => closer.addEventListener('click', closeSearch));

  document.addEventListener('keydown', (event) => {
    if (overlay.hidden) return;
    if (event.key === 'Escape') {
      closeSearch();
      return;
    }
    if (event.key !== 'Tab') return;

    const focusable = Array.from(overlay.querySelectorAll('a[href], button:not([disabled]), input:not([disabled])'));
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
}());
