(function () {
    'use strict';

    const header = document.querySelector('[data-site-header]');
    if (!header) {
        return;
    }

    const toggle = header.querySelector('.sp-menu-toggle');
    const navigation = header.querySelector('.sp-nav');
    if (!toggle || !navigation) {
        return;
    }

    const closeMenu = function () {
        header.classList.remove('is-menu-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('sp-menu-is-open');
    };

    toggle.addEventListener('click', function () {
        const isOpen = header.classList.toggle('is-menu-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
        document.body.classList.toggle('sp-menu-is-open', isOpen);
    });

    navigation.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 820) {
            closeMenu();
        }
    });
}());
