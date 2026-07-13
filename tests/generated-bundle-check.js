#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const UglifyJS = require('uglify-js');

const root = path.resolve(__dirname, '..');
const logDir = path.join(root, 'test-logs');
const logPath = path.join(logDir, 'generated-bundle-check.log');

fs.mkdirSync(logDir, {recursive: true});

const logLines = [];
const failures = [];

function log(message) {
    logLines.push(message);
}

function fail(message) {
    failures.push(message);
    log(`FAIL: ${message}`);
}

function readText(relativePath) {
    return fs.readFileSync(path.join(root, relativePath), 'utf8');
}

function sha256(value) {
    return crypto.createHash('sha256').update(value).digest('hex');
}

function finish() {
    fs.writeFileSync(logPath, `${logLines.join('\n')}\n`);

    if (failures.length) {
        console.error(`Generated bundle validation failed. See ${path.relative(root, logPath)} for details.`);
        process.exit(1);
    }

    console.log(`Generated bundle validation passed. Log: ${path.relative(root, logPath)}`);
}

try {
    log('Generated bundle validation started.');

    const sourceListPath = 'dev/js/source.json';
    const appPath = 'assets/js/app.js';
    const minPath = 'assets/js/app.min.js';

    const sourceList = JSON.parse(readText(sourceListPath));
    if (!Array.isArray(sourceList)) {
        fail(`${sourceListPath} must contain a JSON array.`);
        finish();
    }

    let expectedApp = '';
    for (const source of sourceList) {
        if (typeof source !== 'string' || source.trim() === '') {
            fail(`${sourceListPath} contains an invalid source entry.`);
            continue;
        }

        const sourcePath = path.join('dev/js/src', source);
        const absoluteSourcePath = path.join(root, sourcePath);
        if (!fs.existsSync(absoluteSourcePath)) {
            fail(`Missing source file listed in ${sourceListPath}: ${sourcePath}`);
            continue;
        }

        expectedApp += fs.readFileSync(absoluteSourcePath, 'utf8') + '\n';
    }

    if (!fs.existsSync(path.join(root, appPath))) {
        fail(`Missing generated bundle: ${appPath}`);
    } else {
        const actualApp = readText(appPath);
        log(`${appPath} sha256: ${sha256(actualApp)}`);
        log(`Expected ${appPath} sha256: ${sha256(expectedApp)}`);

        if (actualApp !== expectedApp) {
            const expectedPath = path.join(logDir, 'app.expected.js');
            fs.writeFileSync(expectedPath, expectedApp);
            fail(`${appPath} is out of date with ${sourceListPath}. Expected copy written to ${path.relative(root, expectedPath)}.`);
        }

        try {
            new Function(actualApp);
        } catch (err) {
            fail(`${appPath} has a JavaScript syntax error: ${err.message}`);
        }
    }

    if (!fs.existsSync(path.join(root, minPath))) {
        fail(`Missing generated minified bundle: ${minPath}`);
    } else {
        const minified = readText(minPath);
        const expectedMinified = UglifyJS.minify(expectedApp);
        log(`${minPath} sha256: ${sha256(minified)}`);
        if (minified.trim() === '') {
            fail(`${minPath} is empty.`);
        }
        if (expectedMinified.error) {
            fail(`Could not minify source bundle for comparison: ${expectedMinified.error.message}`);
        } else if (minified !== expectedMinified.code) {
            fail(`${minPath} is out of date with ${sourceListPath}.`);
        }
        try {
            new Function(minified);
        } catch (err) {
            fail(`${minPath} has a JavaScript syntax error: ${err.message}`);
        }
    }

    finish();
} catch (err) {
    fail(err.stack || err.message);
    finish();
}
