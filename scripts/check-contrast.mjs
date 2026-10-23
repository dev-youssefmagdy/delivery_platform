#!/usr/bin/env node
/*
| WCAG 2.x contrast check for the design tokens (P6.3). Not a test suite; exits 1 when a listed pair fails so CI can run it later.
|
|   node scripts/check-contrast.mjs            (from the repository root; needs backend/node_modules for the Tailwind palette)
|
| Pairs are the combinations the UI really uses: admin buttons, badges, alerts, body and muted text, the sidebar, links and focus
| ring, chart series against the chart surface in the light and dark theme, the website brand, and the Flutter AppColors on white and
| on their own 12% tint (StatusChip). Text needs 4.5:1 (3:1 for large or bold 18px+), non-text UI parts need 3:1.
*/
import { readFileSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const backend = join(root, 'backend');

// ---------- colour parsing ----------
const clamp = (v) => Math.min(1, Math.max(0, v));
const srgbEncode = (c) => (c <= 0.0031308 ? 12.92 * c : 1.055 * c ** (1 / 2.4) - 0.055);

function oklchToRgb(l, c, hDeg) {
    const h = (hDeg * Math.PI) / 180;
    const a = c * Math.cos(h);
    const b = c * Math.sin(h);
    const l_ = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
    const m_ = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
    const s_ = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;
    const r = 4.0767416621 * l_ - 3.3077115913 * m_ + 0.2309699292 * s_;
    const g = -1.2684380046 * l_ + 2.6097574011 * m_ - 0.3413193965 * s_;
    const bl = -0.0041960863 * l_ - 0.7034186147 * m_ + 1.707614701 * s_;

    return [r, g, bl].map((v) => clamp(srgbEncode(clamp(v))));
}

function parse(value) {
    const v = value.trim();
    let m;
    if ((m = v.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i))) {
        let h = m[1];
        if (h.length === 3) h = [...h].map((x) => x + x).join('');

        return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16) / 255);
    }
    if ((m = v.match(/^oklch\(\s*([\d.]+)(%?)\s+([\d.]+)\s+([\d.]+)\s*\)$/))) {
        return oklchToRgb(m[2] ? parseFloat(m[1]) / 100 : parseFloat(m[1]), parseFloat(m[3]), parseFloat(m[4]));
    }
    throw new Error(`Cannot parse colour: ${value}`);
}

