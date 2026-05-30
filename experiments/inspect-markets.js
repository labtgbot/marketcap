'use strict';
const {spawn} = require('child_process');
const path = require('path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');
const port = '8896';
const baseURL = `http://127.0.0.1:${port}`;

(async () => {
    const server = spawn('php', ['-S', `127.0.0.1:${port}`, 'dev/php/router.php'], {
        cwd: root,
        env: {...process.env, TONBANKCARD_PROFILE: 'local', TONBANKCARD_BASE_URL: `${baseURL}/`, TONBANKCARD_LOCAL_BASE_URL: `${baseURL}/`, TONBANKCARD_CDN: 'false'},
        stdio: ['ignore', 'ignore', 'ignore'],
    });
    await new Promise(r => setTimeout(r, 2500));
    const browser = await chromium.launch();
    const page = await browser.newPage();
    await page.goto(`${baseURL}/markets`, {waitUntil: 'domcontentloaded'});
    for (const t of [1500, 3000, 5000]) {
        await page.waitForTimeout(t - (t === 1500 ? 0 : 0));
        const info = await page.evaluate(() => ({
            rows: document.querySelectorAll('tbody tr').length,
            watchBtns: document.querySelectorAll('.watchlist-icon-button').length,
            anyWatch: document.querySelectorAll('[class*="watchlist"]').length,
            tablePresent: !!document.querySelector('.v-data-table, table'),
        }));
        console.log(JSON.stringify(info));
    }
    await browser.close();
    server.kill();
})();
