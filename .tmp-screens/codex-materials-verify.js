const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const baseUrl = 'http://localhost:8080';
const failures = [];

function expect(condition, message) {
  if (!condition) failures.push(message);
}

async function inspect(page) {
  return page.evaluate(() => {
    const rect = (node) => {
      if (!node) return null;
      const box = node.getBoundingClientRect();
      return { top: box.top, right: box.right, bottom: box.bottom, left: box.left, width: box.width, height: box.height };
    };
    const cards = Array.from(document.querySelectorAll('.material-library-card'));
    const images = Array.from(document.querySelectorAll('.material-library-card:not(.is-featured) .material-library-image'));
    const grid = document.querySelector('.material-library-grid');
    const featured = document.querySelector('.material-library-card.is-featured');
    const standard = cards.filter((card) => !card.classList.contains('is-featured'));
    return {
      viewport: { clientWidth: document.documentElement.clientWidth, scrollWidth: document.documentElement.scrollWidth },
      hero: rect(document.querySelector('.materials-hero')),
      grid: rect(grid),
      cards: cards.map((card) => ({ ...rect(card), featured: card.classList.contains('is-featured'), wide: card.classList.contains('is-last-wide') })),
      featured: rect(featured),
      standard: standard.map(rect),
      imageHeights: images.map((image) => rect(image).height),
      guidance: Boolean(document.querySelector('.material-library-guidance')),
      gridColumns: grid ? getComputedStyle(grid).gridTemplateColumns.split(' ').length : 0,
    };
  });
}

function check(data, mode) {
  expect(data.viewport.scrollWidth <= data.viewport.clientWidth, `${mode}: materials page has horizontal overflow`);
  expect(Boolean(data.hero && data.grid && data.featured), `${mode}: materials hero or catalogue shell is missing`);
  expect(data.cards.length >= 6, `${mode}: material library is incomplete`);
  expect(data.guidance, `${mode}: material guidance is unavailable`);
  expect(data.imageHeights.every((height) => Math.abs(height - data.imageHeights[0]) < 2), `${mode}: standard material image frames are not uniform`);
  if (mode === 'desktop') {
    expect(data.gridColumns === 2, 'desktop: materials are not presented in a two-column browsing rhythm');
    expect(data.featured.width >= data.grid.width - 2, 'desktop: featured material does not anchor the full material grid');
    expect(data.standard.every((card) => card.width >= data.grid.width * .45), 'desktop: material cards are too narrow');
  }
  if (mode === 'mobile') {
    expect(data.gridColumns === 1, 'mobile: material library does not collapse to one readable column');
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
  await page.goto(`${baseUrl}/materials/`, { waitUntil: 'networkidle' });
  const data = await inspect(page);
  const guidance = page.locator('.material-library-guidance').nth(1);
  await guidance.locator('summary').click();
  const expanded = await guidance.evaluate((node) => {
    const content = node.querySelector('.material-library-guidance-inner');
    const nodeBox = node.getBoundingClientRect();
    const contentBox = content?.getBoundingClientRect();
    return { open: node.open, contentHeight: contentBox?.height || 0, contained: contentBox ? contentBox.bottom <= nodeBox.bottom + 1 : false };
  });
  await guidance.locator('summary').click();
  await page.screenshot({ path: `.tmp-screens/materials-${mode}-final.png`, fullPage: true });
  check(data, mode);
  expect(expanded.open && expanded.contentHeight > 0 && expanded.contained, `${mode}: material guidance cannot be expanded without clipping`);
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
  console.log('Materials visual checks passed for desktop and mobile.');
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
