(function () {
    'use strict';

    if (!window.gsap || !window.ScrollTrigger) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reducedMotion.matches) {
        return;
    }

    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    const motion = gsap.matchMedia();

    gsap.registerPlugin(ScrollTrigger);
    document.documentElement.classList.add('sp-motion-active');

    const elements = function (selector, scope) {
        return gsap.utils.toArray(selector, scope || document);
    };

    const createScrollProgress = function () {
        const progress = document.createElement('div');
        const fill = document.createElement('span');

        progress.className = 'sp-scroll-progress';
        progress.setAttribute('aria-hidden', 'true');
        progress.appendChild(fill);
        document.body.appendChild(progress);

        ScrollTrigger.create({
            start: 0,
            end: 'max',
            onUpdate: function (self) {
                gsap.set(fill, { scaleX: self.progress });
            },
        });
    };

    const animateHeader = function () {
        const headerItems = elements('.sp-header .sp-brand, .sp-header .sp-nav > *, .sp-header .sp-header-cta, .sp-header .sp-menu-toggle');
        if (!headerItems.length) {
            return;
        }

        gsap.from(headerItems, {
            autoAlpha: 0,
            y: -14,
            duration: 0.62,
            stagger: 0.055,
            ease: 'power3.out',
            clearProps: 'opacity,visibility,transform',
        });
    };

    const animatePageIntro = function () {
        const homeTitle = document.querySelector('.sp-news-hero .sp-hero-heading');
        const innerHero = document.querySelector('.sp-inner-hero-grid');
        const articleHead = document.querySelector('.sp-article-head');

        if (homeTitle) {
            const timeline = gsap.timeline({ defaults: { ease: 'power3.out' }, delay: 0.08 });
            timeline
                .from('.sp-channel-bar', { autoAlpha: 0, y: -12, duration: 0.5 })
                .from('.sp-hero-heading .sp-eyebrow', { autoAlpha: 0, y: 18, duration: 0.48 }, '-=0.24')
                .from('.sp-hero-heading h1', { autoAlpha: 0, y: 42, duration: 0.86 }, '-=0.2')
                .from('.sp-hero-heading > p', { autoAlpha: 0, y: 26, duration: 0.65 }, '-=0.54')
                .from('.sp-lead-image', { autoAlpha: 0, scale: 0.965, y: 30, duration: 0.82 }, '-=0.34')
                .from('.sp-lead-copy > *', { autoAlpha: 0, y: 24, duration: 0.62, stagger: 0.075 }, '-=0.58')
                .from('.sp-hero-side .sp-side-story', { autoAlpha: 0, x: 28, duration: 0.68, stagger: 0.12 }, '-=0.68');
            return;
        }

        if (innerHero) {
            gsap.timeline({ defaults: { ease: 'power3.out' }, delay: 0.06 })
                .from('.sp-inner-eyebrow', { autoAlpha: 0, y: 16, duration: 0.48 })
                .from('.sp-inner-hero h1', { autoAlpha: 0, y: 42, duration: 0.86 }, '-=0.2')
                .from('.sp-inner-hero-note > *', { autoAlpha: 0, x: 26, duration: 0.62, stagger: 0.08 }, '-=0.52')
                .from('.sp-topic-tabs', { autoAlpha: 0, y: 14, duration: 0.52 }, '-=0.34');
            return;
        }

        if (articleHead) {
            gsap.timeline({ defaults: { ease: 'power3.out' }, delay: 0.06 })
                .from('.sp-article-breadcrumb, .sp-article-head .sp-kicker', { autoAlpha: 0, y: 14, duration: 0.48, stagger: 0.08 })
                .from('.sp-article-head h1', { autoAlpha: 0, y: 40, duration: 0.84 }, '-=0.25')
                .from('.sp-article-deck, .sp-article-meta', { autoAlpha: 0, y: 22, duration: 0.62, stagger: 0.1 }, '-=0.5')
                .from('.sp-article-cover', { autoAlpha: 0, scale: 0.98, y: 28, duration: 0.78 }, '-=0.28');
        }
    };

    const revealGroup = function (containerSelector, itemSelector, options) {
        elements(containerSelector).forEach(function (container) {
            const items = itemSelector ? elements(itemSelector, container) : [container];
            if (!items.length) {
                return;
            }

            gsap.from(items, {
                autoAlpha: 0,
                y: (options && options.y) || 36,
                x: (options && options.x) || 0,
                duration: (options && options.duration) || 0.74,
                stagger: (options && options.stagger) || 0.085,
                ease: 'power3.out',
                clearProps: 'opacity,visibility,transform',
                scrollTrigger: {
                    trigger: container,
                    start: 'top 86%',
                    once: true,
                },
            });
        });
    };

    const animateRevealGroups = function () {
        revealGroup('.sp-briefing-grid', '.sp-brief-list > a', { y: 18, stagger: 0.06 });
        revealGroup('.sp-content-section', '.sp-editorial-heading');
        revealGroup('.sp-article-feed', '.sp-feed-card', { y: 42, stagger: 0.11 });
        revealGroup('.sp-feed-sidebar', ':scope > section', { y: 28, stagger: 0.12 });
        revealGroup('.sp-self-hosted-grid', '.sp-self-hosted-copy > *, .sp-self-hosted-feature', { y: 38, stagger: 0.09 });
        revealGroup('.sp-topic-directory', '.sp-editorial-heading');
        revealGroup('.sp-topic-cards', ':scope > a', { y: 34, stagger: 0.075 });
        revealGroup('.sp-home-newsletter .sp-container', ':scope > div', { y: 34, stagger: 0.12 });

        revealGroup('.sp-inner-section', '.sp-inner-section-head');
        revealGroup('.sp-inner-featured-row', ':scope > *', { y: 34, stagger: 0.12 });
        revealGroup('.sp-inner-card-grid', ':scope > .sp-inner-card', { y: 40, stagger: 0.09 });
        revealGroup('.sp-topic-directory-grid', ':scope > a', { y: 38, stagger: 0.09 });
        revealGroup('.sp-guide-paths', ':scope > article', { y: 42, stagger: 0.11 });
        revealGroup('.sp-principle-grid', ':scope > article', { y: 36, stagger: 0.1 });
        revealGroup('.sp-editorial-process', 'header, li', { y: 32, stagger: 0.08 });
        revealGroup('.sp-work-editorial', ':scope > a', { y: 26, stagger: 0.07 });
        revealGroup('.sp-contact-editorial', ':scope > aside, :scope > .sp-contact-form-wrap', { y: 36, stagger: 0.14 });
        revealGroup('.sp-inner-dark-band .sp-inner-container', ':scope > *', { y: 32, stagger: 0.09 });
        revealGroup('.sp-inner-cta', '.sp-inner-container > *', { y: 30, stagger: 0.08 });

        revealGroup('.sp-article-layout', ':scope > .sp-article-toc, :scope > .sp-article-content', { y: 30, stagger: 0.12 });
        revealGroup('.sp-article-content', ':scope > h2, :scope > h3, :scope > figure, :scope > blockquote', { y: 28, stagger: 0.06 });
        revealGroup('.sp-article-tags', ':scope > a', { y: 18, stagger: 0.06 });
    };

    const animateMedia = function () {
        const wrappers = elements('.sp-lead-image, .sp-side-image, .sp-feed-image, .sp-self-hosted-feature, .sp-inner-card-media, .sp-article-cover, .sp-inline-figure');

        wrappers.forEach(function (wrapper) {
            const media = wrapper.querySelector('img, .sp-image-fallback, .sp-inner-card-fallback');
            if (!media) {
                return;
            }

            media.classList.add('sp-motion-media');
            gsap.fromTo(media, {
                scale: 1.055,
                yPercent: -1.5,
            }, {
                scale: 1,
                yPercent: 1.5,
                ease: 'none',
                scrollTrigger: {
                    trigger: wrapper,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.7,
                },
            });
        });
    };

    const enableMagnetism = function () {
        elements('.sp-button, .sp-header-cta, .sp-editorial-button, .sp-inner-cta a').forEach(function (button) {
            const moveX = gsap.quickTo(button, 'x', { duration: 0.36, ease: 'power3.out' });
            const moveY = gsap.quickTo(button, 'y', { duration: 0.36, ease: 'power3.out' });

            button.classList.add('sp-is-magnetic');
            button.addEventListener('pointermove', function (event) {
                const bounds = button.getBoundingClientRect();
                moveX((event.clientX - bounds.left - bounds.width / 2) * 0.16);
                moveY((event.clientY - bounds.top - bounds.height / 2) * 0.2);
            });
            button.addEventListener('pointerleave', function () {
                moveX(0);
                moveY(0);
            });
        });
    };

    createScrollProgress();
    animateHeader();
    animatePageIntro();
    animateRevealGroups();

    motion.add('(min-width: 821px) and (prefers-reduced-motion: no-preference)', function () {
        animateMedia();
    });

    motion.add('(min-width: 1024px) and (hover: hover) and (pointer: fine)', function () {
        enableMagnetism();
    });

    window.addEventListener('load', function () {
        ScrollTrigger.refresh();
    }, { once: true });
}());
