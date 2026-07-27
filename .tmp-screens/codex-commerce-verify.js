const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const baseUrl = 'http://localhost:8080';
const failures = [];

function expect(condition, message) {
  if (!condition) failures.push(message);
}

async function addProduct(page, slug) {
  await page.goto(`${baseUrl}/product/${slug}/`, { waitUntil: 'networkidle' });
  const id = await page.locator('form.cart [name="add-to-cart"]').first().getAttribute('value');
  if (!id) throw new Error(`Could not find add-to-cart ID for ${slug}.`);
  await page.goto(`${baseUrl}/?add-to-cart=${id}`, { waitUntil: 'networkidle' });
}

async function snapshotPage(page, name) {
  await page.screenshot({ path: `.tmp-screens/${name}.png`, fullPage: true });
}

async function inspect(page) {
  return page.evaluate(() => {
    const box = (selector) => {
      const node = document.querySelector(selector);
      if (!node) return null;
      const rect = node.getBoundingClientRect();
      const style = getComputedStyle(node);
      return {
        bottom: rect.bottom,
        height: rect.height,
        left: rect.left,
        right: rect.right,
        top: rect.top,
        width: rect.width,
        display: style.display,
        overflow: style.overflow,
        textOverflow: style.textOverflow,
        whiteSpace: style.whiteSpace,
      };
    };
    const radio = document.querySelector('.wc-block-components-radio-control__option');
    const radioInput = radio?.querySelector('.wc-block-components-radio-control__input');
    const radioLabel = radio?.querySelector('.wc-block-components-radio-control__label');
    const radioBox = (node) => {
      if (!node) return null;
      const rect = node.getBoundingClientRect();
      return { left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom };
    };

    return {
      viewport: {
        clientWidth: document.documentElement.clientWidth,
        scrollWidth: document.documentElement.scrollWidth,
      },
      product: {
        cart: box('.tk-product-buy form.cart'),
        quantity: box('.tk-product-buy form.cart:not(.variations_form) .quantity'),
        button: box('.tk-product-buy form.cart:not(.variations_form) .single_add_to_cart_button'),
        railValues: Array.from(document.querySelectorAll('.tk-product-tech-rail strong')).map((node) => {
          const rect = node.getBoundingClientRect();
          const style = getComputedStyle(node);
          return {
            clientHeight: node.clientHeight,
            scrollHeight: node.scrollHeight,
            height: rect.height,
            overflow: style.overflow,
            textOverflow: style.textOverflow,
            whiteSpace: style.whiteSpace,
          };
        }),
      },
      cart: {
        assurance: box('.tk-cart-assurance'),
        totals: box('.tk-cart-collaterals .cart_totals'),
        checkoutButton: box('.tk-cart-collaterals .checkout-button'),
      },
      checkout: {
        root: box('.tk-checkout-page'),
        form: box('.wc-block-components-sidebar-layout'),
        sidebar: box('.wc-block-checkout__sidebar'),
        placeOrder: box('.wc-block-components-checkout-place-order-button'),
        fieldHeights: Array.from(document.querySelectorAll('.wc-block-components-form .wc-block-components-text-input input')).map((node) => node.getBoundingClientRect().height),
        radioInput: radioBox(radioInput),
        radioLabel: radioBox(radioLabel),
        mobileOrderSummaryDuplicates: Array.from(document.querySelectorAll('.checkout-order-summary-block-fill-wrapper')).filter((node) => getComputedStyle(node).display !== 'none').length,
      },
    };
  });
}

