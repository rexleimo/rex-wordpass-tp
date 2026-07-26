/* global wp */
((($) => {
  const i18n = window.tokraftAdminI18n || {};
  const t = (key, ...values) => values.reduce(
    (text, value) => text.replace('%s', String(value)),
    i18n[key] || key,
  );

  const setPreview = (target, attachment, mediaType = 'image') => {
    const input = document.getElementById(target);
    const preview = document.getElementById(`${target}-preview`);
    if (!input || !preview) return;
    input.value = attachment ? attachment.id : '';
    if (preview.tagName === 'IMG') {
      preview.src = attachment ? (attachment.sizes?.medium?.url || attachment.url) : '';
      preview.style.display = attachment ? 'block' : 'none';
    } else if (mediaType === 'video') {
      if (attachment) {
        const label = attachment.filename || attachment.url.split('/').pop() || 'Video selected';
        preview.innerHTML = `<a href="${attachment.url}" target="_blank" rel="noopener">${label}</a>`;
        preview.style.display = 'block';
      } else {
        preview.innerHTML = '';
        preview.style.display = 'none';
      }
    }
    preview.dispatchEvent(new CustomEvent('tokraftimagechange', { bubbles: true }));
  };

  const openImagePicker = (target, mediaType = 'image') => {
    const isVideo = mediaType === 'video';
    const frame = wp.media({
      title: isVideo ? (t('select_video') || 'Select video') : t('select_image'),
      button: { text: isVideo ? (t('use_this_video') || 'Use this video') : t('use_this_image') },
      library: { type: isVideo ? 'video' : 'image' },
      multiple: false,
    });
    frame.on('select', () => setPreview(target, frame.state().get('selection').first().toJSON(), mediaType));
    frame.open();
  };

  const setGalleryPreview = (target, attachments = []) => {
    const input = document.getElementById(target);
    const preview = document.getElementById(`${target}-preview`);
    if (!input || !preview) return;

    input.value = attachments.map((attachment) => attachment.id).join(',');
    preview.replaceChildren();
    attachments.forEach((attachment, index) => {
      const figure = document.createElement('figure');
      const image = document.createElement('img');
      const caption = document.createElement('figcaption');
      image.src = attachment.sizes?.medium?.url || attachment.url;
      image.alt = '';
      caption.textContent = t('image_number', index + 1);
      figure.append(image, caption);
      preview.append(figure);
    });
    preview.dispatchEvent(new CustomEvent('tokraftgallerychange', { bubbles: true }));
  };

  const galleryIds = (target) => (document.getElementById(target)?.value || '')
    .split(',')
    .map((id) => id.trim())
    .filter(Boolean);

  const openGalleryPicker = (target, maxItems = 5, replaceIndex = null) => {
    const input = document.getElementById(target);
    if (!input) return;
    const existingIds = galleryIds(target);
    const previewImages = Array.from(document.getElementById(`${target}-preview`)?.querySelectorAll('img') || []);
    const existingAttachments = existingIds.map((id, index) => ({ id: Number(id), url: previewImages[index]?.src || '' }));
    const isReplacing = Number.isInteger(replaceIndex);
    const frame = wp.media({
      title: isReplacing ? t('replace_carousel_image') : t('select_carousel_images'),
      button: { text: isReplacing ? t('replace_image') : t('use_these_images') },
      library: { type: 'image' },
      multiple: !isReplacing,
    });

    if (!isReplacing) {
      frame.on('open', () => {
        const selection = frame.state().get('selection');
        existingIds.forEach((id) => selection.add(wp.media.attachment(id)));
      });
    }

    frame.on('select', () => {
      const attachments = frame.state().get('selection').toJSON();
      if (isReplacing) {
        const replacement = attachments[0];
        if (!replacement) return;
        const nextAttachments = [...existingAttachments];
        nextAttachments[replaceIndex] = replacement;
        setGalleryPreview(target, nextAttachments);
        return;
      }
      if (attachments.length > maxItems) window.alert(t('carousel_image_limit', maxItems));
      setGalleryPreview(target, attachments.slice(0, maxItems));
    });
    frame.open();
  };

  $(document).on('click', '.tokraft-media-select', (event) => {
    event.preventDefault();
    openImagePicker(event.currentTarget.dataset.target, event.currentTarget.dataset.mediaType || 'image');
  });

  $(document).on('click', '.tokraft-media-clear', (event) => {
    event.preventDefault();
    const target = event.currentTarget.dataset.target;
    const selectButton = document.querySelector(`.tokraft-media-select[data-target="${target}"]`);
    setPreview(target, null, selectButton?.dataset.mediaType || 'image');
  });

  $(document).on('click', '.tokraft-media-gallery-select', (event) => {
    event.preventDefault();
    openGalleryPicker(event.currentTarget.dataset.target, Number(event.currentTarget.dataset.maxItems) || 5);
  });

  $(document).on('click', '.tokraft-media-gallery-clear', (event) => {
    event.preventDefault();
    setGalleryPreview(event.currentTarget.dataset.target, []);
  });

  const initHeroVisualOptions = () => {
    const mode = document.querySelector('[data-tokraft-hero-visual-mode]');
    if (!mode) return;
    const options = document.querySelectorAll('[data-tokraft-hero-visual-option]');
    const update = () => options.forEach((option) => {
      option.hidden = option.dataset.tokraftHeroVisualOption !== mode.value;
    });
    mode.addEventListener('change', update);
    update();
  };

  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[character]));

  const initHeroSlideEditor = (section, updateHeroCount) => {
    if (section.dataset.tokraftHeroSlideEditorReady) return;
    const slidesPanel = section.querySelector('[data-tokraft-drawer-panel="slides"]');
    const mode = section.querySelector('[data-tokraft-hero-visual-mode]');
    const carouselField = document.getElementById('tokraft_home_settings_hero_slides')?.closest('.tokraft-admin-field');
    const singleOption = section.querySelector('[data-tokraft-hero-visual-option="single"]');
    const carouselOption = section.querySelector('[data-tokraft-hero-visual-option="carousel"]');
    const modeField = mode?.closest('.tokraft-admin-field');
    if (!slidesPanel || !mode || !modeField || !carouselField || !singleOption || !carouselOption) return;

    const source = document.createElement('div');
    source.className = 'tokraft-hero-slide-editor__source';
    source.append(modeField, singleOption, carouselOption);

    const editor = document.createElement('div');
    editor.className = 'tokraft-hero-slide-editor';
    editor.innerHTML = `<div class="tokraft-hero-slide-editor__mode"><span class="dashicons dashicons-format-gallery"></span><div><strong></strong><small></small></div><button type="button" class="button-link" data-tokraft-toggle-hero-mode>${t('change')}</button></div><div class="tokraft-hero-slide-editor__toolbar"><span>${t('carousel_slides')}</span><button type="button" class="button" data-tokraft-add-hero-slide><span class="dashicons dashicons-plus-alt2"></span>${t('add_slide')}</button></div><div class="tokraft-hero-slide-editor__list"></div><section class="tokraft-hero-slide-editor__detail"></section>`;
    slidesPanel.append(editor, source);

    const galleryInput = document.getElementById('tokraft_home_settings_hero_slides');
    const galleryPreview = document.getElementById('tokraft_home_settings_hero_slides-preview');
    const singleInput = document.getElementById('tokraft_home_settings_hero_image');
    const singlePreview = document.getElementById('tokraft_home_settings_hero_image-preview');
    const modeTitle = editor.querySelector('.tokraft-hero-slide-editor__mode strong');
    const modeText = editor.querySelector('.tokraft-hero-slide-editor__mode small');
    const toolbar = editor.querySelector('.tokraft-hero-slide-editor__toolbar');
    const list = editor.querySelector('.tokraft-hero-slide-editor__list');
    const detail = editor.querySelector('.tokraft-hero-slide-editor__detail');
    let selectedIndex = 0;

    const slideData = () => {
      const ids = galleryIds('tokraft_home_settings_hero_slides');
      const images = Array.from(galleryPreview?.querySelectorAll('img') || []);
      return ids.map((id, index) => ({ id, src: images[index]?.src || '', index }));
    };

    const render = () => {
      const isCarousel = mode.value === 'carousel';
      const slides = slideData();
      selectedIndex = Math.min(selectedIndex, Math.max(0, slides.length - 1));
      modeTitle.textContent = isCarousel ? t('carousel_mode') : t('single_image_mode');
      modeText.textContent = isCarousel ? (
        slides.length === 0 ? t('carousel_no_slides') : (slides.length === 1 ? t('carousel_one_slide') : t('carousel_many_slides', slides.length))
      ) : t('single_image_homepage');
      toolbar.hidden = !isCarousel;
      list.hidden = !isCarousel;
      detail.hidden = !isCarousel;

      if (isCarousel) {
        list.innerHTML = slides.length
          ? slides.map((slide, index) => {
            const number = String(index + 1).padStart(2, '0');
            return `<article class="tokraft-hero-slide-editor__row${index === selectedIndex ? ' is-active' : ''}"><img src="${escapeHtml(slide.src)}" alt=""><span class="dashicons dashicons-menu"></span><div><small>${t('slide_number', number)}</small><strong>${t('homepage_slide')}</strong></div><em>${index === 0 ? t('active_first_slide') : t('slide_short_number', number)}</em><button type="button" class="button" data-tokraft-edit-hero-slide="${index}">${t('edit')}</button></article>`;
          }).join('')
          : `<div class="tokraft-hero-slide-editor__empty">${t('no_carousel_images')}</div>`;
        const selectedSlide = slides[selectedIndex];
        detail.innerHTML = selectedSlide
          ? `<span>${t('selected_slide', String(selectedIndex + 1).padStart(2, '0'), selectedIndex === 0 ? t('active_suffix') : '')}</span><h3>${t('homepage_slide')}</h3><div class="tokraft-hero-slide-editor__detail-grid"><div><img src="${escapeHtml(selectedSlide.src)}" alt=""><button type="button" class="button" data-tokraft-replace-hero-slide="${selectedIndex}">${t('replace')}</button></div><div><small>${t('image_description')}</small><p>${t('image_alt_text_help')}</p><small>${t('visible_on_homepage')}</small><p><i></i> ${t('saved_carousel_visibility')}</p></div></div>`
          : '';
      } else {
        list.replaceChildren();
        const imageUrl = singlePreview?.getAttribute('src') || '';
        detail.innerHTML = `<div class="tokraft-hero-slide-editor__single-help"><strong>${t('single_image_selected')}</strong><p>${t('single_image_help')}</p>${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="">` : ''}<button type="button" class="button" data-tokraft-select-hero-single>${imageUrl ? t('replace_image') : t('choose_image')}</button></div>`;
      }

      updateHeroCount(slides.length, isCarousel);
    };

    editor.addEventListener('click', (event) => {
      const modeToggle = event.target.closest('[data-tokraft-toggle-hero-mode]');
      const addSlide = event.target.closest('[data-tokraft-add-hero-slide]');
      const editSlide = event.target.closest('[data-tokraft-edit-hero-slide]');
      const replaceSlide = event.target.closest('[data-tokraft-replace-hero-slide]');
      const selectSingle = event.target.closest('[data-tokraft-select-hero-single]');
      if (modeToggle) {
        mode.value = mode.value === 'carousel' ? 'single' : 'carousel';
        mode.dispatchEvent(new Event('change', { bubbles: true }));
        render();
      }
      if (addSlide) openGalleryPicker(galleryInput.id, 5);
      if (editSlide) {
        selectedIndex = Number(editSlide.dataset.tokraftEditHeroSlide) || 0;
        render();
      }
      if (replaceSlide) openGalleryPicker(galleryInput.id, 5, Number(replaceSlide.dataset.tokraftReplaceHeroSlide));
      if (selectSingle && singleInput) openImagePicker(singleInput.id);
    });
    galleryPreview?.addEventListener('tokraftgallerychange', render);
    singlePreview?.addEventListener('tokraftimagechange', render);
    mode.addEventListener('change', render);
    section.dataset.tokraftHeroSlideEditorReady = 'true';
    render();
  };

  const initBlockManager = () => {
    const root = document.querySelector('.tokraft-home-settings');
    const form = root?.querySelector('form');
    if (!root || !form || root.querySelector('[data-tokraft-block-manager]')) return;

    const value = (id) => document.getElementById(id)?.value || '';
    const countItems = (id, fallback) => {
      const raw = value(id);
      return raw ? t('slide_count', raw.split(',').filter(Boolean).length) : fallback;
    };
    const blocks = [
      { id: 'tokraft-hero', title: t('hero_title'), description: t('hero_description'), icon: 'format-image', count: countItems('tokraft_home_settings_hero_slides', t('one_image')), visible: true, status: 'PUBLISHED' },
      { id: 'tokraft-routes', title: t('business_routes_title'), description: t('business_routes_description'), icon: 'networking', count: t('two_routes'), visible: true, status: 'PUBLISHED' },
      { id: 'tokraft-equipment', title: t('equipment_title'), description: t('equipment_description'), icon: 'admin-tools', count: t('card_count', value('tokraft_home_settings_equipment_count') || '0'), visible: true, status: 'PUBLISHED' },
      { id: 'tokraft-materials', title: t('materials_cases_title'), description: t('materials_cases_description'), icon: 'category', count: t('item_count', value('tokraft_home_settings_materials_count') || '0'), visible: true, status: 'PUBLISHED' },
      { id: 'tokraft-trust', title: t('final_cta_title'), description: t('final_cta_description'), icon: 'yes-alt', count: t('one_block'), visible: false, status: 'DRAFT' },
    ];
    const sections = new Map(blocks.map((block) => [block.id, document.getElementById(block.id)]));
    if (Array.from(sections.values()).some((section) => !section)) return;

    document.documentElement.classList.add('tokraft-block-manager-enabled');
    const manager = document.createElement('section');
    manager.className = 'tokraft-block-manager';
    manager.dataset.tokraftBlockManager = '';
    const homepageUrl = escapeHtml(i18n.homepage_url || '/');
    manager.innerHTML = `<header class="tokraft-block-manager__page-heading"><div><span>${t('content_website')}</span><h1>${t('home_page_blocks')}</h1><p>${t('home_page_blocks_help')}</p></div><div class="tokraft-block-manager__live"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><div class="tokraft-block-manager__live-copy"><small>${t('published_version')}</small><strong>${t('live_homepage_content')}</strong></div><a class="tokraft-block-manager__view-home" href="${homepageUrl}" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external" aria-hidden="true"></span><span>${t('view_homepage')}</span></a></div></header><div class="tokraft-block-manager__table"><header class="tokraft-block-manager__table-intro"><div><span>${t('page_composition')}</span><p>${t('page_composition_help')}</p></div><span class="tokraft-block-manager__filter"><span class="dashicons dashicons-filter"></span>${t('all_blocks')}</span></header><div class="tokraft-block-manager__table-head"><span>${t('order')}</span><span>${t('block')}</span><span>${t('visibility')}</span><span>${t('status')}</span><span>${t('edit_column')}</span></div><div class="tokraft-block-manager__rows"></div><footer>${t('blocks_footer')}</footer></div>`;
    const rows = manager.querySelector('.tokraft-block-manager__rows');
    blocks.forEach((block, index) => {
      const row = document.createElement('article');
      row.className = `tokraft-block-manager__row${index === 0 ? ' is-editing' : ''}`;
      row.dataset.tokraftBlockId = block.id;
      row.innerHTML = `<span class="tokraft-block-manager__order"><span class="dashicons dashicons-menu"></span>${String(index + 1).padStart(2, '0')}</span><div class="tokraft-block-manager__copy"><span class="tokraft-block-manager__icon dashicons dashicons-${block.icon}"></span><div><strong>${block.title}</strong><small>${block.description}</small></div></div><span class="tokraft-block-manager__count">${block.count}</span><span class="tokraft-block-manager__switch${block.visible ? '' : ' is-off'}" aria-label="${block.visible ? t('visible_on_homepage') : t('hidden_on_homepage')}"><i></i></span><span class="tokraft-block-manager__status${block.status === 'DRAFT' ? ' is-draft' : ''}">${block.status === 'DRAFT' ? t('draft') : t('published')}</span><button type="button" class="button" data-tokraft-open-block="${block.id}">${t('edit')} <span class="dashicons dashicons-edit"></span></button>`;
      rows.append(row);
    });
    form.before(manager);

    const staging = document.createElement('div');
    staging.className = 'tokraft-block-manager__staging';
    Array.from(sections.values()).forEach((section) => staging.append(section));
    form.prepend(staging);

    const layer = document.createElement('div');
    layer.className = 'tokraft-block-drawer-layer';
    layer.innerHTML = `<aside class="tokraft-block-drawer" aria-modal="true" aria-hidden="true" role="dialog"><header><div><span class="tokraft-block-drawer__kicker">${t('edit_block')}</span><h2></h2></div><span class="tokraft-block-drawer__draft">${t('draft')}</span><button type="button" class="button-link" data-tokraft-close-block aria-label="${t('close_editor')}">&times;</button></header><nav class="tokraft-block-drawer__tabs" aria-label="${t('hero_editor_tabs')}" hidden><button type="button" data-tokraft-drawer-tab="content"><span class="dashicons dashicons-editor-textcolor"></span>${t('content')}</button><button type="button" data-tokraft-drawer-tab="actions"><span class="dashicons dashicons-admin-links"></span>${t('actions')}</button><button type="button" data-tokraft-drawer-tab="slides"><span class="dashicons dashicons-format-gallery"></span><span data-tokraft-slides-tab-label>${t('slides_label')}</span></button></nav><div class="tokraft-block-drawer__content"></div><footer><button type="button" class="button" data-tokraft-close-block>${t('cancel')}</button><button type="submit" class="button button-primary">${t('save_homepage_changes')}</button></footer></aside>`;
    form.append(layer);
    const drawer = layer.querySelector('.tokraft-block-drawer');
    const drawerTitle = drawer.querySelector('h2');
    const drawerTabs = drawer.querySelector('.tokraft-block-drawer__tabs');
    const drawerContent = drawer.querySelector('.tokraft-block-drawer__content');
    const slidesTabLabel = drawer.querySelector('[data-tokraft-slides-tab-label]');
    let activeSection = null;
    let trigger = null;

    const prepareHeroTabs = (section) => {
      if (section.dataset.tokraftHeroTabsReady) return;
      const children = Array.from(section.children).slice(1);
      const panel = (name) => {
        const node = document.createElement('div');
        node.className = 'tokraft-block-drawer__panel';
        node.dataset.tokraftDrawerPanel = name;
        return node;
      };
      const content = panel('content');
      const actions = panel('actions');
      const slides = panel('slides');
      content.append(...children.slice(0, 2));
      if (children[7]) content.append(children[7]);
      actions.append(...children.slice(2, 4));
      slides.append(...children.slice(4, 7));
      section.append(content, actions, slides);
      section.dataset.tokraftHeroTabsReady = 'true';
    };

    const activateTab = (tabName) => {
      drawer.querySelectorAll('[data-tokraft-drawer-tab]').forEach((tab) => {
        const isActive = tab.dataset.tokraftDrawerTab === tabName;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', String(isActive));
      });
      activeSection?.querySelectorAll('[data-tokraft-drawer-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.tokraftDrawerPanel !== tabName;
      });
    };

    const updateHeroCount = (count, isCarousel) => {
      const rowCount = manager.querySelector('[data-tokraft-block-id="tokraft-hero"] .tokraft-block-manager__count');
      if (rowCount) rowCount.textContent = isCarousel ? t('slide_count', count) : t('one_image');
      if (slidesTabLabel) slidesTabLabel.textContent = isCarousel ? t('slides_with_count', count) : t('slides_label');
    };

    const close = () => {
      if (!activeSection) return;
      staging.append(activeSection);
      activeSection = null;
      layer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('tokraft-block-drawer-open');
      manager.querySelectorAll('.is-editing').forEach((row) => row.classList.remove('is-editing'));
      trigger?.focus();
    };

    const open = (id, opener) => {
      const section = sections.get(id);
      const block = blocks.find((item) => item.id === id);
      if (!section || !block) return;
      if (activeSection) staging.append(activeSection);
      activeSection = section;
      trigger = opener;
      drawerTitle.textContent = block.title;
      const isHero = id === 'tokraft-hero';
      drawerTabs.hidden = !isHero;
      if (isHero) {
        prepareHeroTabs(section);
        initHeroSlideEditor(section, updateHeroCount);
      }
      drawerContent.append(section);
      manager.querySelectorAll('.tokraft-block-manager__row').forEach((row) => row.classList.toggle('is-editing', row.dataset.tokraftBlockId === id));
      layer.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      document.body.classList.add('tokraft-block-drawer-open');
      if (isHero) activateTab('slides');
      drawer.querySelector('[data-tokraft-close-block]')?.focus();
    };

    manager.addEventListener('click', (event) => {
      const button = event.target.closest('[data-tokraft-open-block]');
      if (button) open(button.dataset.tokraftOpenBlock, button);
    });
    drawerTabs.addEventListener('click', (event) => {
      const tab = event.target.closest('[data-tokraft-drawer-tab]');
      if (tab) activateTab(tab.dataset.tokraftDrawerTab);
    });
    layer.addEventListener('click', (event) => {
      if (event.target === layer || event.target.closest('[data-tokraft-close-block]')) close();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && activeSection) close();
    });
  };

  const initColorPickers = (scope) => {
    if (!$.fn.wpColorPicker) return;
    $(scope || document).find('.tokraft-color-field').each((index, input) => {
      const field = $(input);
      if (field.data('tokraftColorPicker')) return;
      field.data('tokraftColorPicker', true).wpColorPicker();
    });
  };

  $(document).on('click', '[data-color-add]', (event) => {
    event.preventDefault();
    const repeater = event.currentTarget.closest('[data-color-repeater]');
    const template = repeater?.querySelector('[data-color-template]');
    const rows = repeater?.querySelector('[data-color-rows]');
    if (!template || !rows) return;
    // Index only has to be unique within the form; the server re-indexes on save.
    const markup = template.innerHTML.replace(/__index__/g, `new-${Date.now()}-${rows.children.length}`);
    rows.insertAdjacentHTML('beforeend', markup);
    initColorPickers(rows.lastElementChild);
    rows.lastElementChild?.querySelector('.tokraft-color-label')?.focus();
  });

  $(document).on('click', '[data-color-remove]', (event) => {
    event.preventDefault();
    const row = event.currentTarget.closest('[data-color-row]');
    const rows = row?.parentElement;
    if (!row || !rows) return;
    if (rows.children.length > 1) {
      row.remove();
      return;
    }
    // Keep one empty row so the field never disappears; an empty label is dropped on save.
    row.querySelector('.tokraft-color-label').value = '';
  });

  $(initBlockManager);
  $(initHeroVisualOptions);
  $(() => initColorPickers());
})(jQuery));
