import { chromium, type FullConfig } from '@playwright/test';

async function globalSetup(config: FullConfig) {
  const baseURL = config.projects[0].use.baseURL || 'https://www.dev.local';

  console.log('Waiting for Joomla to be ready...');

  const browser = await chromium.launch();
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  const maxRetries = 30;
  let retries = 0;

  while (retries < maxRetries) {
    try {
      const response = await page.goto(baseURL, { timeout: 5000 });
      if (response && response.ok()) {
        console.log('Joomla is ready!');
        break;
      }
    } catch {
      retries++;
      console.log(`Waiting for Joomla... (${retries}/${maxRetries})`);
      await page.waitForTimeout(2000);
    }
  }

  if (retries >= maxRetries) {
    throw new Error('Joomla did not become ready in time');
  }

  await browser.close();
}

export default globalSetup;
