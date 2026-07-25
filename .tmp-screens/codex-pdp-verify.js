const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const url = 'http://localhost:8080/product/pla-basic/';
const failures = [];
const browserErrors = [];

function check(condition, message) {
  if (!condition) failures.push(message);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1100 }, deviceScaleFactor: 1 });

  page.on('console', (message) => {
    if (message.type() === 'error') browserErrors.push(`console: ${message.text()}`);
  });
  page.on('pageerror', (error) => browserErrors.push(`pageerror: ${error.message}`));

  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.variations_form .tk-option-chips');
  await page.waitForFunction(() => Array.from(document.querySelectorAll('.tk-product-story img, .tk-product-workflow img')).every((image) => image.complete && image.naturalWidth > 0));
  await page.waitForTimeout(500);

  const initial = await page.evaluate(() => {
    const selects = Array.from(document.querySelectorAll('.variations_form select'));
    return selects.map((select) => ({
      id: select.id,
      name: select.name,
      value: select.value,
      options: Array.from(select.options).filter((option) => option.value && !option.disabled).map((option) => option.value),
    }));
  });
  const desktopLayout = await page.evaluate(() => ({
    clientWidth: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
    image: document.querySelector('.woocommerce-product-gallery__image img')?.currentSrc || '',
    railItems: document.querySelectorAll('.tk-variation-gallery-item').length,
    selectWidths: Array.from(document.querySelectorAll('.tk-native-select')).map((select) => select.getBoundingClientRect().width),
    topPrice: document.querySelector('.tk-product-current-price')?.textContent.trim() || '',
    summaryPosition: getComputedStyle(document.querySelector('.tk-product-summary')).position,
    detailCards: document.querySelectorAll('.tk-detail-card').length,
    specificationRows: document.querySelectorAll('#tk-product-panel-specs .tk-spec-row').length,
    storyImages: document.querySelectorAll('.tk-product-story img, .tk-product-workflow img').length,
    workflowSteps: document.querySelectorAll('.tk-workflow-copy li').length,
    discoverMore: {
      heading: document.querySelector('#tk-discover-more-heading')?.textContent.trim() || '',
      items: document.querySelectorAll('[data-tk-discover-item]').length,
      images: Array.from(document.querySelectorAll('.tk-discover-item-image img')).map((image) => ({
        src: image.currentSrc || image.src,
        naturalWidth: image.naturalWidth,
      })),
      prices: Array.from(document.querySelectorAll('.tk-discover-item-price')).map((price) => price.textContent.trim()),
    },
  }));
  check(desktopLayout.scrollWidth === desktopLayout.clientWidth, 'Desktop page has horizontal overflow.');
  check(desktopLayout.image.includes('pla-basic-spool'), 'PLA Basic did not load the intended spool image.');
  check(desktopLayout.railItems === 0, 'Duplicate variation images produced a misleading gallery rail.');
  check(desktopLayout.selectWidths.every((width) => width <= 1), 'Hidden variation selects still affect desktop layout.');
  check(desktopLayout.topPrice.includes('$24.99'), `Expected default price $24.99, received ${desktopLayout.topPrice}.`);
  check(desktopLayout.summaryPosition === 'sticky', 'Desktop purchase summary is not sticky.');
  check(desktopLayout.detailCards === 3, 'Expected three decision-focused detail cards.');
  check(desktopLayout.specificationRows >= 10, 'Product specifications are still too sparse.');
  check(desktopLayout.storyImages >= 2, 'Product story does not include enough editorial imagery.');
  check(desktopLayout.workflowSteps === 4, 'Product story workflow is incomplete.');
  check(desktopLayout.discoverMore.heading === 'Discover More Here!', 'Discover-more heading is missing.');
  check(desktopLayout.discoverMore.items === 3, 'Expected three real discover-more products.');
  check(desktopLayout.discoverMore.images.every((image) => image.src && image.naturalWidth > 0), 'Discover-more products are missing real product images.');
  check(desktopLayout.discoverMore.prices.every(Boolean), 'Discover-more products are missing current prices.');

  const gallerySticky = await page.evaluate(async () => {
    const gallery = document.querySelector('.tk-product-gallery');
    const main = document.querySelector('.tk-product-main');
    const details = document.querySelector('.tk-product-spec-section');
    const stickyTop = Number.parseFloat(getComputedStyle(gallery).top) || 0;
    const mainBottom = main.getBoundingClientRect().bottom + window.scrollY;
    const galleryHeight = gallery.getBoundingClientRect().height;
    const middleScroll = Math.max(0, Math.min(420, mainBottom - galleryHeight - stickyTop - 120));
    const originalScrollBehavior = document.documentElement.style.scrollBehavior;
    document.documentElement.style.scrollBehavior = 'auto';
    window.scrollTo(0, middleScroll);
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    const pinnedTop = gallery.getBoundingClientRect().top;
    const releaseScroll = Math.max(middleScroll + 1, mainBottom - galleryHeight - stickyTop + 56);
    window.scrollTo(0, releaseScroll);
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    const releasedTop = gallery.getBoundingClientRect().top;
    const detailsTop = details.getBoundingClientRect().top;
    window.scrollTo(0, 0);
    document.documentElement.style.scrollBehavior = originalScrollBehavior;
    return {
      position: getComputedStyle(gallery).position,
      stickyTop,
      pinnedTop,
      releasedTop,
      detailsTop,
    };
  });
  check(gallerySticky.position === 'sticky', 'Desktop product gallery is not sticky.');
  check(Math.abs(gallerySticky.pinnedTop - gallerySticky.stickyTop) <= 2, 'Product gallery does not remain pinned while the purchase section scrolls.');
  check(gallerySticky.releasedTop < gallerySticky.stickyTop - 20 && gallerySticky.detailsTop < 900, 'Product gallery does not release as product details enter view.');
  const initialValues = Object.fromEntries(initial.map((select) => [select.name, select.value]));
  check(initialValues.attribute_color === 'Mistletoe Green', 'Default colour is not Mistletoe Green.');
  check(initialValues.attribute_type === 'Filament with spool', 'Default format is not Filament with spool.');
  check(initialValues.attribute_size === '1 kg', 'Default size is not 1 kg.');
  const singleChoice = initial.find((select) => select.options.length === 1);
  check(Boolean(singleChoice), 'Expected a variation select with one available value.');
  check(Boolean(singleChoice?.value), 'The sole available variation value was not auto-selected.');

  const discoverFirstQuantity = page.locator('[data-tk-discover-item]').first().locator('.tk-discover-quantity-input');
  const discoverFirstIncrease = page.locator('[data-tk-discover-item]').first().locator('[data-tk-discover-quantity="increase"]');
  await discoverFirstIncrease.click();
  check(await discoverFirstQuantity.inputValue() === '2', 'Discover-more quantity control did not increase quantity.');
  await discoverFirstQuantity.fill('0');
  await discoverFirstQuantity.blur();
  check(await discoverFirstQuantity.inputValue() === '1', 'Discover-more quantity control did not enforce a minimum of one.');
  const discoverFirstAdd = page.locator('[data-tk-discover-item]').first().locator('.tk-discover-add-button');
  await discoverFirstAdd.click();
  await page.waitForFunction(() => document.querySelector('.tk-discover-add-button')?.textContent.trim() === 'Added');
  check(await discoverFirstAdd.textContent() === 'Added', 'Discover-more product was not added through the enhanced cart action.');

  const colorButton = page.locator('.tk-color-swatches button:not(:disabled)').first();
  await colorButton.click();

  const typeGroup = page.locator('.tk-option-chips[aria-label*="type" i]');
  const typeButtons = typeGroup.locator('button:not(:disabled)');
  check(await typeButtons.count() >= 2, 'Expected at least two enabled type options.');

  await page.evaluate(() => {
    const select = Array.from(document.querySelectorAll('.variations_form select')).find((item) => item.name.toLowerCase().includes('type'));
    window.__tkTypeChangeCount = 0;
    select?.addEventListener('change', () => { window.__tkTypeChangeCount += 1; });
  });
  await typeButtons.nth(1).click();
  await page.waitForFunction(() => Number(document.querySelector('input.variation_id')?.value || 0) > 0);
  await page.waitForTimeout(450);

  const selected = await page.evaluate(() => ({
    variationId: Number(document.querySelector('input.variation_id')?.value || 0),
    addDisabled: document.querySelector('.single_add_to_cart_button')?.matches(':disabled, .disabled, .wc-variation-selection-needed') ?? true,
    addOuter: document.querySelector('.single_add_to_cart_button')?.outerHTML || '',
    selectedButtons: Array.from(document.querySelectorAll('.tk-color-swatches button[aria-pressed="true"], .tk-option-chips button[aria-pressed="true"]')).length,
    typeChangeCount: window.__tkTypeChangeCount,
    price: document.querySelector('.woocommerce-variation-price .price')?.textContent.trim() || '',
    topPrice: document.querySelector('.tk-product-current-price')?.textContent.trim() || '',
    variationHtml: document.querySelector('.single_variation')?.innerHTML || '',
  }));
  check(selected.variationId > 0, 'Variation ID stayed at zero after selecting color and type.');
  check(!selected.addDisabled, 'Add-to-cart remained disabled after a complete variation selection.');
  check(selected.selectedButtons >= 3, 'Selected variation buttons do not expose aria-pressed=true.');
  check(selected.typeChangeCount === 1, `Type option emitted ${selected.typeChangeCount} change events instead of one.`);
  check(selected.topPrice.includes('$21.99'), `Expected refill price $21.99, received ${selected.topPrice}.`);

  const firstVariationId = selected.variationId;
  await typeButtons.first().click();
  await page.waitForFunction((previousId) => {
    const nextId = Number(document.querySelector('input.variation_id')?.value || 0);
    return nextId > 0 && nextId !== previousId;
  }, firstVariationId);
  await page.waitForTimeout(450);
  const alternate = await page.evaluate(() => ({
    variationId: Number(document.querySelector('input.variation_id')?.value || 0),
    price: document.querySelector('.woocommerce-variation-price .price')?.textContent.trim() || '',
    topPrice: document.querySelector('.tk-product-current-price')?.textContent.trim() || '',
  }));
  check(alternate.variationId !== firstVariationId, 'Changing type did not select a different variation.');
  check(Boolean(selected.price) && Boolean(alternate.price) && selected.price !== alternate.price, 'Variation price did not update between type choices.');
  check(alternate.topPrice.includes('$24.99'), `Expected spool price $24.99, received ${alternate.topPrice}.`);

  const specsTab = page.locator('#tk-product-tab-specs');
  await specsTab.click();
  const clickTabState = await page.evaluate(() => ({
    selected: document.querySelector('#tk-product-tab-specs')?.getAttribute('aria-selected'),
    specsHidden: document.querySelector('#tk-product-panel-specs')?.hidden,
    detailsHidden: document.querySelector('#tk-product-panel-details')?.hidden,
  }));
  check(clickTabState.selected === 'true', 'Clicked tab did not update aria-selected.');
  check(clickTabState.specsHidden === false && clickTabState.detailsHidden === true, 'Clicked tab did not update panel hidden state.');

  await specsTab.press('ArrowRight');
  const keyboardTabState = await page.evaluate(() => ({
    selected: document.querySelector('#tk-product-tab-ship')?.getAttribute('aria-selected'),
    focused: document.activeElement?.id,
    shipHidden: document.querySelector('#tk-product-panel-ship')?.hidden,
  }));
  check(keyboardTabState.selected === 'true', 'ArrowRight did not activate the next tab.');
  check(keyboardTabState.focused === 'tk-product-tab-ship', 'ArrowRight did not move focus to the next tab.');
  check(keyboardTabState.shipHidden === false, 'ArrowRight did not reveal the next tab panel.');

  await page.locator('#tk-product-tab-details').click();
  for (const image of await page.locator('.tk-product-story img, .tk-product-workflow img').all()) {
    await image.scrollIntoViewIfNeeded();
    await page.waitForTimeout(100);
  }
  await page.screenshot({ path: '.tmp-screens/codex-pdp-desktop-final.png', fullPage: true });

  const mobilePage = await browser.newPage({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 });
  mobilePage.on('console', (message) => {
    if (message.type() === 'error') browserErrors.push(`mobile console: ${message.text()}`);
  });
  mobilePage.on('pageerror', (error) => browserErrors.push(`mobile pageerror: ${error.message}`));
  await mobilePage.goto(url, { waitUntil: 'domcontentloaded' });
  await mobilePage.waitForSelector('.tk-product-detail');
  await mobilePage.waitForFunction(() => Array.from(document.querySelectorAll('.tk-product-story img, .tk-product-workflow img')).every((image) => image.complete && image.naturalWidth > 0));
  await mobilePage.waitForTimeout(500);
  const mobileLayout = await mobilePage.evaluate(() => ({
    clientWidth: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
    selectWidths: Array.from(document.querySelectorAll('.tk-native-select')).map((select) => select.getBoundingClientRect().width),
    storyImages: Array.from(document.querySelectorAll('.tk-product-story img, .tk-product-workflow img')).map((image) => ({
      src: image.currentSrc,
      naturalWidth: image.naturalWidth,
      rect: image.getBoundingClientRect().toJSON(),
      display: getComputedStyle(image).display,
      visibility: getComputedStyle(image).visibility,
      opacity: getComputedStyle(image).opacity,
      objectFit: getComputedStyle(image).objectFit,
    })),
    gallery: (() => {
      const frame = document.querySelector('.tk-product-gallery-frame');
      const gallery = document.querySelector('.woocommerce-product-gallery');
      const viewport = document.querySelector('.flex-viewport');
      const image = document.querySelector('.woocommerce-product-gallery__image img');
      const rect = (element) => element ? element.getBoundingClientRect().toJSON() : null;
      return {
        frame: rect(frame),
        gallery: rect(gallery),
        viewport: rect(viewport),
        image: rect(image),
        imageSrc: image?.currentSrc || '',
        imageComplete: image?.complete || false,
        imageStyle: image ? {
          display: getComputedStyle(image).display,
          visibility: getComputedStyle(image).visibility,
          opacity: getComputedStyle(image).opacity,
          objectFit: getComputedStyle(image).objectFit,
        } : null,
      };
    })(),
  }));
  check(mobileLayout.scrollWidth === mobileLayout.clientWidth, 'Mobile page has horizontal overflow.');
  check(mobileLayout.selectWidths.every((width) => width <= 1), 'Hidden variation selects still affect mobile layout.');
  for (const image of await mobilePage.locator('.tk-product-story img, .tk-product-workflow img').all()) {
    await image.scrollIntoViewIfNeeded();
    await mobilePage.waitForTimeout(100);
  }
  await mobilePage.screenshot({ path: '.tmp-screens/codex-pdp-mobile-final.png', fullPage: true });
  await mobilePage.close();

  const breadcrumbCases = [
    { path: '/product/reusable-spool/', label: 'Accessories' },
    { path: '/product/pla-basic-starter-classic-pack/', label: 'Bundles' },
  ];
  const breadcrumbs = {};
  for (const item of breadcrumbCases) {
    await page.goto(`http://localhost:8080${item.path}`, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('.tk-product-breadcrumb');
    breadcrumbs[item.path] = (await page.locator('.tk-product-breadcrumb').innerText()).replace(/\s+/g, ' ').trim();
    check(breadcrumbs[item.path].toLowerCase().includes(item.label.toLowerCase()), `${item.path} breadcrumb did not include ${item.label}.`);
  }

  check(browserErrors.length === 0, `Browser errors: ${browserErrors.join(' | ')}`);
  const report = { initial, desktopLayout, gallerySticky, selected, alternate, clickTabState, keyboardTabState, mobileLayout, breadcrumbs, browserErrors, failures };
  console.log(JSON.stringify(report, null, 2));
  await browser.close();
  if (failures.length) process.exit(1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
