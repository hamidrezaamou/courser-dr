import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import fs from 'node:fs';
import path from 'node:path';

const dir = path.resolve('storage/app/ui-preview');
const only = process.argv.slice(2);
const files = fs.readdirSync(dir).filter((f) => f.startsWith('page-') && f.endsWith('.html'));
const targets = only.length ? files.filter((f) => only.some((o) => f.includes(o))) : files;

const browser = await chromium.launch({ channel: 'chrome' });
const errors = [];

for (const file of targets) {
    const name = file.replace(/^page-|\.html$/g, '');

    for (const theme of ['light', 'dark']) {
        const page = await browser.newPage({
            viewport: { width: 1280, height: 900 },
            deviceScaleFactor: 1.5,
        });
        page.on('pageerror', (e) => errors.push(`${name}/${theme}: ${e.message}`));

        // Seed the preference the app itself reads, so its bootstrap agrees with us.
        await page.addInitScript((t) => {
            try {
                localStorage.setItem('theme', t);
            } catch (e) {}
            // Runs before the document exists, so apply the class once it does.
            document.addEventListener('DOMContentLoaded', () => {
                document.documentElement.classList.toggle('dark', t === 'dark');
            });
        }, theme);
        await page.goto(pathToFileURL(path.join(dir, file)).href);
        await page.waitForTimeout(500);

        const out = path.join(dir, `shot-${name}-${theme}.png`);
        await page.screenshot({ path: out, fullPage: false });
        console.log('wrote', path.basename(out));
        await page.close();
    }
}

await browser.close();
console.log(errors.length ? '\npage errors:\n  ' + errors.join('\n  ') : '\nno page errors');
