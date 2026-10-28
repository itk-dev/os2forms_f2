// https://playwright.dev/docs/auth#basic-shared-account-in-all-tests
import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authFile = path.join(__dirname, '../playwright/.auth/user.json');

setup('authenticate', async ({ page }) => {
  // Perform authentication steps. Replace these actions with your own.
  await page.goto('/user/login');
  await page.locator('[name="name"]').fill('admin');
  await page.locator('[name="pass"]').fill('password');
  await page.locator('[name="pass"]').press('Enter');

  await page.goto('/admin');
  // End of authentication steps.

  await page.context().storageState({ path: authFile });
});
