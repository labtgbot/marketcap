'use strict';
const {spawn} = require('child_process');
const path = require('path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');
const port = '8895';
const baseURL = `http://127.0.0.1:${port}`;

(async () => {
    const server = spawn('php', ['-S', `127.0.0.1:${port}`, 'dev/php/router.php'], {
        cwd: root,
        env: {...process.env, TONBANKCARD_PROFILE: 'local', TONBANKCARD_BASE_URL: `${baseURL}/`, TONBANKCARD_LOCAL_BASE_URL: `${baseURL}/`, TONBANKCARD_CDN: 'false'},
        stdio: ['ignore', 'inherit', 'inherit'],
    });
    await new Promise(r => setTimeout(r, 2500));
    const browser = await chromium.launch();
    const page = await browser.newPage();
    await page.goto(`${baseURL}/screener`, {waitUntil: 'domcontentloaded'});
    await page.waitForTimeout(2000);
    const html = await page.evaluate(() => {
        const el = document.querySelector('div[aria-owns]');
        if (!el) return 'NONE';
        // climb to the v-select root
        const root = el.closest('.v-select') || el;
        return root.outerHTML;
    });
    console.log(html.slice(0, 2000));
    await browser.close();
    server.kill();
})();
