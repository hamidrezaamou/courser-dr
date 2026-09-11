import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Loads every rendered page in dark mode and reports elements that still paint a
 * light background — the signature of a hardcoded colour that missed dark mode.
 */

const dir = path.resolve('storage/app/ui-preview');
const files = fs.readdirSync(dir).filter((f) => f.startsWith('page-') && f.endsWith('.html'));

const browser = await chromium.launch({ channel: 'chrome' });
const findings = [];

for (const file of files) {
    const name = file.replace(/^page-|\.html$/g, '');
    const page = await browser.newPage({ viewport: { width: 1280, height: 1000 } });
    // Seed the preference the app itself reads, so its bootstrap can't undo us.
    await page.addInitScript(() => {
        try {
            localStorage.setItem('theme', 'dark');
        } catch (e) {}
        // Runs before the document exists, so apply the class once it does.
        document.addEventListener('DOMContentLoaded', () => {
            document.documentElement.classList.add('dark');
        });
    });
    await page.goto(pathToFileURL(path.join(dir, file)).href);
    await page.waitForTimeout(400);

    const hits = await page.evaluate(() => {
        const luminance = (rgb) => {
            const m = rgb.match(/[\d.]+/g);
            if (!m) return null;
            const [r, g, b, a = 1] = m.map(Number);
            if (a < 0.5) return null;
            return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
        };

        const out = [];
        for (const el of document.querySelectorAll('body *')) {
            if (!el.offsetParent && el.tagName !== 'BODY') continue;
            const cs = getComputedStyle(el);
            const bg = luminance(cs.backgroundColor);
            if (bg === null || bg < 0.75) continue;

            const fg = luminance(cs.color);
            out.push({
                sel: el.tagName.toLowerCase() + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : ''),
                bg: cs.backgroundColor,
                fg: cs.color,
                // Light-on-light is unreadable; light-on-dark is merely off-theme.
                severity: fg !== null && fg > 0.5 ? 'UNREADABLE' : 'light surface',
            });
        }
        return out;
    });

    const seen = new Set();
    for (const h of hits) {
        const key = name + '|' + h.sel;
        if (seen.has(key)) continue;
        seen.add(key);
        findings.push(`[${h.severity}] ${name}  ${h.sel}\n    bg ${h.bg}  fg ${h.fg}`);
    }

    await page.close();
}

await browser.close();
console.log(findings.length ? findings.join('\n') : 'no light-on-light elements in dark mode');
