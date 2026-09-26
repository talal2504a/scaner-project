const { chromium } = require('C:/Users/User/AppData/Roaming/npm/node_modules/playwright');
const fs = require('fs');

(async () => {
  fs.mkdirSync('shots', { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const base = 'http://localhost/scaner-prodect/project/root/';
  try {
    await page.goto(base, { waitUntil: 'networkidle', timeout: 20000 });
  } catch (e) { console.log('LOAD ERROR:', e.message); await browser.close(); return; }
  await page.waitForTimeout(1500);
  await page.screenshot({ path: 'shots/home.png', fullPage: true });
  console.log('DONE: screenshots/home.png');

  // Quick checks
  const stats = await page.$$eval('.stat-card .value', els => els.map(e => e.textContent.trim()));
  console.log('STAT CARDS:', JSON.stringify(stats));
  const hasLoading = (await page.content()).includes('Loading...');
  console.log('STILL LOADING:', hasLoading);
  const ctnCount = await page.$$eval('.ctn-head', els => els.length);
  console.log('ITEM ROWS:', ctnCount);
  await browser.close();
})();