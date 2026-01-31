module.exports = {
  testDir: "tests/e2e",
  timeout: 120000,
  use: {
    headless: true,
    locale: 'tr-TR',
    viewport: { width: 1280, height: 720 },
    screenshot: "only-on-failure",
    video: "retain-on-failure",
    trace: "retain-on-failure",
  },
  reporter: [
    ['list'],
    ['html', { open: 'never' }]
  ],
};
