import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import path from 'node:path';

const dir = path.resolve('storage/app/ui-preview');
const html = path.join(dir, 'page-workspace-media.html');
const out = path.join(dir, 'shot-workspace-media-light.png');
const outWb = path.join(dir, 'shot-workspace-whiteboard-tab-light.png');
const outShell = path.join(dir, 'shot-workspace-shell-light.png');

const browser = await chromium.launch({ channel: 'chrome' });
const page = await browser.newPage({
    viewport: { width: 720, height: 820 },
    deviceScaleFactor: 1.5,
});

await page.addInitScript(() => {
    try { localStorage.setItem('theme', 'light'); } catch (e) {}
});

await page.goto(pathToFileURL(html).href, { waitUntil: 'domcontentloaded' });
await page.waitForTimeout(800);

await page.evaluate(() => {
    // Hide overlays that steal the preview
    document.querySelectorAll('.ans-overlay, #answer-panel-root, .row-toolbox-overlay, .confirm-modal, .pg-modal').forEach((el) => {
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
    });

    document.querySelectorAll('[x-cloak]').forEach((el) => el.removeAttribute('x-cloak'));
    document.querySelectorAll('.tg-drawer').forEach((el) => {
        el.style.display = 'none';
    });

    const media = [...document.querySelectorAll('.tg-drawer')].find((el) => el.querySelector('.tg-media-tabs'));
    if (media) {
        media.style.display = 'flex';
        media.style.visibility = 'visible';
        media.style.opacity = '1';
        media.style.zIndex = '50';
    }

    document.querySelectorAll('[x-show]').forEach((el) => {
        const expr = el.getAttribute('x-show') || '';
        if (expr.includes("mediaTab === 'photos'")) {
            el.style.display = '';
            el.removeAttribute('x-cloak');
        }
        if (expr.includes("mediaTab === 'drawings'") || expr.includes("mediaTab === 'exams'") || expr.includes("mediaTab === 'rx'")) {
            el.style.display = 'none';
        }
    });
    document.querySelectorAll('.tg-media-tab').forEach((btn) => {
        btn.classList.toggle('is-active', (btn.textContent || '').includes('عکس'));
    });
});

await page.waitForTimeout(250);
await page.screenshot({ path: out, fullPage: false });
console.log('wrote', path.basename(out));

await page.evaluate(() => {
    document.querySelectorAll('[x-show]').forEach((el) => {
        const expr = el.getAttribute('x-show') || '';
        if (expr.includes("mediaTab === 'drawings'")) {
            el.style.display = '';
            el.removeAttribute('x-cloak');
        }
        if (expr.includes("mediaTab === 'photos'") || expr.includes("mediaTab === 'exams'") || expr.includes("mediaTab === 'rx'")) {
            el.style.display = 'none';
        }
    });
    document.querySelectorAll('.tg-media-tab').forEach((btn) => {
        btn.classList.toggle('is-active', (btn.textContent || '').includes('وایت'));
    });
});
await page.waitForTimeout(200);
await page.screenshot({ path: outWb, fullPage: false });
console.log('wrote', path.basename(outWb));

// Composite look: workspace shell chrome around media panel
await page.setViewportSize({ width: 860, height: 900 });
await page.evaluate(() => {
    document.body.style.background = 'rgba(15, 23, 42, 0.45)';
    const media = [...document.querySelectorAll('.tg-drawer')].find((el) => el.querySelector('.tg-media-tabs'));
    if (!media) return;
    media.style.position = 'fixed';
    media.style.inset = 'auto';
    media.style.top = '72px';
    media.style.left = '50%';
    media.style.transform = 'translateX(-50%)';
    media.style.width = 'min(42rem, 92vw)';
    media.style.height = 'min(78dvh, 700px)';
    media.style.background = 'transparent';
    media.style.alignItems = 'stretch';
    media.style.justifyContent = 'center';

    const panel = media.querySelector('.tg-drawer__panel');
    if (panel) {
        panel.style.borderRadius = '0 0 1rem 1rem';
        panel.style.height = '100%';
        panel.style.maxHeight = '100%';
        panel.style.border = '1px solid var(--line)';
        panel.style.boxShadow = 'var(--shadow-lg)';
    }

    let bar = document.getElementById('pw-preview-bar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'pw-preview-bar';
        bar.style.cssText = 'position:fixed;top:24px;left:50%;transform:translateX(-50%);width:min(42rem,92vw);display:flex;align-items:center;justify-content:space-between;gap:0.75rem;padding:0.7rem 0.9rem;border:1px solid var(--line);border-bottom:0;border-radius:1rem 1rem 0 0;background:color-mix(in srgb, var(--brand-soft) 55%, var(--panel));z-index:60;font-family:Vazirmatn,sans-serif;';
        bar.innerHTML = '<strong style="margin:0;font-size:0.92rem;font-weight:800;color:var(--ink)">پرونده / گالری</strong><span style="display:inline-grid;place-items:center;width:2rem;height:2rem;border-radius:999px;background:var(--panel);color:var(--ink);font-size:1rem">×</span>';
        document.body.appendChild(bar);
    }
});
await page.waitForTimeout(200);
await page.screenshot({ path: outShell, fullPage: false });
console.log('wrote', path.basename(outShell));

await browser.close();
