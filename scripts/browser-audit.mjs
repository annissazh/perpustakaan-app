/**
 * Audit runtime browser via Chrome DevTools Protocol.
 *
 * Membuka tiap halaman, menangkap error JS / exception / request gagal,
 * lalu mencetak ringkasan.
 *
 * Jalankan: node scripts/browser-audit.mjs <baseUrl> <path1,path2,...>
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const baseUrl = process.argv[2] || 'http://127.0.0.1:8899';
const paths = (process.argv[3] || '/').split(',');

const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9222;

const profile = mkdtempSync(join(tmpdir(), 'perpus-audit-'));
const chrome = spawn(CHROME, [
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${profile}`,
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--window-size=1440,900',
    'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function waitForChrome() {
    for (let i = 0; i < 40; i++) {
        try {
            const res = await fetch(`http://127.0.0.1:${PORT}/json/version`);
            if (res.ok) return await res.json();
        } catch { /* belum siap */ }
        await sleep(250);
    }
    throw new Error('Chrome tidak siap');
}

class Cdp {
    constructor(ws) {
        this.ws = ws;
        this.id = 0;
        this.pending = new Map();
        this.events = [];

        ws.addEventListener('message', (event) => {
            const msg = JSON.parse(event.data);

            if (msg.id && this.pending.has(msg.id)) {
                const { resolve, reject } = this.pending.get(msg.id);
                this.pending.delete(msg.id);
                msg.error ? reject(new Error(JSON.stringify(msg.error))) : resolve(msg.result);
            } else if (msg.method) {
                this.events.push(msg);
            }
        });
    }

    send(method, params = {}, sessionId) {
        const id = ++this.id;
        const payload = { id, method, params };
        if (sessionId) payload.sessionId = sessionId;

        this.ws.send(JSON.stringify(payload));

        return new Promise((resolve, reject) => {
            this.pending.set(id, { resolve, reject });
            setTimeout(() => {
                if (this.pending.has(id)) {
                    this.pending.delete(id);
                    reject(new Error(`timeout: ${method}`));
                }
            }, 20000);
        });
    }
}


