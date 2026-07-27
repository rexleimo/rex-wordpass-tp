const { chromium } = require('C:/Users/Administrator/.rexcil/harness-cli/mcp-server/node_modules/playwright');
const path = require('path');
const fs = require('fs');

const profileDir = 'C:/Users/Administrator/AppData/Local/Temp/codex-wordpress-admin-profile';
const outputDir = 'E:/coding/rex-wordpass-tp/deliverables/backend-user-guide/screenshots/raw';

const screens = [
  ['01-dashboard.png', '/wp-admin/'],
  ['02-posts.png', '/wp-admin/edit.php'],
  ['03-media.png', '/wp-admin/upload.php'],
  ['04-products.png', '/wp-admin/edit.php?post_type=product'],
  ['05-orders.png', '/wp-admin/admin.php?page=wc-orders'],
  ['06-pages.png', '/wp-admin/edit.php?post_type=page'],
  ['07-menus.png', '/wp-admin/nav-menus.php'],
  ['08-woocommerce-settings.png', '/wp-admin/admin.php?page=wc-settings'],
];

(async () => {
  fs.mkdirSync(outputDir, { recursive: true });
  const context = await chromium.launchPersistentContext(profileDir, {
    channel: 'chrome',
    headless: false,
    viewport: { width: 1440, height: 1000 },
    deviceScaleFactor: 1,
  });
  const page = context.pages()[0] || await context.newPage();

  for (const [filename, adminPath] of screens) {
    await page.goto(`http://localhost:8080${adminPath}`, { waitUntil: 'networkidle' });
    if (page.url().includes('wp-login.php')) {
      throw new Error('The copied browser profile is not authenticated.');
    }
    await page.screenshot({ path: path.join(outputDir, filename), fullPage: false });
    console.log(`${filename}: ${page.title()}`);
  }

  await context.close();
})().catch((error) => {
  console.error(error.stack || error);
  process.exit(1);
});
