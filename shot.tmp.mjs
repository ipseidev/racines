import { chromium } from '@playwright/test';
const OUT = '/tmp/claude-501/-Users-serra-Codes-racines/179517c2-2f26-4d87-a55c-20de2cd65e04/scratchpad';
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1200 }, locale: 'fr-FR' });
await context.addInitScript(() => localStorage.setItem('welcome-offer', JSON.stringify({ status: 'dismissed', at: Date.now() })));
const page = await context.newPage();
await page.goto('http://localhost:8001/', { waitUntil: 'networkidle' });
await page.locator('#le-livre').scrollIntoViewIfNeeded();
await page.waitForTimeout(1100);
console.log(JSON.stringify(await page.evaluate(() => {
    const s = document.getElementById('le-livre');
    return {
        chapeau: getComputedStyle(s.querySelector('h2 + p')).fontSize,
        titre_point: getComputedStyle(s.querySelector('dt')).fontSize,
        description: getComputedStyle(s.querySelector('dd')).fontSize,
    };
})));
await page.locator('#le-livre').locator('dl').screenshot({ path: `${OUT}/offer-dl.png` });
for (const w of [360, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width: w, height: 1000 });
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(250);
    console.log(w, JSON.stringify(await page.evaluate(() => ({ s: document.documentElement.scrollWidth, c: document.documentElement.clientWidth }))));
}
await browser.close();