async function main() {
    await waitForChrome();

    /**
     * Ambil screenshot halaman untuk verifikasi visual.
     */
    async function capture(path, file) {
        await cdp.send('Page.navigate', { url: baseUrl + path }, sessionId);
        await sleep(2600);

        const { data } = await cdp.send('Page.captureScreenshot', {
            format: 'png',
            captureBeyondViewport: true,
        }, sessionId);

        writeFileSync(file, Buffer.from(data, 'base64'));
        console.log(`       screenshot -> ${file}`);
    }


    const targets = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
    const page = targets.find((t) => t.type === 'page');
    const ws = new WebSocket(page.webSocketDebuggerUrl);
    await new Promise((r) => ws.addEventListener('open', r, { once: true }));

    const cdp = new Cdp(ws);

    const { sessionId } = await cdp.send('Target.attachToTarget', { targetId: page.id, flatten: true });

    await cdp.send('Page.enable', {}, sessionId);
    await cdp.send('Runtime.enable', {}, sessionId);
    await cdp.send('Log.enable', {}, sessionId);
    await cdp.send('Network.enable', {}, sessionId);

    // Set cookie session bila diberikan (hasil login via curl).
    const cookie = process.argv[4];
    if (cookie) {
        const [name, value] = cookie.split('=');
        await cdp.send('Network.setCookie', {
            name,
            value,
            domain: '127.0.0.1',
            path: '/',
        }, sessionId);
    }

    let hasError = false;

    for (const path of paths) {
        const url = baseUrl + path;

        cdp.events.length = 0;

        await cdp.send('Page.navigate', { url }, sessionId);
        await sleep(2200);

        const problems = [];

        for (const ev of cdp.events) {
            if (ev.method === 'Runtime.exceptionThrown') {
                const d = ev.params.exceptionDetails;
                problems.push(`EXCEPTION: ${d.exception?.description || d.text}`);
            }

            if (ev.method === 'Runtime.consoleAPICalled' && ev.params.type === 'error') {
                problems.push(`CONSOLE.ERROR: ${ev.params.args.map((a) => a.value ?? a.description).join(' ')}`);
            }

            if (ev.method === 'Log.entryAdded' && ev.params.entry.level === 'error') {
                const e = ev.params.entry;
                if (e.url && e.url.includes('favicon')) continue;
                problems.push(`LOG: ${e.text} ${e.url ? '(' + e.url + ')' : ''}`);
            }

            if (ev.method === 'Network.loadingFailed' && !ev.params.errorText.includes('ERR_ABORTED')) {
                problems.push(`NETWORK FAIL: ${ev.params.errorText} ${ev.params.type}`);
            }
        }

        const probe = await cdp.send('Runtime.evaluate', {
            expression: `JSON.stringify({
                perpus: typeof window.Perpus,
                toast: typeof window.Perpus?.toast,
                confirm: typeof window.Perpus?.confirm,
                search: !!document.querySelector('#tableSearch'),
                empty: !!document.querySelector('#tableEmpty'),
                counter: document.querySelector('#tableCounter')?.textContent || null,
                jquery: !!window.jQuery,
                rows: document.querySelectorAll('#table1 tbody tr').length,
            })`,
            returnByValue: true,
        }, sessionId);

        const info = JSON.parse(probe.result.value);
        const status = problems.length === 0 ? 'OK  ' : 'FAIL';
        if (problems.length > 0) hasError = true;

        console.log(`\n[${status}] ${path}`);
        console.log(`       Perpus=${info.perpus} toast=${info.toast} confirm=${info.confirm} jQuery=${info.jquery}`);
        console.log(`       rows=${info.rows} search=${info.search} empty=${info.empty} counter="${info.counter}"`);

        problems.slice(0, 5).forEach((p) => console.log(`       > ${p}`));
    // Uji interaksi live search bila diminta.
    if (process.argv[5]) {
        await testSearch(paths[0], process.argv[5]);
    }

    // Uji render grafik bila diminta.
    if (process.argv[6]) {
        await testCharts(process.argv[6]);
    }

    // Ambil screenshot bila diminta.
    if (process.argv[7]) {
        await capture(process.argv[6] || paths[0], process.argv[7]);
    }


    }

    /**
     * Uji interaksi: ketik di kotak pencarian lalu periksa jumlah baris tersembunyi.
     */
    async function testSearch(path, term) {
        cdp.events.length = 0;

        await cdp.send('Page.navigate', { url: baseUrl + path }, sessionId);
        await sleep(2200);

        const result = await cdp.send('Runtime.evaluate', {
            expression: `(() => {
                const input = document.querySelector('#tableSearch');
                if (!input) return JSON.stringify({ error: 'tidak ada input pencarian' });

                const rows = Array.from(document.querySelectorAll('#table1 tbody tr'))
                    .filter(r => r.dataset.searchable !== 'false');

                const before = rows.length;

                input.value = ${JSON.stringify(term)};
                input.dispatchEvent(new Event('input', { bubbles: true }));

                const visible = rows.filter(r => !r.hidden).length;
                const counter = document.querySelector('#tableCounter')?.textContent || '';
                const emptyHidden = document.querySelector('#tableEmpty')?.hidden;

                return JSON.stringify({ before, visible, counter, emptyHidden });
            })()`,
            returnByValue: true,
        }, sessionId);

        const info = JSON.parse(result.result.value);
        const pass = info.visible > 0 && info.visible < info.before;

        console.log(`\n[${pass ? 'OK  ' : 'FAIL'}] SEARCH ${path} ("${term}")`);
        console.log(`       sebelum=${info.before} terlihat=${info.visible} counter="${info.counter}" emptyTersembunyi=${info.emptyHidden}`);

        if (!pass) hasError = true;
    }

    /**
     * Uji grafik Chart.js benar-benar ter-render (bukan canvas kosong).
     */
    async function testCharts(path) {
        cdp.events.length = 0;

        await cdp.send('Page.navigate', { url: baseUrl + path }, sessionId);
        await sleep(2600);

        const result = await cdp.send('Runtime.evaluate', {
            expression: `(() => {
                const canvases = Array.from(document.querySelectorAll('canvas'));
                return JSON.stringify({
                    chartLib: typeof window.Chart,
                    canvasCount: canvases.length,
                    rendered: canvases.map(c => ({
                        id: c.id,
                        width: c.width,
                        height: c.height,
                        hasChart: !!c.__chart__,
                    })),
                });
            })()`,
            returnByValue: true,
        }, sessionId);

        const info = JSON.parse(result.result.value);
        const pass = info.chartLib === 'function'
            && info.canvasCount > 0
            && info.rendered.every((c) => c.width > 0 && c.height > 0);

        console.log(`\n[${pass ? 'OK  ' : 'FAIL'}] CHARTS ${path}`);
        console.log(`       Chart=${info.chartLib} canvas=${info.canvasCount} ${JSON.stringify(info.rendered)}`);

        if (!pass) hasError = true;
    }

    ws.close();
    chrome.kill();

    console.log(hasError ? '\n=== ADA MASALAH ===' : '\n=== SEMUA HALAMAN BERSIH ===');
    process.exit(hasError ? 1 : 0);
}

main().catch((e) => {
    console.error('AUDIT GAGAL:', e.message);
    chrome.kill();
    process.exit(2);
});
