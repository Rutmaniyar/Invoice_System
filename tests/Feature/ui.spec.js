const { test, expect } = require('@playwright/test');

test.describe('LedgerFlow installer UI', () => {
  test('renders premium installer shell without console errors', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', (message) => {
      if (message.type() === 'error') {
        consoleErrors.push(message.text());
      }
    });

    await page.goto('/install');
    await expect(page.getByRole('link', { name: 'Skip to main content' })).toBeAttached();

    if ((await page.title()).includes('Sign in')) {
      await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
      await expect(page.getByText('Invoices, quotes, payments, reminders, reports, and audit history')).toHaveCount(0);
      await expect(page.getByText('PHP 8.2 and MySQL')).toHaveCount(0);
    } else {
      await expect(page).toHaveTitle(/Install LedgerFlow/);
      await expect(page.getByRole('heading', { name: 'Install LedgerFlow' })).toBeVisible();
      await expect(page.getByText('Shared hosting installer')).toBeVisible();
      await expect(page.getByRole('button', { name: /Install application/ })).toBeVisible();

      const formIsInvalidBeforeRequiredFields = await page.locator('form').evaluate((form) => !form.checkValidity());
      expect(formIsInvalidBeforeRequiredFields).toBe(true);
    }

    expect(consoleErrors).toEqual([]);
  });

  test('loads built assets and accepts the privacy notice', async ({ page, request }) => {
    const css = await request.get('/assets/css/app.css');
    expect(css.ok()).toBe(true);
    const js = await request.get('/assets/js/app.js');
    expect(js.ok()).toBe(true);

    await page.goto('/install');
    const banner = page.getByRole('region', { name: 'Privacy notice' });
    await expect(banner).toBeVisible();
    await page.getByRole('button', { name: 'Accept' }).click();
    await expect(banner).toBeHidden();
  });

  test('does not overflow on the current viewport', async ({ page }) => {
    await page.goto('/install');
    const sizes = await page.evaluate(() => ({
      viewport: document.documentElement.clientWidth,
      scroll: document.documentElement.scrollWidth
    }));
    expect(sizes.scroll).toBeLessThanOrEqual(sizes.viewport + 2);
  });
});