function checkProduct(data, mode) {
  expect(data.viewport.scrollWidth <= data.viewport.clientWidth, `${mode}: product has horizontal overflow`);
  expect(data.product.railValues.length === 3, `${mode}: product technical rail is incomplete`);
  data.product.railValues.forEach((value, index) => {
    expect(value.whiteSpace !== 'nowrap' && value.overflow !== 'hidden' && value.textOverflow !== 'ellipsis', `${mode}: product rail fact ${index + 1} is truncated`);
    expect(value.scrollHeight <= value.clientHeight + 1, `${mode}: product rail fact ${index + 1} is visually clipped`);
  });
  if (mode === 'desktop') {
    expect(Math.abs(data.product.quantity.top - data.product.button.top) <= 1, 'desktop: product quantity and CTA are not aligned');
    expect(data.product.button.width > data.product.quantity.width * 2, 'desktop: product CTA is too narrow');
  }
}

function checkCart(data, mode) {
  expect(data.viewport.scrollWidth <= data.viewport.clientWidth, `${mode}: cart has horizontal overflow`);
  expect(Boolean(data.cart.assurance), `${mode}: cart review panel is missing`);
  expect(Boolean(data.cart.totals), `${mode}: cart totals are missing`);
  expect(Boolean(data.cart.checkoutButton), `${mode}: cart checkout CTA is missing`);
  if (mode === 'desktop') {
    expect(data.cart.totals.width >= 340, 'desktop: cart totals card is compressed');
    expect(data.cart.checkoutButton.width >= data.cart.totals.width - 52, 'desktop: cart checkout CTA is not full width');
  }
}

function checkCheckout(data, mode) {
  expect(data.viewport.scrollWidth <= data.viewport.clientWidth, `${mode}: checkout has horizontal overflow`);
  expect(Boolean(data.checkout.root && data.checkout.form && data.checkout.sidebar), `${mode}: checkout commerce shell is incomplete`);
  expect(data.checkout.placeOrder?.height >= 52, `${mode}: checkout place-order button is undersized`);
  expect(data.checkout.fieldHeights.every((height) => height >= 50), `${mode}: checkout fields are undersized`);
  expect(data.checkout.radioLabel?.left >= data.checkout.radioInput?.right + 6, `${mode}: payment label overlaps its radio control`);
  if (mode === 'desktop') {
    expect(data.checkout.form.width >= 1200, 'desktop: checkout shell is too narrow');
    expect(data.checkout.sidebar.width >= 340, 'desktop: checkout summary is too narrow');
  }
  if (mode === 'mobile') {
    expect(data.checkout.mobileOrderSummaryDuplicates === 0, 'mobile: checkout order summary is duplicated');
  }
}

async function runViewport(browser, viewport, mode) {
  const context = await browser.newContext({ viewport, deviceScaleFactor: 1 });
  const page = await context.newPage();
  const errors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') errors.push(`console: ${message.text()}`);
  });
  page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));

  await page.goto(`${baseUrl}/product/reusable-spool/`, { waitUntil: 'networkidle' });
  const product = await inspect(page);
  await snapshotPage(page, `commerce-product-${mode}-final`);
  checkProduct(product, mode);

  await addProduct(page, 'reusable-spool');
  await addProduct(page, 'pla-cmyk-lithophane-bundle');
  await page.goto(`${baseUrl}/cart/`, { waitUntil: 'networkidle' });
  const cart = await inspect(page);
  await snapshotPage(page, `commerce-cart-${mode}-final`);
  checkCart(cart, mode);

  await page.goto(`${baseUrl}/checkout/`, { waitUntil: 'networkidle' });
  await page.waitForFunction(
    () => document.querySelector('.wc-block-checkout__sidebar')?.textContent.includes('Reusable Spool'),
    null,
    { timeout: 8000 }
  );
  const checkout = await inspect(page);
  await snapshotPage(page, `commerce-checkout-${mode}-final`);
  checkCheckout(checkout, mode);
  errors.forEach((error) => failures.push(`${mode}: ${error}`));

  await context.close();
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  await runViewport(browser, { width: 1440, height: 1000 }, 'desktop');
  await runViewport(browser, { width: 390, height: 844 }, 'mobile');
  await browser.close();

  if (failures.length) {
    console.error(failures.join('\n'));
    process.exit(1);
  }

  console.log('Commerce visual checks passed for desktop and mobile.');
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
