/* External jsdom via NODE_PATH, matching notice-watcher.js. */
const fs = require('fs');
const path = require('path');
const assert = require('assert/strict');
const { JSDOM } = require('jsdom');
const view = fs.readFileSync(path.join(__dirname, '../includes/view-inspector.php'), 'utf8');
const script = [...view.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(match => match[1]).find(code => code.includes('si-copy'));
async function check(clipboardResult, fallbackResult, expected) {
    const dom = new JSDOM('<textarea id="si-report-text">Report</textarea><span id="si-share-status"></span><button id="si-copy"></button><button id="si-download"></button><button id="si-print"></button>', { runScripts: 'outside-only' });
    const win = dom.window;
    win.document.execCommand = () => {
        if (fallbackResult instanceof Error) throw fallbackResult;
        return fallbackResult;
    };
    if (clipboardResult !== null) {
        win.isSecureContext = true;
        Object.defineProperty(win.navigator, 'clipboard', { value: {
            writeText: () => clipboardResult ? Promise.resolve() : Promise.reject(new Error('Denied')),
        } });
    }
    win.eval(script);
    win.document.getElementById('si-copy').click();
    await new Promise(resolve => setTimeout(resolve, 0));
    assert.equal(win.document.getElementById('si-share-status').textContent, expected);
    win.close();
}
(async () => {
    await check(true, false, 'Copied to clipboard');
    for (const clipboard of [null, false]) {
        await check(clipboard, true, 'Copied to clipboard');
        await check(clipboard, false, 'Press Ctrl+C or Cmd+C to copy');
        await check(clipboard, new Error('Blocked'), 'Press Ctrl+C or Cmd+C to copy');
    }
    console.log('PASS clipboard success, denial, false fallback and throwing fallback');
})().catch(error => { console.error(error); process.exitCode = 1; });
