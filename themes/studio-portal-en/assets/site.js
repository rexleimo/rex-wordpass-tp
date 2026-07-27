(function () {
    'use strict';

    const header = document.querySelector('[data-en-header]');
    const toggle = header ? header.querySelector('.en-menu-toggle') : null;
    const navigation = header ? header.querySelector('.en-nav') : null;

    const closeMenu = function () {
        if (!header || !toggle) {
            return;
        }
        header.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open menu');
        document.body.classList.remove('en-menu-open');
    };

    if (header && toggle && navigation) {
        toggle.addEventListener('click', function () {
            const isOpen = header.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
            document.body.classList.toggle('en-menu-open', isOpen);
        });
        navigation.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                closeMenu();
            }
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 1100) {
                closeMenu();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    }

    document.querySelectorAll('[data-accordion]').forEach(function (accordion) {
        accordion.addEventListener('click', function (event) {
            const button = event.target.closest('button');
            const item = button ? button.closest('article') : null;
            if (!button || !item) {
                return;
            }
            const wasOpen = item.classList.contains('is-open');
            accordion.querySelectorAll('article').forEach(function (entry) {
                entry.classList.remove('is-open');
                const entryButton = entry.querySelector('button');
                if (entryButton) {
                    entryButton.setAttribute('aria-expanded', 'false');
                }
            });
            if (!wasOpen) {
                item.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.querySelectorAll('[data-work-browser]').forEach(function (browser) {
        const buttons = Array.from(browser.querySelectorAll('[data-filter]'));
        const search = browser.querySelector('[data-work-search]');
        const projects = Array.from(browser.querySelectorAll('[data-project]'));
        const counter = browser.querySelector('[data-result-count]');
        let activeFilter = 'all';

        const update = function () {
            const query = search ? search.value.trim().toLowerCase() : '';
            let visible = 0;
            projects.forEach(function (project) {
                const keywords = project.dataset.keywords || '';
                const filterTerm = activeFilter.replace(/s$/, '');
                const matchesFilter = activeFilter === 'all' || keywords.includes(filterTerm);
                const matchesQuery = !query || keywords.includes(query);
                project.hidden = !(matchesFilter && matchesQuery);
                if (!project.hidden) {
                    visible += 1;
                }
            });
            if (counter) {
                counter.textContent = String(visible).padStart(2, '0');
            }
        };

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                activeFilter = button.dataset.filter || 'all';
                buttons.forEach(function (entry) {
                    entry.classList.toggle('is-active', entry === button);
                });
                update();
            });
        });
        if (search) {
            let timer;
            search.addEventListener('input', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(update, 150);
            });
        }
    });

    const ticker = document.querySelector('.en-ticker > div');
    if (ticker && ticker.children.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        Array.from(ticker.children).forEach(function (item) {
            ticker.appendChild(item.cloneNode(true));
        });
    }

    const toc = document.querySelector('.en-article-toc');
    if (toc && 'IntersectionObserver' in window) {
        const links = Array.from(toc.querySelectorAll('a'));
        const sections = links.map(function (link) {
            return document.querySelector(link.getAttribute('href'));
        }).filter(Boolean);
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                links.forEach(function (link) {
                    link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id);
                });
            });
        }, { rootMargin: '-15% 0px -70% 0px' });
        sections.forEach(function (section) {
            observer.observe(section);
        });
    }
}());
