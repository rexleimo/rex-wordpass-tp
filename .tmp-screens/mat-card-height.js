const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });
  const page = await context.newPage();
  await page.goto('http://localhost:8080/materials/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);
  const data = await page.evaluate(() => {
    const slides = Array.from(document.querySelectorAll('.material-library-card.swiper-slide'));
    return slides.slice(0, 4).map((slide) => {
      const cta = slide.querySelector('.material-library-action');
      const b = slide.getBoundingClientRect();
      const c = cta.getBoundingClientRect();
      return { slug: slide.dataset.materialSlug, height: Math.round(b.height), bottom: Math.round(b.bottom), ctaTop: Math.round(c.top), ctaBottom: Math.round(c.bottom) };
    });
  });
  console.log(JSON.stringify(data, null, 1));
  await page.locator('.material-library-stage').screenshot({ path: '.tmp-screens/mat-card-height.png' });
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
