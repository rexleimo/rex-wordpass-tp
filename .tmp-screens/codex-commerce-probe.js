const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const baseUrl = 'http://localhost:8080';

async function addProduct(page, slug) {
  await page.goto(`${baseUrl}/product/${slug}/`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.tk-product-detail');
  const id = await page.locator('form.cart [name="add-to-cart"]').first().getAttribute('value');
  if (!id) throw new Error(`Could not find add-to-cart ID for ${slug}.`);
  await page.goto(`${baseUrl}/?add-to-cart=${id}`, { waitUntil: 'networkidle' });
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });
  const page = await context.newPage();
  const errors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') errors.push(`console: ${message.text()}`);
  });
  page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));

  await page.goto(`${baseUrl}/product/reusable-spool/`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.tk-product-detail');
  const product = await page.evaluate(() => {
    const rect = (element) => element ? element.getBoundingClientRect().toJSON() : null;
    const box = (selector) => {
      const element = document.querySelector(selector);
      if (!element) return null;
      const style = getComputedStyle(element);
      return { selector, rect: rect(element), display: style.display, position: style.position, gridTemplateColumns: style.gridTemplateColumns, width: style.width };
    };
    return ({
    rail: box('.tk-product-tech-rail'),
    values: Array.from(document.querySelectorAll('.tk-product-tech-rail strong')).map((element) => ({
      text: element.textContent.trim(),
      rect: rect(element),
      whiteSpace: getComputedStyle(element).whiteSpace,
      overflow: getComputedStyle(element).overflow,
      textOverflow: getComputedStyle(element).textOverflow,
    })),
    quantity: box('.tk-product-buy .quantity'),
    addButton: box('.tk-product-buy .single_add_to_cart_button'),
    cartForm: box('.tk-product-buy form.cart'),
    cartHtml: document.querySelector('.tk-product-buy form.cart')?.outerHTML || '',
    clientWidth: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
    });
  });
  await page.screenshot({ path: '.tmp-screens/commerce-product-before.png', fullPage: true });

  await addProduct(page, 'reusable-spool');
  await addProduct(page, 'pla-cmyk-lithophane-bundle');
  await page.goto(`${baseUrl}/cart/`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.tk-cart-table');
  const cart = await page.evaluate(() => {
    const rect = (element) => element ? element.getBoundingClientRect().toJSON() : null;
    const box = (selector) => {
      const element = document.querySelector(selector);
      if (!element) return null;
      const style = getComputedStyle(element);
      return { selector, rect: rect(element), display: style.display, position: style.position, gridTemplateColumns: style.gridTemplateColumns, width: style.width };
    };
    return ({
    table: box('.tk-cart-table'),
    form: box('.tk-cart-form'),
    collaterals: box('.tk-cart-collaterals'),
    totals: box('.tk-cart-collaterals .cart_totals'),
    crossSells: box('.tk-cart-collaterals .cross-sells'),
    checkoutButton: box('.tk-cart-collaterals .checkout-button'),
    rows: Array.from(document.querySelectorAll('.tk-cart-table .cart_item')).map((element) => rect(element)),
    clientWidth: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
    });
  });
  await page.screenshot({ path: '.tmp-screens/commerce-cart-before.png', fullPage: true });

  await page.goto(`${baseUrl}/checkout/`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);
  const checkout = await page.evaluate(() => {
    const rect = (element) => element ? element.getBoundingClientRect().toJSON() : null;
    const box = (selector) => {
      const element = document.querySelector(selector);
      if (!element) return null;
      const style = getComputedStyle(element);
      return { selector, rect: rect(element), display: style.display, position: style.position, gridTemplateColumns: style.gridTemplateColumns, width: style.width };
    };
    return ({
    checkout: box('.wc-block-checkout'),
    fields: box('.wc-block-components-checkout-step'),
    orderSummary: box('.wc-block-checkout__sidebar'),
    orderSummaryPanel: box('.wc-block-components-totals-wrapper'),
    placeOrder: box('.wc-block-components-checkout-place-order-button'),
    clientWidth: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
    bodyClasses: Array.from(document.body.classList),
    });
  });
  await page.screenshot({ path: '.tmp-screens/commerce-checkout-before.png', fullPage: true });

  console.log(JSON.stringify({ product, cart, checkout, errors }, null, 2));
  await browser.close();
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
