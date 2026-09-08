import { chromium } from '@playwright/test';
const OUT = '/tmp/claude-501/-Users-serra-Codes-racines/179517c2-2f26-4d87-a55c-20de2cd65e04/scratchpad';
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1100 }, locale: 'fr-FR' });
const page = await context.newPage();
for (const w of [360, 390, 768, 1024, 1280, 1440, 1680]) {
    await page.setViewportSize({ width: w, height: 1000 });
    await page.goto('http://localhost:8001/', { waitUntil: 'networkidle' });
    await page.waitForTimeout(250);
    const r = await page.evaluate(() => ({
        s: document.documentElement.scrollWidth,
        c: document.documentElement.clientWidth,
        lignes: [...document.querySelectorAll('#comment-ca-marche h3')].map((n) => Math.round(n.getBoundingClientRect().height / parseFloat(getComputedStyle(n).lineHeight))),
    }));
    console.log(w, JSON.stringify(r));
}
await page.setViewportSize({ width: 1440, height: 1100 });
await page.reload({ waitUntil: 'networkidle' });
await page.waitForTimeout(400);
await page.locator('#comment-ca-marche').screenshot({ path: `${OUT}/how-final2.png` });
await browser.close();
