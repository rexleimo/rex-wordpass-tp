(function () {
    'use strict';

    if (!window.gsap || !window.ScrollTrigger || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    gsap.registerPlugin(ScrollTrigger);
    document.documentElement.classList.add('en-motion-ready');

    const progress = document.createElement('div');
    const progressFill = document.createElement('span');
    progress.className = 'en-scroll-progress';
    progress.setAttribute('aria-hidden', 'true');
    progress.appendChild(progressFill);
    document.body.appendChild(progress);
    ScrollTrigger.create({
        start: 0,
        end: 'max',
        onUpdate: function (self) {
            gsap.set(progressFill, { scaleX: self.progress });
        },
    });

    const headerItems = gsap.utils.toArray('.en-header > *');
    gsap.from(headerItems, {
        y: -18,
        autoAlpha: 0,
        duration: 0.56,
        stagger: 0.05,
        ease: 'power3.out',
        clearProps: 'transform,opacity,visibility',
    });

    const heroItems = gsap.utils.toArray('[data-hero-item]');
    if (heroItems.length) {
        gsap.from(heroItems, {
            y: 42,
            autoAlpha: 0,
            duration: 0.86,
            stagger: 0.12,
            delay: 0.08,
            ease: 'power3.out',
            clearProps: 'transform,opacity,visibility',
        });
    }

    gsap.utils.toArray('[data-reveal]').forEach(function (element) {
        gsap.from(element, {
            y: 42,
            autoAlpha: 0,
            duration: 0.78,
            ease: 'power3.out',
            clearProps: 'transform,opacity,visibility',
            scrollTrigger: { trigger: element, start: 'top 86%', once: true },
        });
    });

    gsap.utils.toArray('[data-stagger]').forEach(function (group) {
        gsap.from(Array.from(group.children), {
            y: 34,
            autoAlpha: 0,
            duration: 0.7,
            stagger: 0.075,
            ease: 'power3.out',
            clearProps: 'transform,opacity,visibility',
            scrollTrigger: { trigger: group, start: 'top 86%', once: true },
        });
    });

    const motion = gsap.matchMedia();
    motion.add('(min-width: 781px) and (hover: hover)', function () {
        gsap.utils.toArray('[data-media]').forEach(function (frame) {
            const image = frame.querySelector('img');
            if (!image) {
                return;
            }
            image.classList.add('en-motion-media');
            gsap.fromTo(image, { scale: 1.045, yPercent: -1.4 }, {
                scale: 1,
                yPercent: 1.4,
                ease: 'none',
                scrollTrigger: { trigger: frame, start: 'top bottom', end: 'bottom top', scrub: 0.7 },
            });
        });

        gsap.utils.toArray('.en-button--flame').forEach(function (button) {
            const moveX = gsap.quickTo(button, 'x', { duration: 0.32, ease: 'power3.out' });
            const moveY = gsap.quickTo(button, 'y', { duration: 0.32, ease: 'power3.out' });
            button.addEventListener('pointermove', function (event) {
                const box = button.getBoundingClientRect();
                moveX((event.clientX - box.left - box.width / 2) * 0.13);
                moveY((event.clientY - box.top - box.height / 2) * 0.16);
            });
            button.addEventListener('pointerleave', function () {
                moveX(0);
                moveY(0);
            });
        });
    });

    window.addEventListener('load', function () {
        ScrollTrigger.refresh();
    }, { once: true });
}());
