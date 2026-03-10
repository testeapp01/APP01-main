const { test, expect } = require('@playwright/test');

test('login and show dashboard', async ({ page }) => {
  await page.goto('http://localhost:5173/#/login');

  const emailField = page.locator('input[placeholder="Email"]');
  const loginVisible = await emailField.isVisible({ timeout: 5000 }).catch(() => false);

  if (loginVisible) {
    await emailField.fill('admin@example.com');
    await page.fill('input[placeholder="Senha"]', 'secret');
    await page.click('button:has-text("Entrar")');
  }

  await page.goto('http://localhost:5173/#/');
  await page.waitForSelector('text=Painel Executivo', { timeout: 10000 });
  await expect(page.locator('text=Painel Executivo')).toBeVisible();
});
