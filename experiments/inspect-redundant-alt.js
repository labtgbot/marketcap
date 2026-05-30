'use strict';
const {spawn} = require('child_process');
const path = require('path');
const fs = require('fs');
const {chromium} = require('playwright');
const {installRoutes} = require('../tests/lib/browser-fixtures');
const root = path.resolve(__dirname, '..');
const port = '8893';
const baseURL = `http://127.0.0.1:${port}`;
(async () => {
    const server = spawn('php', ['-S', `127.0.0.1:${port}`, 'dev/php/router.php'], {
        cwd: root,
        env: {...process.env, TONBANKCARD_PROFILE: 'local', TONBANKCARD_BASE_URL: `${baseURL}/`, TONBANKCARD_LOCAL_BASE_URL: `${baseURL}/`, TONBANKCARD_CDN: 'false', TONBANKCARD_FEATURE_CHANGENOW: 'true', TONBANKCARD_FEATURE_TON_CONNECT: 'true', CHANGENOW_LINK_ID: 'f300d9f2b6f88e'},
        stdio: ['ignore', 'ignore', 'ignore'],
    });
    await new Promise(r => setTimeout(r, 2500));
    const browser = await chromium.launch();
    const context = await browser.newContext({viewport:{width:1280,height:900}});
    await installRoutes(context, {}, baseURL);
    const page = await context.newPage();
    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});
    await page.waitForTimeout(2500);
    const imgs = await page.evaluate(() => Array.from(document.querySelectorAll('img')).map(i => ({
        alt: i.getAttribute('alt'),
        src: (i.getAttribute('src')||'').slice(0,60),
        cls: i.className,
        parentText: (i.closest('a,button')||i.parentElement||{}).textContent?.trim().slice(0,40),
        visible: i.offsetParent !== null,
    })));
    console.log(JSON.stringify(imgs, null, 1));
    await browser.close();
    server.kill();
})();
