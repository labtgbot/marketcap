'use strict';
const {spawn} = require('child_process');
const path = require('path');
const {chromium} = require('playwright');
const {installRoutes} = require('../tests/lib/browser-fixtures');
const root = path.resolve(__dirname, '..');
const port = '8897';
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
    await page.goto(`${baseURL}/coins/bitcoin`, {waitUntil: 'domcontentloaded'});
    await page.waitForTimeout(3000);
    const info = await page.evaluate(() => ({
        url: location.href,
        sliders: document.querySelectorAll('.v-slider').length,
        roleSliders: document.querySelectorAll('[role="slider"]').length,
        thumbs: document.querySelectorAll('.v-slider__thumb-container').length,
        h: document.querySelector('h1') ? document.querySelector('h1').textContent.trim() : null,
    }));
    console.log('AFTER 3s', JSON.stringify(info));
    await page.waitForTimeout(3000);
    const info2 = await page.evaluate(() => {
        const t = document.querySelector('.v-slider__thumb-container');
        const attrs = {};
        if (t) for (const a of t.attributes) attrs[a.name]=a.value;
        return {thumbs: document.querySelectorAll('.v-slider__thumb-container').length, attrs};
    });
    console.log('AFTER 6s', JSON.stringify(info2));
    await browser.close();
    server.kill();
})();
