// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/Feature',
  timeout: 30000,
  expect: {
    timeout: 5000
  },
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'storage/logs/playwright-report' }]],
  use: {
    baseURL: 'http://127.0.0.1:8090',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure'
  },
  webServer: {
    command: 'php -S 127.0.0.1:8090 -t public',
    url: 'http://127.0.0.1:8090/install',
    reuseExistingServer: true,
    timeout: 10000
  },
  projects: [
    {
      name: 'chromium-desktop',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 1000 } }
    },
    {
      name: 'chromium-mobile',
      use: { ...devices['Pixel 7'] }
    }
  ]
});
