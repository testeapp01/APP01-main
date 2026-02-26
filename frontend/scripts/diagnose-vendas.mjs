import { chromium } from 'playwright';
import fs from 'fs';

(async () => {
  const url = (process.env.TARGET_URL || 'http://localhost:5186').replace(/\/$/, '') + '/vendas';
  const outScreenshot = 'test-results/diagnose-vendas.png';
  const outLog = 'test-results/diagnose-vendas.txt';

  const logs = [];
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1366, height: 900 } });

  page.on('console', msg => {
    logs.push({ type: 'console', text: msg.text(), location: msg.location() });
  });
  page.on('pageerror', err => {
    logs.push({ type: 'pageerror', text: err.message, stack: err.stack });
  });
  page.on('requestfailed', req => {
    logs.push({ type: 'requestfailed', url: req.url(), failure: req.failure() && req.failure().errorText });
  });

  try {
    logs.push({ type: 'info', text: 'Navigating to ' + url });
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await page.screenshot({ path: outScreenshot, fullPage: true });
    logs.push({ type: 'info', text: 'Saved screenshot to ' + outScreenshot });
  } catch (err) {
    logs.push({ type: 'error', text: err.message, stack: err.stack });
  } finally {
    await browser.close();
    fs.writeFileSync(outLog, JSON.stringify(logs, null, 2), 'utf8');
    console.log('Diagnostics saved:', outScreenshot, outLog);
  }
})();
