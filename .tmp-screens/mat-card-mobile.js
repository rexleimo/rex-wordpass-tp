const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async () => {
  const b = await chromium.launch({ headless: true });
  const p = await (await b.newContext({ viewport: { width: 390, height: 844 } })).newPage();
  await p.goto('http://localhost:8080/materials/', { waitUntil: 'networkidle' });
  await p.waitForTimeout(400);
  console.log(JSON.stringify(await p.evaluate(() => ({
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
    cards: Array.from(document.querySelectorAll('.material-library-card.swiper-slide')).slice(0,3).map((s) => {
      const c = s.querySelector('.material-library-action').getBoundingClientRect();
      const r = s.getBoundingClientRect();
      return { slug: s.dataset.materialSlug, h: Math.round(r.height), ctaBottomGap: Math.round(r.bottom - c.bottom) };
    }),
  }))));
  await p.locator('.material-library-stage').screenshot({ path: '.tmp-screens/mat-card-mobile.png' });
  await b.close();
})().catch((e)=>{console.error(e);process.exit(1);});
