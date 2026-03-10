import { chromium } from 'playwright';

(async () => {
  const base = process.env.TARGET_URL || 'http://localhost:5186';
  const paths = ['/', '/vendas', '/clientes-motoristas', '/motoristas'];
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1366, height: 900 } });
  try {
    for (const p of paths) {
      const url = base.replace(/\/$/, '') + p;
      const name = p === '/' ? 'home' : p.replace(/\//g, '_').replace(/^_/, '');
      const out = `test-results/screenshot-${name}.png`;
      console.log('Capturing', url);
      try {
        await page.goto(url, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        await page.screenshot({ path: out, fullPage: true });
        console.log('Saved', out);
      } catch (e) {
        console.error('Failed to capture', url, e.message || e);
      }
    }
  } finally {
    await browser.close();
  }
})();
