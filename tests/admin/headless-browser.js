/* Optional real-browser checks over static in-memory fixtures. No CMS or live site.
 * Usage: node tests/admin/headless-browser.js <php.exe> <official H5P variables.css> [chromium.exe]
 */
'use strict';
const fs = require('fs');
const os = require('os');
const path = require('path');
const http = require('http');
const { spawn, execFileSync } = require('child_process');
const assert = require('assert');
const [php, core, executable = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'] = process.argv.slice(2);
if (!php || !core) { throw new Error('Supply PHP binary and the official H5P variables CSS path.'); }
const pages = JSON.parse(execFileSync(php, ['-n', path.join(__dirname, 'render-fixtures.php'), core], { encoding: 'utf8', windowsHide: true }));
const assets = {
  '/assets/admin.css': ['text/css', fs.readFileSync(path.join(__dirname, '../../assets/css/admin.css'))],
  '/assets/admin.js': ['text/javascript', fs.readFileSync(path.join(__dirname, '../../assets/js/admin.js'))]
};
const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'pjh5pwp-admin-'));
let browser, socket, server, closeBrowser, checks = 0;
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(fn, description) {
  for (let count = 0; count < 160; count++) { const result = await fn(); if (result) { return result; } await delay(50); }
  throw new Error('Timed out: ' + description);
}
function same(expected, actual, message) { assert.deepStrictEqual(actual, expected, message); checks++; }
(async () => {
  try {
    server = http.createServer((req, res) => {
      if (req.method !== 'GET') { res.writeHead(405); res.end(); return; }
      const name = req.url.replace('/fixture/', '');
      if (Object.hasOwn(pages, name)) { res.setHeader('Content-Type', 'text/html; charset=utf-8'); res.end(pages[name]); return; }
      if (Object.hasOwn(assets, req.url)) { res.setHeader('Content-Type', assets[req.url][0]); res.end(assets[req.url][1]); return; }
      res.writeHead(404); res.end();
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    browser = spawn(executable, ['--headless=new', '--no-first-run', '--no-default-browser-check', '--disable-extensions', '--no-proxy-server', '--remote-debugging-port=0', '--user-data-dir=' + profile, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
    browser.on('error', error => { throw error; });
    const portFile = path.join(profile, 'DevToolsActivePort');
    await until(() => fs.existsSync(portFile), 'browser debugger');
    const port = fs.readFileSync(portFile, 'utf8').split('\n')[0];
    const targets = await (await fetch('http://127.0.0.1:' + port + '/json/list')).json();
    const target = targets.find(item => item.type === 'page');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { socket.addEventListener('open', resolve); socket.addEventListener('error', reject); });
    let next = 1;
    const pending = new Map();
    socket.addEventListener('message', event => {
      const message = JSON.parse(event.data); if (!message.id || !pending.has(message.id)) { return; }
      const { resolve, reject, timer } = pending.get(message.id); pending.delete(message.id); clearTimeout(timer);
      if (message.error) { reject(new Error(JSON.stringify(message.error))); } else { resolve(message.result); }
    });
    const command = (method, params = {}) => new Promise((resolve, reject) => {
      const id = next++;
      const timer = setTimeout(() => { pending.delete(id); reject(new Error('CDP timeout: ' + method)); }, 8000);
      pending.set(id, { resolve, reject, timer }); socket.send(JSON.stringify({ id, method, params }));
    });
    closeBrowser = () => command('Browser.close');
    async function evaluate(expression) {
      const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
      if (result.exceptionDetails) { throw new Error(JSON.stringify(result.exceptionDetails)); }
      return result.result.value;
    }
    const url = 'http://127.0.0.1:' + server.address().port + '/fixture/';
    async function navigate(name) {
      console.log('Checking static fixture: ' + name);
      await command('Page.navigate', { url: url + name });
      await until(() => evaluate('location.pathname === ' + JSON.stringify('/fixture/' + name) + ' && document.readyState === "complete"'), name + ' page');
    }
    await navigate('edit');
    await until(() => evaluate('document.querySelector(".pjh5pwp-preview-frame").contentDocument.querySelector("main") !== null'), 'sample frame');
    same(33, await evaluate('document.querySelectorAll("input[type=color]").length'), '33 native companions in browser');
    same(5, await evaluate('document.querySelectorAll(".pjh5pwp-color-group:not([open])").length'), 'Five groups initially collapsed');
    same(false, await evaluate('document.querySelector(".pjh5pwp-picker").hidden'), 'Behavior script booted');
    const palette = await evaluate('Array.from(document.querySelectorAll("iframe")).map(f=>getComputedStyle(f.contentDocument.querySelector("main")).backgroundColor)');
    same(2, palette.length, 'Saved preview and Core comparison');
    same('rgb(249, 251, 255)', palette[1], 'Core comparison uses original H5P variables');
    assert.notStrictEqual(palette[0], 'rgb(255, 0, 255)', 'Parent theme CSS cannot enter preview frame'); checks++;
    const spacing = await evaluate('Array.from(document.querySelectorAll("iframe")).map(f=>getComputedStyle(f.contentDocument.querySelector("main")).paddingTop)');
    same(spacing[1], spacing[0], 'Themed sample preserves the same unclassified Core spacing as the Core comparison');
    assert.ok(parseFloat(spacing[0]) > 0, 'Native Core spacing resolves'); checks++;
    async function color(value) {
      await evaluate('(()=>{const f=document.querySelector("[data-pjh5pwp-color=\\"--h5p-theme-background\\"]");f.value=' + JSON.stringify(value) + ';f.dispatchEvent(new Event("input",{bubbles:true}));})()');
      return evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor');
    }
    same('rgb(18, 52, 86)', await color('#123456'), 'Live valid color rendered');
    same('rgb(18, 52, 86)', await color('url(https://invalid.example)'), 'Invalid color retains last valid rendering');
    same(palette[0], await color(''), 'Blank restores inherited Mint');
    await color('#abc');
    await evaluate('document.querySelector(".pjh5pwp-form").reset()');
    await delay(100);
    same(palette[0], await evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor'), 'Native form reset restores saved preview');
    same('', await evaluate('document.querySelector("[data-pjh5pwp-color=\\"--h5p-theme-background\\"]").value'), 'Reset restores blank sparse field');
    same(false, await evaluate('document.querySelector("iframe").sandbox.contains("allow-scripts")'), 'Frame scripts prohibited');
    same(true, await evaluate('getComputedStyle(document.querySelector(".pjh5pwp-color-row")).gridTemplateColumns.length > 0'), 'Color row layout applied');
    await command('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: false });
    same(true, await evaluate('document.documentElement.scrollWidth <= innerWidth'), 'Narrow layout avoids page overflow');
    await navigate('builtin');
    same(0, await evaluate('document.querySelectorAll("form").length'), 'Builtin view immutable');
    same(34, await evaluate('document.querySelectorAll(".pjh5pwp-palette tbody tr").length'), 'Complete literal preset palette displayed');
    await navigate('duplicate');
    same(0, await evaluate('document.querySelectorAll("[data-pjh5pwp-color]").length'), 'Builtin duplication palette read-only');
    await navigate('delete');
    same(0, await evaluate('document.querySelectorAll("form").length'), 'Selected global default has no delete form');
    await navigate('overview');
    const forms = await evaluate(`Array.from(document.forms, form => ({
      op: form.querySelector('[name="pjh5pwp_op"]')?.value,
      mode: form.querySelector('[name="mode"]')?.value || '',
      method: form.method,
      nonce: Boolean(form.querySelector('[name="pjh5pwp_nonce"], [name="nonce"]')?.value),
      revision: form.querySelector('[name="revision"]')?.value,
      context: Boolean(form.querySelector('[name="pjh5pwp_context"], [name="context"]')?.value)
    }))`);
    same(['default:', 'visibility:', 'export:single', 'export:selected', 'export:all'].sort(),
      forms.map(form => form.op + ':' + form.mode).sort(), 'Exactly the independent default, publication and three export operations');
    same(true, forms.every(form => form.method === 'post' && form.nonce && form.context && form.revision !== undefined), 'Every operation retains POST, nonce and snapshot binding');
    same(true, await evaluate(`(() => {
      const visibility = document.querySelector('[name="pjh5pwp_op"][value="visibility"]').form;
      const button = visibility.querySelector('button[form="pjh5pwp-export-example"]');
      const single = document.getElementById('pjh5pwp-export-example');
      return Boolean(button && single && button.form === single && !visibility.contains(single)
        && single.querySelector('[name="id"]').value === 'example');
    })()`), 'Row export targets its separate single-theme form outside publication');
    same(true, await evaluate(`(() => {
      const selected = Array.from(document.forms).find(form => form.querySelector('[name="mode"]')?.value === 'selected');
      const choices = selected.querySelectorAll('input[name="ids[]"]');
      return choices.length === 1 && choices[0].value === 'example'
        && Array.from(document.querySelectorAll('input[name^="visible["]')).every(input => input.form.querySelector('[name="pjh5pwp_op"]').value === 'visibility');
    })()`), 'Bulk selection contains only the custom theme; publication controls retain their owner');
    same(2, await evaluate(`Array.from(document.querySelectorAll('a[href*="pjh5pwp_view=import"]')).filter(link => link.textContent === 'Import theme colors').length`), 'Overview toolbar and transfer section both link to the dedicated Import screen');
    same(5, await evaluate('document.querySelectorAll("input[name^=\\"visible[\\"]").length'), 'Four optional builtins and one unpublished custom');
    await navigate('add');
    same(33, await evaluate('document.querySelectorAll("[data-pjh5pwp-color]").length'), 'Add all 33 color properties');
    same(0, await evaluate('document.querySelectorAll("iframe").length'), 'Add has no saved preview');
    console.log('Phase 1C-B real Chromium static fixtures: ' + checks + ' assertions passed; no CMS installation or live-site access.');
  } catch (error) { console.error('Browser check failed:', error); throw error;
  } finally {
    if (closeBrowser) { await Promise.race([closeBrowser().catch(() => {}), delay(1500)]); }
    if (socket) { socket.close(); }
    if (browser && browser.exitCode === null) { await delay(500); if (browser.exitCode === null) { browser.kill(); } }
    if (server) { server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }
    // Only the exact temporary profile created above is eligible for recursive removal.
    const resolved = path.resolve(profile);
    if (path.dirname(resolved) !== path.resolve(os.tmpdir()) || !path.basename(resolved).startsWith('pjh5pwp-admin-')) { throw new Error('Unsafe test profile cleanup path'); }
    // Windows browser launchers can leave detached children holding profile files.
    // Match only this exact test profile, never an ordinary user browser/session.
    if (process.platform === 'win32') {
      execFileSync('powershell.exe', ['-NoProfile', '-Command', '$testProfile = $env:PJH5PWP_TEST_PROFILE; Get-CimInstance Win32_Process | Where-Object { ($_.Name -eq "msedge.exe" -or $_.Name -eq "chrome.exe") -and $_.CommandLine -and $_.CommandLine.Contains($testProfile) } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }'], { windowsHide: true, env: { ...process.env, PJH5PWP_TEST_PROFILE: resolved }, stdio: 'pipe' });
      await delay(300);
    }
    fs.rmSync(resolved, { recursive: true, force: true, maxRetries: 3, retryDelay: 100 });
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
