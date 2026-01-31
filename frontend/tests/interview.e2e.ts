import { chromium } from 'playwright';

const BASE_URL = 'https://talentqx.com';

async function testInterviewFlow() {
  console.log('Starting Interview E2E Test...\n');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    locale: 'tr-TR',
    viewport: { width: 1280, height: 720 },
  });
  const page = await context.newPage();

  try {
    // 1. Navigate to interview page
    console.log('1. Navigating to /interview/store_manager...');
    await page.goto(`${BASE_URL}/interview/store_manager`);
    await page.waitForLoadState('networkidle');

    // Check page loaded
    const title = await page.locator('h1').first().textContent();
    console.log(`   Page title: "${title}"`);

    // 2. Check welcome screen elements
    console.log('2. Checking welcome screen elements...');
    const startButton = page.locator('button:has-text("Mülakatı Başlat"), button:has-text("Mülakata Başla"), button:has-text("Start Interview")');
    const isStartButtonVisible = await startButton.isVisible();
    console.log(`   Start button visible: ${isStartButtonVisible}`);

    // 3. Check consent checkbox
    console.log('3. Checking consent checkbox...');
    const checkbox = page.locator('input[type="checkbox"]');
    const isCheckboxVisible = await checkbox.isVisible();
    console.log(`   Consent checkbox visible: ${isCheckboxVisible}`);

    // 4. Accept consent
    console.log('4. Accepting consent...');
    await checkbox.check();
    const isChecked = await checkbox.isChecked();
    console.log(`   Checkbox checked: ${isChecked}`);

    // 5. Click start interview
    console.log('5. Starting interview...');
    await startButton.click();

    // Wait for questions to load
    await page.waitForSelector('textarea', { timeout: 10000 });
    console.log('   Interview started - questions loaded!');

    // 6. Check question progress
    console.log('6. Checking question progress...');
    const progressText = await page.locator('text=/Soru|Question/').first().textContent();
    console.log(`   Progress: ${progressText}`);

    // 7. Get first question
    console.log('7. Getting first question...');
    const questionText = await page.locator('h2').first().textContent();
    console.log(`   Question: "${questionText?.substring(0, 60)}..."`);

    // 8. Answer first question
    console.log('8. Answering first question...');
    const textarea = page.locator('textarea');
    await textarea.fill('Bu test cevabıdır. Daha önce yönettiğim bir ekipte en zor kararım düşük performanslı bir çalışanı motive etmek oldu. Birebir görüşmeler yaparak gelişim planı hazırladım.');

    // 9. Click next
    console.log('9. Clicking next...');
    const nextButton = page.locator('button:has-text("Sonraki"), button:has-text("Next")');
    await nextButton.click();

    // Wait for next question
    await page.waitForTimeout(1500);

    // 10. Check progress updated
    console.log('10. Checking progress updated...');
    const newProgressText = await page.locator('text=/Soru|Question/').first().textContent();
    console.log(`    Progress: ${newProgressText}`);

    // 11. Answer remaining questions quickly
    console.log('11. Answering remaining questions (2-8)...');
    for (let i = 2; i <= 8; i++) {
      await textarea.fill(`Bu soru ${i} için test cevabıdır. Detaylı bir şekilde açıklama yaparak durumu değerlendiriyorum ve en uygun çözümü bulmaya çalışıyorum.`);

      if (i < 8) {
        await nextButton.click();
        await page.waitForTimeout(1000);
      } else {
        // Last question - click complete
        const completeButton = page.locator('button:has-text("Tamamla"), button:has-text("Complete"), button:has-text("Bitir")');
        await completeButton.click();
      }
      console.log(`    Question ${i} answered`);
    }

    // 12. Wait for completion screen
    console.log('12. Waiting for completion screen...');
    await page.waitForSelector('text=/Tamamlandı|Completed/', { timeout: 10000 });

    const completionTitle = await page.locator('h1').first().textContent();
    console.log(`    Completion title: "${completionTitle}"`);

    // Take screenshot
    await page.screenshot({ path: '/www/wwwroot/talentqx-src/frontend/tests/interview-completed.png' });
    console.log('    Screenshot saved: interview-completed.png');

    console.log('\n✅ E2E Test PASSED! Interview flow works correctly.');

  } catch (error) {
    console.error('\n❌ E2E Test FAILED:', error);

    // Take error screenshot
    await page.screenshot({ path: '/www/wwwroot/talentqx-src/frontend/tests/interview-error.png' });
    console.log('Error screenshot saved: interview-error.png');

    process.exit(1);
  } finally {
    await browser.close();
  }
}

testInterviewFlow();
