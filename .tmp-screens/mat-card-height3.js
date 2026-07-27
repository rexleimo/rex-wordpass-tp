const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
  await page.goto('http://localhost:8080/materials/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);
  console.log(JSON.stringify(await page.evaluate(() => {
    const card = document.querySelector('.material-library-card.swiper-slide[data-material-slug="nylon-pa"]');
    const cs = (n) => { const s = getComputedStyle(n); return { display: s.display, flex: s.flex, padding: s.padding, gap: s.rowGap, height: s.height, minHeight: s.minHeight, alignSelf: s.alignSelf, marginBottom: s.marginBottom }; };
    return {
      cardStyle: cs(card),
      children: Array.from(card.children).map((c) => ({ cls: c.className, ...cs(c) })),
      copyChildren: Array.from(card.querySelector('.material-library-copy').children).map((c) => ({ cls: c.className, ...cs(c) })),
    };
  }), null, 1));
  await browser.close();
})().catch((e)=>{console.error(e);process.exit(1);});
