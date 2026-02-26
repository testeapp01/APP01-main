const { test, expect } = require('@playwright/test');

test('login and show dashboard', async ({ page }) => {
  await page.goto('http://localhost:5173');

  // ensure we are at login
  await page.waitForSelector('input[placeholder="Email"]');
  await page.fill('input[placeholder="Email"]', 'admin@example.com');
  await page.fill('input[placeholder="Senha"]', 'secret');
  await page.click('text=Entrar');

  // after login app redirects to hash '#/' — wait for header text
  await page.waitForSelector('text=Hortifrut', { timeout: 5000 });
  const visible = await page.isVisible('text=Hortifrut');
  expect(visible).toBeTruthy();
});
