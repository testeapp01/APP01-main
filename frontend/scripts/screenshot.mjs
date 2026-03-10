import { chromium } from 'playwright';

(async () => {
  const url = process.env.TARGET_URL || 'http://localhost:5186/';
  const out = 'test-results/screenshot.png';

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1366, height: 900 } });
  try {
    console.log('Navigating to', url);
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await page.screenshot({ path: out, fullPage: true });
    console.log('Saved screenshot to', out);
  } catch (err) {
    console.error('Screenshot failed:', err);
    process.exitCode = 2;
  } finally {
    await browser.close();
  }
})();
