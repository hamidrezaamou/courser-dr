import { readdirSync, readFileSync, writeFileSync, renameSync, existsSync, unlinkSync } from 'fs';
import { join } from 'path';

const buildDir = join(process.cwd(), 'public', 'build');
const assetsDir = join(buildDir, 'assets');
const manifestPath = join(buildDir, 'manifest.json');

const files = readdirSync(assetsDir);
const css = files.find((f) => f.endsWith('.css'));
const js = files.find((f) => f.endsWith('.js') && f.startsWith('app'));

if (!css || !js) {
    console.error('stable-build: could not find css/js in public/build/assets');
    process.exit(1);
}

const targetCss = join(assetsDir, 'app.css');
const targetJs = join(assetsDir, 'app.js');
const srcCss = join(assetsDir, css);
const srcJs = join(assetsDir, js);

if (srcCss !== targetCss) {
    if (existsSync(targetCss)) unlinkSync(targetCss);
    renameSync(srcCss, targetCss);
}
if (srcJs !== targetJs) {
    if (existsSync(targetJs)) unlinkSync(targetJs);
    renameSync(srcJs, targetJs);
}

// Remove leftover hashed assets
for (const f of readdirSync(assetsDir)) {
    if (f !== 'app.css' && f !== 'app.js') {
        unlinkSync(join(assetsDir, f));
    }
}

const manifest = {
    'resources/css/app.css': {
        file: 'assets/app.css',
        src: 'resources/css/app.css',
        isEntry: true,
        name: 'app',
        names: ['app.css'],
    },
    'resources/js/app.js': {
        file: 'assets/app.js',
        name: 'app',
        src: 'resources/js/app.js',
        isEntry: true,
    },
};

writeFileSync(manifestPath, JSON.stringify(manifest, null, 2) + '\n', 'utf8');
console.log('stable-build: public/build/assets/app.css + app.js ready');