const lin = (c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
const luminance = ([r, g, b]) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
const ratio = (a, b) => {
    const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (hi + 0.05) / (lo + 0.05);
};
const mix = (fg, bg, alpha) => fg.map((c, i) => c * alpha + bg[i] * (1 - alpha));

// ---------- palettes ----------
const palette = {};
const themeCss = join(backend, 'node_modules/tailwindcss/theme.css');
if (!existsSync(themeCss)) {
    console.error('backend/node_modules/tailwindcss missing: run npm install in backend/ first');
    process.exit(2);
}
for (const [, name, value] of readFileSync(themeCss, 'utf8').matchAll(/--color-([a-z]+-\d+):\s*([^;]+);/g)) {
    palette[name] = value;
}
palette.white = '#ffffff';
palette.black = '#000000';

const adminCss = readFileSync(join(backend, 'resources/css/admin.css'), 'utf8');
for (const [, name, value] of adminCss.matchAll(/--color-(brand-\d+):\s*([^;]+);/g)) {
    palette[name] = value;
}
const websiteCss = readFileSync(join(backend, 'resources/css/website.css'), 'utf8');
const site = {};
for (const [, name, value] of websiteCss.matchAll(/--color-((?:brand|accent)-\d+):\s*([^;]+);/g)) {
    site[name] = value;
}

const viz = (block) => {
    const out = {};
    for (const [, k, v] of block.matchAll(/--(viz-[a-z0-9-]+):\s*(#[0-9a-f]{3,6})/gi)) out[k] = v;

    return out;
};
const vizLight = viz(adminCss.slice(adminCss.indexOf('.admin-chart'), adminCss.indexOf(':root.dark .admin-chart')));
const vizDark = viz(adminCss.slice(adminCss.indexOf(':root.dark .admin-chart')));

const c = (name) => parse(palette[name] ?? name);
const s = (name) => parse(site[name] ?? name);

// ---------- pairs: [label, fg, bg, minimum] ----------
const T = 4.5;
const N = 3;
const pairs = [];
const add = (label, fg, bg, min = T) => pairs.push({ label, fg, bg, min });

// Documented waivers (see docs/L10N_A11Y_REVIEW.md): the pair is reported as WAIVED, never fails the run.
const waivers = [
    [/^chart light: series [345] /, 'chart marks are also identified by the HTML legend, the tooltip and the data table; the validated palette order is kept'],
    [/^flutter accent text on white/, 'accent is used for decorative icons next to text only (stars, at-cost marker), never as text colour'],
    [/^flutter accent StatusChip/, 'accent is not a ChipTone'],
];

// Admin
add('admin btn-primary (white on brand-600)', c('white'), c('brand-600'));
add('admin btn-primary hover (white on brand-700)', c('white'), c('brand-700'));
add('admin btn-danger (white on rose-600)', c('white'), c('rose-600'));
add('admin btn-success (white on emerald-700)', c('white'), c('emerald-700'));
add('admin btn-warning (white on amber-700)', c('white'), c('amber-700'));
add('admin btn-secondary (slate-700 on white)', c('slate-700'), c('white'));
add('admin body (slate-800 on slate-50)', c('slate-800'), c('slate-50'));
add('admin muted text (slate-500 on white)', c('slate-500'), c('white'));
add('admin muted text (slate-500 on slate-50)', c('slate-500'), c('slate-50'));
add('admin link (brand-700 on white)', c('brand-700'), c('white'));
add('admin link (brand-700 on brand-50)', c('brand-700'), c('brand-50'));
add('admin sidebar text (slate-200 on brand-950)', c('slate-200'), c('brand-950'));
add('admin sidebar muted (slate-300 on brand-950)', c('slate-300'), c('brand-950'));
for (const [n, fg, bg] of [
    ['neutral', 'slate-700', 'slate-50'],
    ['info', 'sky-700', 'sky-50'],
    ['success', 'emerald-700', 'emerald-50'],
    ['warning', 'amber-700', 'amber-50'],
    ['danger', 'rose-700', 'rose-50'],
    ['primary', 'brand-700', 'brand-50'],
]) {
    add(`admin badge/alert ${n} (${fg} on ${bg})`, c(fg), c(bg));
}
add('admin focus ring (brand-500 on white, non-text)', c('brand-500'), c('white'), N);
add('admin form control border (--color-field on white, non-text)', parse(adminCss.match(/--color-field:\s*(#[0-9a-f]{6})/i)[1]), c('white'), N);
add('admin placeholder (slate-500 on white)', c('slate-500'), c('white'));
add('site form control border (--color-field on white, non-text)', parse(websiteCss.match(/--color-field:\s*(#[0-9a-f]{6})/i)[1]), c('white'), N);
add('admin icon-btn (slate-500 on white)', c('slate-500'), c('white'));

// Charts, both themes: series marks against the surface (3:1), secondary text (4.5:1)
for (const [theme, set] of [['light', vizLight], ['dark', vizDark]]) {
    const surface = parse(set['viz-surface']);
    add(`chart ${theme}: secondary text on surface`, parse(set['viz-text-secondary']), surface);
    for (let i = 1; i <= 8; i++) {
        add(`chart ${theme}: series ${i} on surface (non-text)`, parse(set[`viz-${i}`]), surface, N);
    }
}

// Website
add('site link/brand text (brand-700 on white)', s('brand-700'), c('white'));
add('site white on brand-700 button', c('white'), s('brand-700'));
add('site white on brand-600 button', c('white'), s('brand-600'));
add('site brand-800 on brand-50', s('brand-800'), s('brand-50'));
add('site body (slate-700 on white)', c('slate-700'), c('white'));
add('site muted (slate-500 on white)', c('slate-500'), c('white'));
add('site slate-900 on accent-500', c('slate-900'), s('accent-500'));
add('site footer text (slate-300 on slate-900)', c('slate-300'), c('slate-900'));
add('site footer text (slate-200 on slate-900)', c('slate-200'), c('slate-900'));

// Flutter AppColors (platform_core/lib/src/ui/tokens.dart)
const tokens = readFileSync(join(root, 'packages/platform_core/lib/src/ui/tokens.dart'), 'utf8');
const flutter = {};
for (const [, name, hex] of tokens.matchAll(/static const (\w+) = Color\(0xFF([0-9A-Fa-f]{6})\);/g)) {
    flutter[name] = parse(`#${hex}`);
}
const white = c('white');
for (const [name, rgb] of Object.entries(flutter)) {
    add(`flutter ${name} text on white`, rgb, white);
    // StatusChip darkens the tone by 25% for its text (components.dart), so test that colour on the tint.
    add(`flutter ${name} StatusChip text (darkened 25%) on its 12% tint`, mix([0, 0, 0], rgb, 0.25), mix(rgb, white, 0.12));
}
if (flutter.brand) add('flutter white on brand (filled button)', white, flutter.brand);
if (flutter.danger) add('flutter white on danger', white, flutter.danger);

// ---------- report ----------
let failures = 0;
for (const { label, fg, bg, min } of pairs) {
    const r = ratio(fg, bg);
    const ok = r + 1e-9 >= min;
    const waiver = ok ? null : waivers.find(([re]) => re.test(label));
    if (!ok && !waiver) failures++;
    console.log(`${ok ? 'ok  ' : waiver ? 'WAIVED' : 'FAIL'} ${r.toFixed(2).padStart(5)} (min ${min})  ${label}${waiver ? `  [${waiver[1]}]` : ''}`);
}
console.log(`\n${pairs.length} pairs, ${failures} failing`);
process.exit(failures === 0 ? 0 : 1);
