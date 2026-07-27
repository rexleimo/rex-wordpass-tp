const { chromium } = require('C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const url = 'http://localhost:8080/what-happens-after-you-submit-a-quote/';
const failures = [];
const browserErrors = [];

function check(condition, message) {
  if (!condition) failures.push(message);
}

async function inspectPage(page) {
  return page.evaluate(() => {
    const rect = (element) => element ? element.getBoundingClientRect().toJSON() : null;
    const computed = (element) => {
      if (!element) return null;
      const style = getComputedStyle(element);
      return {
        color: style.color,
        display: style.display,
        gridTemplateColumns: style.gridTemplateColumns,
        position: style.position,
      };
    };
    const hero = document.querySelector('.blog-single-hero');
    const cover = document.querySelector('.blog-single-cover');
    return {
      hero: {
        hasCover: hero?.classList.contains('has-cover') || false,
        rect: rect(hero),
        style: computed(hero),
      },
      cover: { rect: rect(cover), style: computed(cover) },
      title: { rect: rect(document.querySelector('.blog-single-hero h1')), style: computed(document.querySelector('.blog-single-hero h1')) },
      body: { rect: rect(document.querySelector('.blog-single-body')), style: computed(document.querySelector('.blog-single-body')) },
      steps: Array.from(document.querySelectorAll('.blog-step')).map((step) => {
        const copy = Array.from(step.children).find((child) => child.tagName === 'DIV');
        const emptyParagraph = Array.from(step.children).find((child) => child.tagName === 'P' && !child.textContent.trim());
        return {
          rect: rect(step),
          copy: { rect: rect(copy), style: computed(copy) },
          emptyParagraph: { rect: rect(emptyParagraph), style: computed(emptyParagraph) },
        };
      }),
      inlineImages: Array.from(document.querySelectorAll('.blog-single-content figure img')).map((image) => ({
        complete: image.complete,
        naturalWidth: image.naturalWidth,
        rect: rect(image),
      })),
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
    };
  });
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const desktop = await browser.newPage({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });
  desktop.on('console', (message) => {
    if (message.type() === 'error') browserErrors.push(`desktop console: ${message.text()}`);
  });
  desktop.on('pageerror', (error) => browserErrors.push(`desktop pageerror: ${error.message}`));
  await desktop.goto(url, { waitUntil: 'networkidle' });
  await desktop.waitForSelector('.blog-single');
  await desktop.waitForFunction(() => Array.from(document.querySelectorAll('.blog-single-cover img, .blog-single-content figure img')).every((image) => image.complete && image.naturalWidth > 0));
  const desktopReport = await inspectPage(desktop);
  await desktop.screenshot({ path: '.tmp-screens/blog-detail-desktop-final.png', fullPage: true });

  check(desktopReport.scrollWidth === desktopReport.clientWidth, 'Desktop blog page has horizontal overflow.');
  check(desktopReport.hero.hasCover, 'Article hero is missing its cover treatment.');
  check(desktopReport.hero.style?.position === 'relative', 'Article hero is not the overlay positioning context.');
  check(desktopReport.cover.style?.position === 'absolute', 'Article cover is not layered behind the copy.');
  check(Math.abs((desktopReport.hero.rect.width / desktopReport.hero.rect.height) - (16 / 9)) < 0.03, 'Desktop article cover does not use the 16:9 standard ratio.');
  check(desktopReport.title.style?.color === 'rgb(255, 255, 255)', 'Article title does not have readable cover-overlay contrast.');
  check(desktopReport.steps.length === 4, 'Expected four review-loop cards.');
  check(desktopReport.steps.every((step) => step.copy.rect.width > 200 && step.rect.height < 260), 'A review-loop card still has collapsed copy.');
  check(desktopReport.steps.every((step) => step.emptyParagraph.style?.display === 'none'), 'An injected empty paragraph still consumes a review-loop grid cell.');
  check(desktopReport.inlineImages.every((image) => image.complete && image.naturalWidth > 0 && Math.abs((image.rect.width / image.rect.height) - (16 / 9)) < 0.03), 'Inline article images do not use the standard 16:9 ratio.');

  const mobile = await browser.newPage({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 });
  mobile.on('console', (message) => {
    if (message.type() === 'error') browserErrors.push(`mobile console: ${message.text()}`);
  });
  mobile.on('pageerror', (error) => browserErrors.push(`mobile pageerror: ${error.message}`));
  await mobile.goto(url, { waitUntil: 'networkidle' });
  await mobile.waitForSelector('.blog-single');
  const mobileReport = await inspectPage(mobile);
  await mobile.screenshot({ path: '.tmp-screens/blog-detail-mobile-final.png', fullPage: true });

  check(mobileReport.scrollWidth === mobileReport.clientWidth, 'Mobile blog page has horizontal overflow.');
  check(Math.abs((mobileReport.hero.rect.width / mobileReport.hero.rect.height) - (4 / 5)) < 0.03, 'Mobile article cover does not use the 4:5 standard ratio.');
  check(mobileReport.steps.every((step) => step.rect.width > 300 && step.copy.rect.width > 220), 'Mobile review-loop cards did not collapse to a readable single column.');
  check(browserErrors.length === 0, `Browser errors: ${browserErrors.join(' | ')}`);

  const report = { desktop: desktopReport, mobile: mobileReport, browserErrors, failures };
  console.log(JSON.stringify(report, null, 2));
  await browser.close();
  if (failures.length) process.exit(1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
