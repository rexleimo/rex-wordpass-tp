(function () {
  'use strict';

  const home = document.querySelector('.home-hero');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!home || reduceMotion || !window.gsap || !window.ScrollTrigger) return;

  const gsap = window.gsap;
  const ScrollTrigger = window.ScrollTrigger;
  gsap.registerPlugin(ScrollTrigger);
  document.documentElement.classList.add('tk-home-motion-ready');

  const progress = document.createElement('div');
  const progressFill = document.createElement('span');
  progress.className = 'tk-home-progress';
  progress.setAttribute('aria-hidden', 'true');
  progress.appendChild(progressFill);
  document.body.appendChild(progress);
  ScrollTrigger.create({
    start: 0,
    end: 'max',
    onUpdate: (self) => gsap.set(progressFill, { scaleX: self.progress }),
  });

  const heroTimeline = gsap.timeline({ defaults: { ease: 'power3.out' } });
  heroTimeline
    .from('.home-hero-copy .home-section-tag', { x: -28, autoAlpha: 0, duration: 0.55 })
    .from('.home-hero-copy h1', { y: 54, autoAlpha: 0, duration: 0.86 }, '-=0.28')
    .from('.home-hero-copy > p, .home-hero-actions, .home-hero-note', {
      y: 28,
      autoAlpha: 0,
      duration: 0.68,
      stagger: 0.1,
    }, '-=0.5')
    .from('.home-hero-stage', {
      clipPath: 'inset(0 0 0 100%)',
      scale: 0.985,
      duration: 1.05,
      clearProps: 'clipPath,transform',
    }, 0.12)
    .from('.home-proof-item', {
      y: 24,
      autoAlpha: 0,
      duration: 0.58,
      stagger: 0.07,
    }, '-=0.34');

  gsap.utils.toArray('.home-section-heading, .home-materials-intro').forEach((heading) => {
    gsap.from(Array.from(heading.children), {
      y: 42,
      autoAlpha: 0,
      duration: 0.78,
      stagger: 0.1,
      ease: 'power3.out',
      clearProps: 'transform,opacity,visibility',
      scrollTrigger: { trigger: heading, start: 'top 84%', once: true },
    });
  });

  [
    '.home-route-grid',
    '.home-config-grid',
    '.home-material-rail',
    '.home-product-grid',
    '.home-case-grid',
    '.home-final-facts',
  ].forEach((selector) => {
    const group = document.querySelector(selector);
    if (!group || !group.children.length) return;
    gsap.from(Array.from(group.children), {
      y: 46,
      autoAlpha: 0,
      duration: 0.72,
      stagger: 0.085,
      ease: 'power3.out',
      clearProps: 'transform,opacity,visibility',
      scrollTrigger: { trigger: group, start: 'top 86%', once: true },
    });
  });

  const finalCta = document.querySelector('.home-final-copy');
  if (finalCta) {
    gsap.from(Array.from(finalCta.children), {
      x: -34,
      autoAlpha: 0,
      duration: 0.72,
      stagger: 0.09,
      ease: 'power3.out',
      clearProps: 'transform,opacity,visibility',
      scrollTrigger: { trigger: finalCta, start: 'top 82%', once: true },
    });
  }

  document.querySelectorAll('.home-final-facts > div > span').forEach((value) => {
    const original = value.textContent.trim();
    const match = original.match(/-?\d+(?:[.,]\d+)?/);
    if (!match) return;
    const normalized = match[0].replace(',', '.');
    const decimals = normalized.includes('.') ? normalized.split('.')[1].length : 0;
    const counter = { value: 0 };
    ScrollTrigger.create({
      trigger: value,
      start: 'top 88%',
      once: true,
      onEnter: () => gsap.to(counter, {
        value: Number(normalized),
        duration: 1.25,
        ease: 'power2.out',
        onUpdate: () => {
          const current = counter.value.toFixed(decimals);
          value.textContent = original.replace(match[0], current);
        },
      }),
    });
  });

  const motion = gsap.matchMedia();
  motion.add('(min-width: 901px) and (hover: hover)', () => {
    document.querySelectorAll([
      '.home-hero-stage img',
      '.home-route-media img',
      '.home-config-image img',
      '.home-material-image img',
      '.home-product-image img',
      '.home-case-image img',
    ].join(',')).forEach((image) => {
      image.classList.add('tk-motion-media');
      gsap.fromTo(image, { '--tk-motion-y': '-1.5%' }, {
        '--tk-motion-y': '1.5%',
        ease: 'none',
        scrollTrigger: {
          trigger: image.parentElement,
          start: 'top bottom',
          end: 'bottom top',
          scrub: 0.7,
        },
      });
    });

    const stage = document.querySelector('.home-hero-stage');
    const grid = stage?.querySelector('.home-stage-grid');
    if (stage && grid) {
      const moveX = gsap.quickTo(grid, 'x', { duration: 0.6, ease: 'power3.out' });
      const moveY = gsap.quickTo(grid, 'y', { duration: 0.6, ease: 'power3.out' });
      stage.addEventListener('pointermove', (event) => {
        const box = stage.getBoundingClientRect();
        moveX(((event.clientX - box.left) / box.width - 0.5) * 18);
        moveY(((event.clientY - box.top) / box.height - 0.5) * 18);
      });
      stage.addEventListener('pointerleave', () => {
        moveX(0);
        moveY(0);
      });
    }

    document.querySelectorAll('.home-button').forEach((button) => {
      const moveX = gsap.quickTo(button, 'x', { duration: 0.32, ease: 'power3.out' });
      const moveY = gsap.quickTo(button, 'y', { duration: 0.32, ease: 'power3.out' });
      button.addEventListener('pointermove', (event) => {
        const box = button.getBoundingClientRect();
        moveX((event.clientX - box.left - box.width / 2) * 0.1);
        moveY((event.clientY - box.top - box.height / 2) * 0.14);
      });
      button.addEventListener('pointerleave', () => {
        gsap.to(button, {
          x: 0,
          y: 0,
          duration: 0.32,
          ease: 'power3.out',
          overwrite: true,
          clearProps: 'transform',
        });
      });
    });
  });

  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}());
