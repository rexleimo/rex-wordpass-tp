const path = require('path');
const PW = 'C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright';
const { chromium } = require(PW);

const BASE = process.env.BASE || 'http://localhost:8080';
const OUT = path.resolve(__dirname);

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 1000 },
  { name: 'mobile', width: 390, height: 844 },
];

// Pages under test; `slug` becomes the screenshot filename prefix.
const PAGES = (process.env.PAGES || 'shop,case,quote').split(',');
const ROUTES = {
  shop: { slug: 'showcase-shop', url: '/shop/' },
  case: { slug: 'showcase-case', url: '/case-studies/outdoor-asa-cable-guide/' },
  quote: { slug: 'showcase-quote', url: '/quote/' },
  pdp: { slug: 'showcase-pdp', url: process.env.PDP_URL || '/shop/' },
  home: { slug: 'showcase-home', url: '/' },
};

(async () => {
  const browser = await chromium.launch();
  let failures = 0;

  for (const key of PAGES) {
    const route = ROUTES[key];
    if (!route) {
      console.log(`SKIP unknown route "${key}"`);
      continue;
    }

    for (const vp of VIEWPORTS) {
      const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
      const page = await context.newPage();
      const errors = [];
      page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
      page.on('pageerror', (e) => errors.push(String(e)));

      const url = BASE + route.url;
      const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
      await page.waitForTimeout(600);

      const metrics = await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        // Anything sticking past the viewport is a layout bug, not a design choice.
        overflowing: Array.from(document.querySelectorAll('body *'))
          .filter((el) => el.getBoundingClientRect().right > document.documentElement.clientWidth + 1)
          .slice(0, 6)
          .map((el) => `${el.tagName.toLowerCase()}.${(el.className || '').toString().split(' ')[0]}`),
      }));

      const overflow = metrics.scrollWidth > metrics.clientWidth + 1;
      const status = response ? response.status() : 0;
      const bad = status >= 400 || overflow || errors.length;
      if (bad) failures++;

      console.log(
        `${bad ? 'FAIL' : 'ok  '} ${route.slug}/${vp.name} status=${status} ` +
        `scroll=${metrics.scrollWidth}/${metrics.clientWidth}` +
        (overflow ? ` overflowing=[${metrics.overflowing.join(', ')}]` : '') +
        (errors.length ? ` console=${JSON.stringify(errors.slice(0, 3))}` : '')
      );

      await page.screenshot({
        path: path.join(OUT, `${route.slug}-${vp.name}.png`),
        fullPage: true,
      });
      await context.close();
    }
  }

  await browser.close();
  process.exit(failures ? 1 : 0);
})();
