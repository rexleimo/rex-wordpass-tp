(() => {
  const initHeroCarousel = () => {
    const carousel = document.querySelector('[data-hero-carousel]');
    if (!carousel) return;

    const slides = Array.from(carousel.querySelectorAll('[data-hero-carousel-slide]'));
    const dots = Array.from(carousel.querySelectorAll('[data-hero-carousel-dot]'));
    const previous = carousel.querySelector('[data-hero-carousel-previous]');
    const next = carousel.querySelector('[data-hero-carousel-next]');
    const current = Array.from(document.querySelectorAll('[data-hero-carousel-current]'));
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeIndex = 0;
    let timer;

    const activate = (index) => {
      activeIndex = (index + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => {
        const isActive = slideIndex === activeIndex;
        slide.classList.toggle('is-active', isActive);
        slide.setAttribute('aria-hidden', String(!isActive));
      });
      dots.forEach((dot, dotIndex) => {
        const isActive = dotIndex === activeIndex;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-current', String(isActive));
      });
      current.forEach((counter) => {
        counter.textContent = activeIndex + 1;
      });
    };

    const stop = () => {
      if (timer) window.clearInterval(timer);
      timer = undefined;
    };

    const start = () => {
      if (reducedMotion || document.hidden) return;
      stop();
      timer = window.setInterval(() => activate(activeIndex + 1), 6000);
    };

    previous?.addEventListener('click', () => {
      activate(activeIndex - 1);
      start();
    });
    next?.addEventListener('click', () => {
      activate(activeIndex + 1);
      start();
    });
    dots.forEach((dot, index) => dot.addEventListener('click', () => {
      activate(index);
      start();
    }));
    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', () => window.setTimeout(start, 0));
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    start();
  };

  initHeroCarousel();

  const form = document.querySelector('.quote-form');

  if (window.jQuery) {
    window.jQuery('.variations_form')
      .on('found_variation', function (event, variation) {
        const dimensions = window.jQuery(this).closest('.tokraft-product-summary').find('.tokraft-product-dimensions');
        const value = dimensions.find('strong');
        const next = window.jQuery('<div>').html(variation.dimensions_html || '').text().trim();
        if (value.length && next) value.text(next);
      })
      .on('reset_data hide_variation', function () {
        const dimensions = window.jQuery(this).closest('.tokraft-product-summary').find('.tokraft-product-dimensions');
        const value = dimensions.find('strong');
        if (value.length) value.text(dimensions.data('default-dimensions'));
      });
  }

  if (!form) return;

  const $ = (selector) => document.querySelector(selector);
  const fileInput = $('#model-file');
  const fileStatus = $('#file-status');
  const material = $('#material');
  const quantity = $('#quantity');
  const ranges = ['infill', 'walls', 'layer-height'].map((id) => $('#' + id));

  function rangeProgress(input) {
    const progress = ((input.value - input.min) / (input.max - input.min)) * 100;
    input.style.setProperty('--progress', `${progress}%`);
  }

  function updateImpacts() {
    const infill = Number($('#infill').value);
    const walls = Number($('#walls').value);
    const layer = Number($('#layer-height').value);
    $('#infill-impact').textContent = infill <= 20 ? 'Balanced strength, efficient material use and standard lead time.' : infill <= 50 ? 'Stronger internal structure with more material and print time.' : 'High-density part: maximum strength, material use and production time.';
    $('#walls-impact').textContent = walls <= 3 ? 'A dependable balance for functional prototypes.' : walls <= 4 ? 'More durable surfaces and stronger mounting features.' : 'Heavy-duty shells for demanding, load-bearing use.';
    $('#layer-impact').textContent = layer <= .16 ? 'Fine surface detail and smoother curves; expect a longer print time.' : layer <= .24 ? 'Standard detail with a balanced production time.' : 'Faster production with a more visible layer texture.';
  }

  function updateSummary() {
    const infill = Number($('#infill').value);
    const walls = Number($('#walls').value);
    const layer = Number($('#layer-height').value);
    const qty = Math.max(1, Number(quantity.value) || 1);
    const color = document.querySelector('input[name="color"]:checked')?.value || 'Natural';
    const factor = 1 + ((infill - 20) * .006) + ((walls - 3) * .1) + ((.2 - layer) * 1.1);
    const base = Number(material.selectedOptions[0]?.dataset.estimate) || 24;
    const low = Math.max(10, Math.round(base * qty * factor));
    const high = Math.round(low * 1.4);
    $('#summary-material').textContent = material.value;
    $('#summary-color').textContent = color;
    $('#summary-quantity').textContent = `${qty} ${qty === 1 ? 'part' : 'parts'}`;
    $('#summary-infill').textContent = `${infill}%`;
    $('#summary-walls').textContent = walls;
    $('#summary-layer').textContent = `${layer.toFixed(2)} mm`;
    $('#estimate-price').textContent = `$${low}–$${high}`;
    updateImpacts();
  }

  ranges.forEach((input) => {
    rangeProgress(input);
    input.addEventListener('input', () => {
      const output = $('#' + input.dataset.output);
      const value = Number(input.value);
      output.textContent = input.id === 'layer-height' ? `${value.toFixed(2)}${input.dataset.suffix}` : `${value}${input.dataset.suffix}`;
      rangeProgress(input);
      updateSummary();
    });
  });

  [material, quantity, ...document.querySelectorAll('input[name="color"]')].forEach((input) => input.addEventListener('change', updateSummary));
  quantity.addEventListener('input', updateSummary);
  fileInput.addEventListener('change', () => {
    fileStatus.textContent = fileInput.files[0] ? `Selected: ${fileInput.files[0].name}` : '';
  });

  const dialog = $('#help-modal');
  document.querySelectorAll('[data-help]').forEach((button) => button.addEventListener('click', () => {
    $('#help-copy').textContent = button.dataset.help;
    dialog.showModal();
  }));
  dialog.querySelectorAll('button').forEach((button) => button.addEventListener('click', () => dialog.close()));
  updateSummary();
})();
