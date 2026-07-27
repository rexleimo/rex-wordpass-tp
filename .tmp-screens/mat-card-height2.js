const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
  await page.goto('http://localhost:8080/materials/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);
  console.log(JSON.stringify(await page.evaluate(() => {
    const r = (n) => { const b = n.getBoundingClientRect(); return { top: Math.round(b.top), bottom: Math.round(b.bottom), h: Math.round(b.height) }; };
    return Array.from(document.querySelectorAll('.material-library-card.swiper-slide')).slice(0,4).map((s) => ({
      slug: s.dataset.materialSlug,
      card: r(s),
      img: r(s.querySelector('.material-library-image')),
      copy: r(s.querySelector('.material-library-copy')),
      guidance: r(s.querySelector('.material-library-guidance')),
      cta: r(s.querySelector('.material-library-action')),
    }));
  }), null, 1));
  await browser.close();
})().catch((e)=>{console.error(e);process.exit(1);});
