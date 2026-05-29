#!/usr/bin/env node
'use strict';

/*
 * Accessibility audit (issue #170).
 *
 * Runs axe-core (WCAG 2.1 A/AA + best-practice rules) through Playwright/Chromium
 * against the key public routes and fails the build on any blocking violation.
 *
 * "Blocking" means a `critical` or `serious` impact violation whose rule id is
 * NOT on the consciously-deferred allowlist below. The deferred rules are
 * tracked in docs/readiness-analysis-2026.md with a remediation plan; they are
 * reported here as warnings so regressions stay visible without breaking CI.
 *
 * The server runs under the `local` profile so the audit is deterministic and
 * makes no live CoinGecko request (it uses the bundled discovery universe and
 * the offline TON catalog), mirroring tests/browser-smoke.js.
 */

const fs = require('fs');
const path = require('path');
const {spawn} = require('child_process');
const {chromium} = require('playwright');

const axeSource = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');

const root = path.resolve(__dirname, '..');
const logDir = path.join(root, 'test-logs');
const reportLogPath = path.join(logDir, 'accessibility-check.log');
const reportJsonPath = path.join(logDir, 'accessibility-report.json');
const serverLogPath = path.join(logDir, 'accessibility-server.log');
const host = process.env.A11Y_HOST || '127.0.0.1';
const port = process.env.A11Y_PORT || '8894';
const baseURL = process.env.A11Y_BASE_URL || `http://${host}:${port}`;

// Key public routes the audit must cover.
const routes = [
    {path: '/', label: 'home'},
    {path: '/markets', label: 'markets list'},
    {path: '/coins/bitcoin', label: 'coin detail'},
    {path: '/exchanges', label: 'exchanges list'},
    {path: '/watchlist', label: 'watchlist'},
];

// Impacts that block the build unless the rule id is consciously deferred.
const blockingImpacts = new Set(['critical', 'serious']);

// Consciously deferred rules — see docs/readiness-analysis-2026.md. These are
// reported as warnings (never silently dropped) but do not fail the build.
//   - color-contrast: the brand primary (#1BB2DA, rendered as ~#0097be on the
//     app bar) yields a 3.4:1 ratio with white text vs the 4.5:1 AA target.
//     Darkening the brand colour is a design decision pending sign-off.
//   - link-in-text-block: inline links inside paragraph copy rely on colour;
//     queued behind the same design-token revision.
const deferredRules = new Set([
    'color-contrast',
    'link-in-text-block',
]);

fs.mkdirSync(logDir, {recursive: true});
fs.writeFileSync(reportLogPath, '');
fs.writeFileSync(serverLogPath, '');

const reportLog = fs.createWriteStream(reportLogPath, {flags: 'a'});
const serverLog = fs.createWriteStream(serverLogPath, {flags: 'a'});

let server;

function log(message) {
    reportLog.write(`${message}\n`);
    console.log(message);
}

function startServer() {
    log(`Starting PHP server at ${baseURL}`);
    server = spawn('php', ['-S', `${host}:${port}`, 'dev/php/router.php'], {
        cwd: root,
        env: {
            ...process.env,
            TONBANKCARD_PROFILE: 'local',
            TONBANKCARD_BASE_URL: `${baseURL}/`,
            TONBANKCARD_LOCAL_BASE_URL: `${baseURL}/`,
            TONBANKCARD_CDN: 'false',
        },
        stdio: ['ignore', 'pipe', 'pipe'],
    });

    server.stdout.pipe(serverLog);
    server.stderr.pipe(serverLog);
}

async function waitForServer() {
    const deadline = Date.now() + 15000;
    let lastError = null;

    while (Date.now() < deadline) {
        try {
            const response = await fetch(`${baseURL}/`);
            if (response.ok) {
                return;
            }
            lastError = new Error(`HTTP ${response.status}`);
        } catch (err) {
            lastError = err;
        }

        await new Promise(resolve => setTimeout(resolve, 250));
    }

    throw new Error(`PHP server did not become ready at ${baseURL}: ${lastError ? lastError.message : 'unknown error'}`);
}

async function stopServer() {
    if (!server || server.killed) {
        return;
    }

    server.kill('SIGTERM');
    await new Promise(resolve => {
        const timer = setTimeout(resolve, 2000);
        server.once('exit', () => {
            clearTimeout(timer);
            resolve();
        });
    });
}

async function auditRoute(page, route) {
    const response = await page.goto(`${baseURL}${route.path}`, {waitUntil: 'domcontentloaded'});
    if (!response || !response.ok()) {
        throw new Error(`${route.label} (${route.path}) returned HTTP ${response ? response.status() : 'no response'}`);
    }

    // Let Vue/Vuetify hydrate the route before scanning the rendered DOM.
    await page.waitForTimeout(1200);
    await page.addScriptTag({content: axeSource});

    const result = await page.evaluate(async () => {
        return window.axe.run(document, {
            resultTypes: ['violations'],
            runOnly: {
                type: 'tag',
                values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice'],
            },
        });
    });

    return result.violations.map(violation => ({
        id: violation.id,
        impact: violation.impact,
        help: violation.help,
        helpUrl: violation.helpUrl,
        nodes: violation.nodes.length,
        targets: violation.nodes.slice(0, 5).map(node => node.target.join(' ')),
    }));
}

async function run() {
    startServer();
    await waitForServer();

    const browser = await chromium.launch();
    const page = await browser.newPage();

    const report = [];
    const blocking = [];
    const deferred = [];

    try {
        for (const route of routes) {
            log(`\nAuditing ${route.label} (${route.path})`);
            const violations = await auditRoute(page, route);

            if (!violations.length) {
                log('  No accessibility violations detected.');
            }

            for (const violation of violations) {
                const tag = `[${violation.impact}] ${violation.id} (${violation.nodes} node${violation.nodes === 1 ? '' : 's'}) — ${violation.help}`;
                const entry = {route: route.path, ...violation};
                report.push(entry);

                if (blockingImpacts.has(violation.impact) && !deferredRules.has(violation.id)) {
                    blocking.push(entry);
                    log(`  BLOCKING ${tag}`);
                    violation.targets.forEach(target => log(`      → ${target}`));
                } else if (deferredRules.has(violation.id)) {
                    deferred.push(entry);
                    log(`  deferred ${tag}`);
                } else {
                    log(`  warning  ${tag}`);
                }
            }
        }
    } finally {
        await browser.close();
    }

    fs.writeFileSync(reportJsonPath, JSON.stringify({
        baseURL,
        routes: routes.map(route => route.path),
        deferredRules: [...deferredRules],
        violations: report,
    }, null, 2));

    log(`\nReport written to ${path.relative(root, reportJsonPath)}`);
    log(`Summary: ${report.length} total violation rule(s), ${blocking.length} blocking, ${deferred.length} consciously deferred.`);

    if (blocking.length) {
        throw new Error(`${blocking.length} blocking accessibility violation(s) detected. See ${path.relative(root, reportLogPath)}`);
    }

    log('Accessibility check passed (no blocking violations).');
}

run()
    .catch(err => {
        log(`Accessibility check failed: ${err.stack || err.message}`);
        process.exitCode = 1;
    })
    .finally(async () => {
        await stopServer();
        reportLog.end();
        serverLog.end();
    });
