const { test, expect } = require("@playwright/test");

const ROLE = process.env.ROLE || "store_manager";
const BASE = process.env.BASE || "https://talentqx.com";
const URL = `${BASE}/interview/${ROLE}`;

test(`Interview flow works in browser for ${ROLE}`, async ({ page }) => {
  // Daha stabil olsun diye:
  page.setDefaultTimeout(60_000);

  // Network isteklerini logla
  page.on('request', req => {
    if (req.url().includes('/api/')) {
      console.log('API Request:', req.method(), req.url());
    }
  });
  page.on('response', res => {
    if (res.url().includes('/api/')) {
      console.log('API Response:', res.status(), res.url());
    }
  });

  // Console log'ları yakala (debugging için)
  page.on('console', msg => console.log('BROWSER:', msg.type(), msg.text()));
  page.on('pageerror', err => console.log('PAGE ERROR:', err.message));

  // 1) Sayfa açılır
  console.log(`Navigating to ${URL}...`);
  await page.goto(URL, { waitUntil: "networkidle", timeout: 30000 });
  console.log('Page loaded');

  // Hoşgeldin / Online Mülakat veya Online Interview yazısı (h1)
  await expect(page.getByRole('heading', { name: /online mülakat|online interview/i })).toBeVisible();
  console.log('Welcome heading visible');

  // 2) Gizlilik checkbox
  const checkbox = page.locator('input[type="checkbox"]').first();
  await checkbox.check();
  await expect(checkbox).toBeChecked();
  console.log('Consent checkbox checked');

  // 3) Mülakata Başla - butona tıkla
  const startButton = page.getByRole("button", { name: /mülakatı başlat|mülakata başla|start interview/i });
  await expect(startButton).toBeEnabled({ timeout: 5000 });
  console.log('Start button enabled');
  await startButton.click();
  console.log('Start button clicked');

  // Bekle - API çağrıları için zaman ver
  await page.waitForTimeout(3000);

  // Debug screenshot
  await page.screenshot({ path: 'after-start-click.png' });
  console.log('Screenshot taken: after-start-click.png');

  // Sayfadaki tüm HTML'i al
  const bodyHTML = await page.locator('body').innerHTML();
  console.log('Body HTML length:', bodyHTML.length);
  console.log('Body HTML (first 500 chars):', bodyHTML.substring(0, 500));

  // Sorular yüklenene kadar bekle (textarea görünene kadar)
  await page.waitForSelector('textarea', { timeout: 30000 });
  console.log('Textarea found - questions loaded');

  // 4) 8 soru akışı: her soruya 2-3 cümle cevap
  for (let i = 1; i <= 8; i++) {
    console.log(`Answering question ${i}/8...`);

    // textarea
    const answerBox = page.locator("textarea").first();
    await expect(answerBox).toBeVisible({ timeout: 10000 });

    await answerBox.fill(
      `Bu soruya yanıtım: ${i}. Önce durumu netleştiririm, sonra sorumluluk alır ve adım adım çözüm uygularım. Ekiple ve müşteriyle iletişimi açık tutarım.`
    );

    // Son soru değilse Next/Sonraki
    if (i < 8) {
      // Buton metnine göre bul
      const nextBtn = page.locator('button').filter({ hasText: /^(Sonraki|Next)$/i });
      await expect(nextBtn).toBeVisible();
      await nextBtn.click();
      // Cevap kaydedilmesini bekle
      await page.waitForTimeout(2000);
      console.log(`Question ${i} submitted`);
    }
  }

  // 5) Tamamla (son soruda)
  console.log('Completing interview...');
  const completeBtn = page.locator('button').filter({ hasText: /Tamamla|Bitir|Complete|Finish/i });
  await expect(completeBtn).toBeVisible();
  await completeBtn.click();

  // 6) Tamamlandı ekranı
  await expect(page.getByText(/mülakat tamamlandı|interview completed/i)).toBeVisible({ timeout: 20000 });
  console.log('Interview completed successfully!');
});
