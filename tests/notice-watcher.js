/* Run with an external jsdom install via NODE_PATH; no runtime dependency. */
const fs = require('fs');
const path = require('path');
const assert = require('assert');
const { JSDOM } = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/thrg-si-notices.js'), 'utf8');
const css = fs.readFileSync(path.join(__dirname, '../assets/inspector.css'), 'utf8');
const wait = () => new Promise(resolve => setTimeout(resolve, 25));
async function fixture(options = {}) {
    const dom = new JSDOM('<!doctype html><html><head></head><body class="js thrg-si-screen"><div id="wpbody-content"><div class="wrap thrg-inspector">' + (options.noAnchor ? '' : '<hr class="wp-header-end">') + (options.notice ? '<div class="notice">Initial</div>' : '') + '</div></div></body></html>', { runScripts: 'outside-only' });
    const style = dom.window.document.createElement('style'); style.textContent = css; dom.window.document.head.appendChild(style);
    if (options.noObserver) dom.window.MutationObserver = undefined;
    if (options.deferred) await new Promise(resolve => dom.window.addEventListener('load', resolve, { once: true }));
    if (!options.noScript) dom.window.eval(source);
    await wait();
    return dom;
}
(async () => {
    let dom = await fixture({ notice: true });
    let doc = dom.window.document, node = doc.querySelector('.notice'), button = doc.querySelector('.thrg-si-notice-bar button');
    assert.equal(dom.window.getComputedStyle(node).display, 'none');
    button.click(); assert.notEqual(dom.window.getComputedStyle(node).display, 'none'); assert.equal(button.getAttribute('aria-expanded'), 'true');
    button.click(); assert.equal(dom.window.getComputedStyle(node).display, 'none'); dom.window.close();
    dom = await fixture(); doc = dom.window.document;
    assert(!doc.body.classList.contains('thrg-si-notices-ready'));
    node = doc.createElement('div'); node.className = 'notice'; node.textContent = 'Late'; doc.querySelector('.wrap').appendChild(node); await wait();
    assert(doc.body.classList.contains('thrg-si-notices-ready')); assert(!doc.querySelector('.thrg-si-notice-bar').hidden);
    doc.querySelector('.thrg-si-notice-bar button').click(); assert.notEqual(dom.window.getComputedStyle(node).display, 'none');
    node.hidden = true; await wait(); assert(doc.querySelector('.thrg-si-notice-bar').hidden); assert(!doc.body.classList.contains('thrg-si-notices-ready')); dom.window.close();
    for (const options of [{ noScript: true }, { noObserver: true }, { noAnchor: true }]) {
        dom = await fixture({ notice: true, ...options });
        assert.notEqual(dom.window.getComputedStyle(dom.window.document.querySelector('.notice')).display, 'none'); dom.window.close();
    }
    dom = await fixture({ notice: true, deferred: true });
    assert.equal(dom.window.document.querySelectorAll('.thrg-si-notice-bar').length, 1); dom.window.close();
    dom = await fixture(); doc = dom.window.document;
    for (const cls of ['notice thrg-si-own', 'notice settings-error']) { node = doc.createElement('div'); node.className = cls; doc.querySelector('.wrap').appendChild(node); }
    await wait(); assert(!doc.body.classList.contains('thrg-si-notices-ready')); dom.window.close();
    console.log('PASS initial/late/dismissed notices, Show/Hide, script/observer/anchor fallback, deferred init and own-notice exclusions');
})().catch(error => { console.error(error); process.exitCode = 1; });
