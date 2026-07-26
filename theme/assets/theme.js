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

  const initHomeProofRail = () => {
    const section = document.querySelector('[data-home-proof]');
    const rail = section?.querySelector('[data-home-proof-rail]');
    const previous = section?.querySelector('[data-home-proof-previous]');
    const next = section?.querySelector('[data-home-proof-next]');
    if (!section || !rail || !previous || !next) return;

    const updateControls = () => {
      const overflow = rail.scrollWidth - rail.clientWidth > 2;
      previous.disabled = !overflow || rail.scrollLeft <= 4;
      next.disabled = !overflow || rail.scrollLeft >= rail.scrollWidth - rail.clientWidth - 4;
    };
    const move = (direction) => rail.scrollBy({ left: direction * Math.max(rail.clientWidth * .8, 240), behavior: 'smooth' });
    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    rail.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', updateControls, { passive: true });
    if (window.ResizeObserver) new ResizeObserver(updateControls).observe(rail);
    updateControls();
  };

  const initMaterialSwiper = () => {
    const slider = document.querySelector('[data-home-material-swiper]');
    const tabs = Array.from(document.querySelectorAll('[data-home-material-target]'));
    const scrollbar = slider?.querySelector('[data-home-material-scrollbar]');
    if (!slider || !scrollbar || !tabs.length || typeof window.Swiper !== 'function') return;

    const selectTab = (tab) => {
      tabs.forEach((item) => item.setAttribute('aria-selected', String(item === tab)));
    };
    const slideOffset = () => {
      if (slider.clientWidth <= 620) return 18;
      if (slider.clientWidth <= 820) return 40;
      return Math.max(32, Math.floor((slider.clientWidth - 1376) / 2));
    };
    const swiper = new window.Swiper(slider, {
      slidesPerView: 'auto',
      spaceBetween: 18,
      slidesOffsetBefore: slideOffset(),
      slidesOffsetAfter: slideOffset(),
      grabCursor: true,
      speed: 500,
      threshold: 6,
      watchOverflow: true,
      breakpoints: {
        0: { spaceBetween: 14 },
        621: { spaceBetween: 18 },
      },
      scrollbar: {
        el: scrollbar,
        draggable: true,
        dragSize: 'auto',
        snapOnRelease: true,
      },
    });
    const updateOffsets = () => {
      const offset = slideOffset();
      swiper.params.slidesOffsetBefore = offset;
      swiper.params.slidesOffsetAfter = offset;
      swiper.update();
    };
    const moveToTab = (tab, index) => {
      selectTab(tab);
      swiper.slideTo(index);
    };
    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => moveToTab(tab, index));
      tab.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        tabs[nextIndex].focus();
        moveToTab(tabs[nextIndex], nextIndex);
      });
    });
    swiper.on('slideChange', () => selectTab(tabs[swiper.activeIndex]));
    window.addEventListener('resize', updateOffsets, { passive: true });
    selectTab(tabs[swiper.activeIndex]);
  };

  const initMaterialLibrary = () => {
    const slider = document.querySelector('[data-material-library-swiper]');
    const previous = document.querySelector('[data-material-library-previous]');
    const next = document.querySelector('[data-material-library-next]');
    const scrollbar = document.querySelector('[data-material-library-scrollbar]');
    const filters = Array.from(document.querySelectorAll('[data-material-filter]'));
    const wrapper = slider?.querySelector('.swiper-wrapper');
    if (!slider || !wrapper || !previous || !next || !scrollbar || !filters.length || typeof window.Swiper !== 'function') return;

    const slides = Array.from(wrapper.children);
    let activeType = 'all';
    let swiper;

    const buildSlider = () => {
      if (swiper) swiper.destroy(true, false);
      wrapper.replaceChildren(...slides.filter((slide) => activeType === 'all' || slide.dataset.materialType === activeType));
      swiper = new window.Swiper(slider, {
        slidesPerView: 3,
        slidesPerGroup: 1,
        spaceBetween: 18,
        grabCursor: true,
        speed: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 560,
        keyboard: { enabled: true, onlyInViewport: true },
        watchOverflow: true,
        navigation: { prevEl: previous, nextEl: next },
        scrollbar: { el: scrollbar, draggable: true, dragSize: 'auto', snapOnRelease: true },
        breakpoints: {
          0: { slidesPerView: 1, spaceBetween: 0 },
          701: { slidesPerView: 2, spaceBetween: 16 },
          1101: { slidesPerView: 3, spaceBetween: 18 },
        },
      });
    };

    const selectFilter = (filter) => {
      activeType = filter.dataset.materialFilter;
      filters.forEach((item) => {
        const selected = item === filter;
        item.classList.toggle('is-active', selected);
        item.setAttribute('aria-selected', String(selected));
      });
      buildSlider();
    };

    filters.forEach((filter, index) => {
      filter.addEventListener('click', () => selectFilter(filter));
      filter.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? filters.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + filters.length) % filters.length;
        filters[nextIndex].focus();
        selectFilter(filters[nextIndex]);
      });
    });
    buildSlider();
  };

  initHomeProofRail();
  initMaterialSwiper();
  initMaterialLibrary();

  if (window.jQuery) {
    window.jQuery('.variations_form')
      .on('found_variation', function (event, variation) {
        const dimensions = window.jQuery(this).closest('.tokraft-product-summary, .tk-product-summary').find('.tokraft-product-dimensions');
        const value = dimensions.find('strong');
        const next = window.jQuery('<div>').html(variation.dimensions_html || '').text().trim();
        if (value.length && next) value.text(next);
      })
      .on('reset_data hide_variation', function () {
        const dimensions = window.jQuery(this).closest('.tokraft-product-summary, .tk-product-summary').find('.tokraft-product-dimensions');
        const value = dimensions.find('strong');
        if (value.length) value.text(dimensions.data('default-dimensions'));
      });
  }

  const form = document.querySelector('.quote-form');
  if (form) {
    const $ = (selector) => document.querySelector(selector);
    const fileInput = $('#model-file');
    const fileStatus = $('#file-status');
    const material = $('#material');
    const quantity = $('#quantity');
    const ranges = ['infill', 'walls', 'layer-height'].map((id) => $('#' + id)).filter(Boolean);

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

    [material, quantity, ...document.querySelectorAll('input[name="color"]')].forEach((input) => input?.addEventListener('change', updateSummary));
    quantity?.addEventListener('input', updateSummary);
    fileInput?.addEventListener('change', () => {
      fileStatus.textContent = fileInput.files[0] ? `Selected: ${fileInput.files[0].name}` : '';
    });

    const dialog = $('#help-modal');
    if (dialog) {
      document.querySelectorAll('[data-help]').forEach((button) => button.addEventListener('click', () => {
        $('#help-copy').textContent = button.dataset.help;
        dialog.showModal();
      }));
      dialog.querySelectorAll('button').forEach((button) => button.addEventListener('click', () => dialog.close()));
    }
    updateSummary();
  }

  const tabRoot = document.querySelector('.tk-product-tabs');
  if (tabRoot) {
    const tabs = Array.from(tabRoot.querySelectorAll('[data-tk-tab]'));
    const tabSection = tabRoot.closest('.tk-product-spec-section');
    const panels = Array.from(tabSection?.querySelectorAll('[data-tk-panel]') || []);

    const activateTab = (nextTab, moveFocus = false) => {
      const id = nextTab.dataset.tkTab;
      tabs.forEach((tab) => {
        const isActive = tab === nextTab;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.tabIndex = isActive ? 0 : -1;
      });
      panels.forEach((panel) => {
        const isActive = panel.dataset.tkPanel === id;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
      });
      if (moveFocus) nextTab.focus();
    };

    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activateTab(tab));
      tab.addEventListener('keydown', (event) => {
        let nextIndex = null;
        if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
        if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
        if (event.key === 'Home') nextIndex = 0;
        if (event.key === 'End') nextIndex = tabs.length - 1;
        if (nextIndex === null) return;
        event.preventDefault();
        activateTab(tabs[nextIndex], true);
      });
    });

    activateTab(tabs.find((tab) => tab.classList.contains('is-active')) || tabs[0]);
  }

  // Filament shop grid density
  const gridShell = document.querySelector('[data-tk-grid-cols]');
  if (gridShell) {
    document.querySelectorAll('[data-tk-grid]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const cols = btn.dataset.tkGrid;
        gridShell.setAttribute('data-tk-grid-cols', cols);
        document.querySelectorAll('[data-tk-grid]').forEach((b) => b.classList.toggle('is-active', b === btn));
      });
    });
  }

  // Colour swatches: active state + optional image swap
  document.querySelectorAll('.tk-fcard').forEach((card) => {
    const swatches = Array.from(card.querySelectorAll('.tk-swatch'));
    const primary = card.querySelector('.tk-fcard-img.is-primary');
    swatches.forEach((swatch) => {
      swatch.addEventListener('click', (event) => {
        event.preventDefault();
        swatches.forEach((s) => {
          s.classList.toggle('is-active', s === swatch);
          s.setAttribute('aria-checked', s === swatch ? 'true' : 'false');
        });
        if (primary && swatch.dataset.image) {
          primary.src = swatch.dataset.image;
        } else if (primary && swatch.dataset.color) {
          primary.style.boxShadow = `inset 0 0 0 999px ${swatch.dataset.color}22`;
        }
      });
    });
  });

  // Order received: copy reference
  document.querySelectorAll('[data-copy-order]').forEach((button) => {
    button.addEventListener('click', async () => {
      const value = button.dataset.copyOrder || '';
      try {
        await navigator.clipboard.writeText(value);
        const original = button.textContent;
        button.textContent = 'Copied: ' + value;
        window.setTimeout(() => { button.textContent = original; }, 1600);
      } catch (err) {
        window.prompt('Copy order reference', value);
      }
    });
  });

  // Variable product options → Bambu-like chips/swatches
  const detail = document.querySelector('.tk-product-detail.bambu-like');
  const variationsForm = document.querySelector('.variations_form');
  if (detail && variationsForm) {
    const currentPrice = detail.querySelector('.tk-product-current-price');
    const priceNote = detail.querySelector('.tk-price-note');
    const defaultPrice = currentPrice?.innerHTML || '';
    const defaultPriceNote = priceNote?.textContent || '';

    if (currentPrice && window.jQuery) {
      detail.classList.add('is-price-synced');
      window.jQuery(variationsForm)
        .on('found_variation show_variation', (event, variation) => {
          const parsedPrice = window.jQuery('<div>').html(variation.price_html || '').find('.price').html();
          if (parsedPrice) currentPrice.innerHTML = parsedPrice;
          if (priceNote) {
            priceNote.textContent = variation.is_in_stock
              ? '1 kg / selected configuration / in stock'
              : 'Selected configuration / unavailable';
          }
        })
        .on('reset_data hide_variation', () => {
          currentPrice.innerHTML = defaultPrice;
          if (priceNote) priceNote.textContent = defaultPriceNote;
        });
    }

    let colorMap = {};
    try {
      colorMap = JSON.parse(detail.dataset.colorMap || '{}') || {};
    } catch (e) {
      colorMap = {};
    }

    const enhanceSelect = (select) => {
      if (!select || select.dataset.tkEnhanced === '1') return;
      select.dataset.tkEnhanced = '1';
      select.classList.add('tk-native-select');
      select.style.position = 'absolute';
      select.style.opacity = '0';
      select.style.pointerEvents = 'none';
      select.style.width = '1px';
      select.style.height = '1px';
      select.tabIndex = -1;
      select.setAttribute('aria-hidden', 'true');

      const labelText = (select.closest('tr')?.querySelector('label')?.textContent || select.name || '').toLowerCase();
      const isColor = labelText.includes('color') || select.id.toLowerCase().includes('color') || select.name.toLowerCase().includes('color');
      const wrap = document.createElement('div');
      wrap.className = isColor ? 'tk-color-swatches' : 'tk-option-chips';
      wrap.setAttribute('role', 'group');
      wrap.setAttribute('aria-label', labelText || 'Product option');
      select.parentElement.appendChild(wrap);

      let selectedLabel = null;
      if (isColor) {
        selectedLabel = document.createElement('div');
        selectedLabel.className = 'tk-selected-color-label';
        selectedLabel.setAttribute('aria-live', 'polite');
        select.parentElement.appendChild(selectedLabel);
      }

      const rebuild = () => {
        const available = Array.from(select.options).filter((opt) => opt.value && !opt.disabled);
        if (!select.value && available.length === 1) {
          select.value = available[0].value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          return;
        }

        wrap.innerHTML = '';
        Array.from(select.options).forEach((opt) => {
          if (!opt.value) return;
          const btn = document.createElement('button');
          btn.type = 'button';
          const active = select.value === opt.value;
          btn.disabled = opt.disabled;
          btn.setAttribute('aria-disabled', opt.disabled ? 'true' : 'false');
          btn.setAttribute('aria-pressed', active ? 'true' : 'false');
          if (isColor) {
            const hex = colorMap[opt.text.trim()] || colorMap[opt.value] || '#9E9E9E';
            btn.className = 'tk-color-swatch' + (active ? ' is-active' : '') + (opt.disabled ? ' is-disabled' : '');
            btn.style.setProperty('--swatch', hex);
            btn.textContent = opt.text.trim();
            btn.setAttribute('aria-label', opt.text.trim());
            btn.title = opt.text.trim();
          } else {
            btn.className = 'tk-option-chip' + (active ? ' is-active' : '') + (opt.disabled ? ' is-disabled' : '');
            btn.textContent = opt.text.trim();
          }
          btn.addEventListener('click', () => {
            if (opt.disabled || select.value === opt.value) return;
            select.value = opt.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
          });
          wrap.appendChild(btn);
        });
        if (selectedLabel) {
          const current = select.options[select.selectedIndex];
          selectedLabel.textContent = current && current.value ? ('Color : ' + current.text.trim()) : 'Color : select';
        }
      };

      select.addEventListener('change', rebuild);
      rebuild();
      // Woo toggles option disabled states after variation checks
      const observer = new MutationObserver(rebuild);
      observer.observe(select, { childList: true, subtree: true, attributes: true });
    };

    variationsForm.querySelectorAll('select').forEach(enhanceSelect);
    if (window.jQuery) {
      window.jQuery(variationsForm).on('woocommerce_update_variation_values show_variation hide_variation reset_data', () => {
        variationsForm.querySelectorAll('select').forEach(enhanceSelect);
      });
    }

    // Variation images are already supplied by WooCommerce on the form. Build a
    // separate, keyboard-accessible rail so that product options and photos stay in sync.
    const galleryFrame = detail.querySelector('.tk-product-gallery-frame');
    const galleryImage = galleryFrame?.querySelector('.woocommerce-product-gallery__image img');
    let variations = [];
    try {
      variations = JSON.parse(variationsForm.dataset.product_variations || '[]');
    } catch (e) {
      variations = [];
    }

    if (galleryFrame && galleryImage && variations.length) {
      const selects = Array.from(variationsForm.querySelectorAll('select[name]'));
      const colorSelect = selects.find((select) => /color/i.test(select.name + ' ' + select.id + ' ' + (select.closest('tr')?.textContent || '')));
      const rail = document.createElement('div');
      rail.className = 'tk-variation-gallery';
      rail.setAttribute('aria-label', colorSelect ? 'Choose colour photo' : 'Choose product photo');
      rail.setAttribute('role', 'list');
      const railShell = document.createElement('div');
      railShell.className = 'tk-variation-gallery-shell';
      const previous = document.createElement('button');
      previous.type = 'button';
      previous.className = 'tk-variation-gallery-nav tk-variation-gallery-nav-prev';
      previous.setAttribute('aria-label', 'Show previous product photos');
      previous.textContent = '‹';
      const next = document.createElement('button');
      next.type = 'button';
      next.className = 'tk-variation-gallery-nav tk-variation-gallery-nav-next';
      next.setAttribute('aria-label', 'Show next product photos');
      next.textContent = '›';

      const primaryAttribute = colorSelect?.name;
      const entries = [];
      const seen = new Set();
      const seenImages = new Set();
      variations.forEach((variation) => {
        const key = primaryAttribute ? variation.attributes?.[primaryAttribute] : variation.variation_id;
        const imageSrc = variation.image?.full_src || variation.image?.src;
        if (!key || seen.has(key) || !imageSrc || seenImages.has(imageSrc)) return;
        seen.add(key);
        seenImages.add(imageSrc);
        entries.push({ key, variation });
      });

      const setGalleryImage = (image) => {
        if (!image?.src) return;
        galleryImage.src = image.src;
        galleryImage.srcset = image.srcset || '';
        galleryImage.sizes = image.sizes || '';
        galleryImage.alt = image.alt || image.title || '';
        galleryImage.closest('a')?.setAttribute('href', image.full_src || image.url || image.src);
      };

      const updateRail = (attributes = {}) => {
        rail.querySelectorAll('button').forEach((button) => {
          const active = primaryAttribute ? button.dataset.optionValue === attributes[primaryAttribute] : button.dataset.variationId === String(attributes.variation_id || '');
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
      };

      entries.forEach(({ key, variation }) => {
        const button = document.createElement('button');
        const image = variation.image;
        const label = primaryAttribute ? key : (image.title || 'Product photo');
        button.type = 'button';
        button.className = 'tk-variation-gallery-item';
        button.dataset.optionValue = key;
        button.dataset.variationId = variation.variation_id;
        button.setAttribute('role', 'listitem');
        button.setAttribute('aria-label', label);
        button.setAttribute('aria-pressed', 'false');
        button.title = label;
        const thumbnail = document.createElement('img');
        thumbnail.src = image.gallery_thumbnail_src || image.thumb_src || image.src;
        thumbnail.alt = '';
        thumbnail.loading = 'lazy';
        const caption = document.createElement('span');
        caption.textContent = label;
        button.append(thumbnail, caption);
        button.addEventListener('click', () => {
          const selected = Object.fromEntries(selects.map((select) => [select.name, select.value]));
          const matched = variations.find((candidate) => candidate.attributes?.[primaryAttribute] === key && selects.every((select) => !selected[select.name] || candidate.attributes?.[select.name] === selected[select.name])) || variation;
          selects.forEach((select) => {
            const next = matched.attributes?.[select.name];
            if (next && select.value !== next) select.value = next;
          });
          selects[0]?.dispatchEvent(new Event('change', { bubbles: true }));
          setGalleryImage(matched.image);
          updateRail(matched.attributes || {});
        });
        rail.appendChild(button);
      });

      if (entries.length > 1) {
        railShell.append(previous, rail, next);
        galleryFrame.insertAdjacentElement('afterend', railShell);
        const updateRailControls = () => {
          const maxScroll = rail.scrollWidth - rail.clientWidth;
          const hasOverflow = maxScroll > 1;
          railShell.classList.toggle('has-overflow', hasOverflow);
          previous.disabled = !hasOverflow || rail.scrollLeft <= 4;
          next.disabled = !hasOverflow || rail.scrollLeft >= maxScroll - 4;
        };
        const moveRail = (direction) => {
          rail.scrollBy({ left: direction * Math.max(rail.clientWidth * .8, 220), behavior: 'smooth' });
        };
        previous.addEventListener('click', () => moveRail(-1));
        next.addEventListener('click', () => moveRail(1));
        rail.addEventListener('scroll', updateRailControls, { passive: true });
        new ResizeObserver(updateRailControls).observe(rail);
        window.requestAnimationFrame(updateRailControls);
        const initial = variations.find((variation) => variation.attributes?.[primaryAttribute] === colorSelect?.value) || entries[0].variation;
        updateRail(initial.attributes || {});
        const syncRailFromColor = () => {
          if (!colorSelect) {
            updateRail({});
            return;
          }
          const selected = variations.find((variation) => variation.attributes?.[primaryAttribute] === colorSelect.value);
          if (!selected) {
            updateRail({});
            return;
          }
          setGalleryImage(selected.image);
          updateRail(selected.attributes || {});
        };
        colorSelect?.addEventListener('change', syncRailFromColor);
        if (window.jQuery) {
          window.jQuery(variationsForm)
            .on('found_variation show_variation', (event, variation) => {
              setGalleryImage(variation.image);
              updateRail(variation.attributes || {});
            })
            .on('reset_data hide_variation', () => {
              syncRailFromColor();
            });
        }
      }
    }
  }

  // Discover-more cards keep a normal WooCommerce form as the fallback, then
  // progressively enhance it with the same fragments event used by shop cards.
  const discoverMore = document.querySelector('[data-tk-discover-more]');
  if (discoverMore) {
    const normaliseQuantity = (input) => {
      const minimum = Math.max(1, Number(input.min) || 1);
      const value = Math.max(minimum, Number.parseInt(input.value, 10) || minimum);
      input.value = String(value);
      return value;
    };

    discoverMore.addEventListener('click', (event) => {
      const quantityButton = event.target.closest('[data-tk-discover-quantity]');
      if (!quantityButton) return;
      const form = quantityButton.closest('[data-tk-discover-form]');
      const input = form?.querySelector('.tk-discover-quantity-input');
      if (!input) return;
      event.preventDefault();
      const step = quantityButton.dataset.tkDiscoverQuantity === 'increase' ? 1 : -1;
      input.value = String(Math.max(Number(input.min) || 1, normaliseQuantity(input) + step));
    });

    discoverMore.addEventListener('change', (event) => {
      if (event.target.matches('.tk-discover-quantity-input')) normaliseQuantity(event.target);
    });

    discoverMore.addEventListener('submit', (event) => {
      const form = event.target.closest('[data-tk-discover-form]');
      const endpoint = window.wc_add_to_cart_params?.wc_ajax_url;
      const $ = window.jQuery;
      if (!form || !endpoint || !$) return;

      event.preventDefault();
      const button = form.querySelector('.tk-discover-add-button');
      const input = form.querySelector('.tk-discover-quantity-input');
      const quantity = input ? normaliseQuantity(input) : 1;
      const productId = Number(form.dataset.productId || 0);
      if (!button || !productId || button.disabled) return;

      const originalLabel = button.textContent;
      button.disabled = true;
      button.textContent = 'Adding...';
      const ajaxUrl = endpoint.toString().replace('%%endpoint%%', 'add_to_cart');

      $.post(ajaxUrl, { product_id: productId, quantity })
        .done((response) => {
          if (response?.error && response?.product_url) {
            window.location.assign(response.product_url);
            return;
          }
          $(document.body).trigger('added_to_cart', [response?.fragments, response?.cart_hash, $(button)]);
          button.classList.add('is-added');
          button.textContent = 'Added';
          window.setTimeout(() => {
            button.classList.remove('is-added');
            button.disabled = false;
            button.textContent = originalLabel;
          }, 1800);
        })
        .fail(() => {
          // If the asynchronous endpoint is unavailable, retain WooCommerce's form path.
          form.submit();
        });
    });
  }
})();
