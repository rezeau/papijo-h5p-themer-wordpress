/* Optional real-browser checks over static in-memory fixtures. No CMS or live site.
 * Usage: node tests/profile/preview-browser.js <php.exe> <official H5P variables.css> [chromium.exe]
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
const pages = JSON.parse(execFileSync(php, ['-n', path.join(__dirname, 'preview.php'), '--render', core], { encoding: 'utf8', windowsHide: true }));
Object.assign(pages, JSON.parse(execFileSync(php, ['-n', path.join(__dirname, '../admin/render-fixtures.php'), core], { encoding: 'utf8', windowsHide: true })));
const assets = {
  '/assets/profile-preview.css': ['text/css', fs.readFileSync(path.join(__dirname, '../../assets/css/profile-preview.css'))],
  '/assets/profile-preview.js': ['text/javascript', fs.readFileSync(path.join(__dirname, '../../assets/js/profile-preview.js'))],
  '/assets/admin.css': ['text/css', fs.readFileSync(path.join(__dirname, '../../assets/css/admin.css'))],
  '/assets/admin.js': ['text/javascript', fs.readFileSync(path.join(__dirname, '../../assets/js/admin.js'))]
};
const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'pjh5pwp-profile-'));
let browser, socket, server, closeBrowser, checks = 0, requestCount = 0, nonGetRequests = 0;
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(fn, description) {
  for (let count = 0; count < 160; count++) { const result = await fn(); if (result) { return result; } await delay(50); }
  throw new Error('Timed out: ' + description);
}
function same(expected, actual, message) { assert.deepStrictEqual(actual, expected, message); checks++; }
(async () => {
  try {
    server = http.createServer((req, res) => {
      requestCount++; if (req.method !== 'GET') nonGetRequests++;
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
    await navigate('profile');
    await until(() => evaluate('document.querySelector("iframe").contentDocument?.getElementById("pjh5pwp-preview-overrides") && !document.querySelector("iframe").hidden'), 'preview document');
    same(false, await evaluate('document.querySelector("details").open'), 'Disclosure starts collapsed');
    same(1, await evaluate('document.querySelectorAll("iframe").length'), 'One frame for every palette');
    same('allow-same-origin', await evaluate('document.querySelector("iframe").getAttribute("sandbox")'), 'Exact scriptless sandbox');
    same(0, await evaluate('document.querySelector("iframe").contentDocument.querySelectorAll("button,input,select,textarea,a,[tabindex],script").length'), 'Preview samples are noninteractive and scriptless');
    same(3, await evaluate('Array.from(new FormData(document.querySelector("form")).keys()).length'), 'Only original Profile nonce/Theme/Density fields');
    same(true, await evaluate(`document.querySelector('iframe').contentDocument.querySelector('meta[http-equiv=Content-Security-Policy]').content.includes("default-src 'none'")`), 'Original CSP retained');
    await command('Page.bringToFront');
    await command('Emulation.setFocusEmulationEnabled', { enabled: true });
    await evaluate('document.querySelector("summary").focus()');
    same('SUMMARY', await evaluate('document.activeElement.tagName'), 'Native disclosure receives keyboard focus');
    await command('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', text: '\r', windowsVirtualKeyCode: 13 });
    await command('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
    same(true, await evaluate('document.querySelector("details").open'), 'Native disclosure opens from keyboard');
    await evaluate('document.querySelector("#pjh5pwp-theme").focus()');
    await delay(100); const requestsBefore = requestCount;
    async function theme(token) {
      await evaluate('(()=>{const select=document.querySelector("#pjh5pwp-theme");select.value=' + JSON.stringify(token) + ';select.dispatchEvent(new Event("change",{bubbles:true}));})()');
      return evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor');
    }
    same('rgb(18, 52, 86)', await theme('custom:public_color'), 'Inherited custom palette has its override');
    same(true, await evaluate('document.querySelector("iframe").contentDocument.querySelector("h2").textContent.includes("<script>")'), 'Label is literal text, never executable markup');
    same('pjh5pwp-theme', await evaluate('document.activeElement.id'), 'Changing the preview does not move focus');
    same('rgb(249, 251, 255)', await theme('core'), 'Core clears complete override set to official variables');
    same('rgb(249, 251, 255)', await theme('builtin:daylight'), 'Daylight is supported separately from Core');
    for (const token of ['builtin:mint','builtin:dark','builtin:sunset','builtin:lavender','custom:legacy']) {
      await theme(token);
      same(false, await evaluate('document.querySelector("iframe").hidden'), 'Valid palette remains visible: ' + token);
    }
    await evaluate('(()=>{const s=document.querySelector("#pjh5pwp-theme");for(const token of ["builtin:dark","core","custom:public_color"]){s.value=token;s.dispatchEvent(new Event("change"));}})()');
    same('rgb(18, 52, 86)', await evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor'), 'Rapid changes end on latest colors');
    const cssBeforeDensity = await evaluate('document.querySelector("iframe").contentDocument.getElementById("pjh5pwp-preview-overrides").textContent');
    await evaluate('(()=>{const s=document.querySelector("#pjh5pwp-density");s.value="large";s.dispatchEvent(new Event("change"));})()');
    same(cssBeforeDensity, await evaluate('document.querySelector("iframe").contentDocument.getElementById("pjh5pwp-preview-overrides").textContent'), 'Density never affects color preview');
    await evaluate('document.querySelector("form").reset()'); await delay(30);
    same('site_default', await evaluate('document.querySelector("#pjh5pwp-theme").value'), 'Native form reset restores Theme');
    same('rgb(135, 101, 67)', await evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor'), 'Reset restores unpublished global effective colors');
    same(false, await evaluate('document.body.innerHTML.includes("hidden_global_secret") || document.body.innerHTML.includes("Private global name")'), 'Private global identity absent from DOM and metadata');
    same('status', await evaluate('document.querySelector(".pjh5pwp-profile-preview-status").getAttribute("role")'), 'Accessible live status');
    same('polite', await evaluate('document.querySelector(".pjh5pwp-profile-preview-status").getAttribute("aria-live")'), 'Polite announcements');
    await command('Emulation.setDeviceMetricsOverride', { width: 320, height: 700, deviceScaleFactor: 1, mobile: false });
    same(true, await evaluate('document.documentElement.scrollWidth <= innerWidth'), 'Profile fixture fits narrow layout');
    same(true, await evaluate('(()=>{const d=document.querySelector("iframe").contentDocument;return d.documentElement.scrollWidth<=d.documentElement.clientWidth;})()'), 'Compact sample avoids horizontal overflow');
    await evaluate('document.documentElement.style.zoom="2"');
    same(true, await evaluate('document.querySelector("iframe").getBoundingClientRect().width <= document.querySelector("td").getBoundingClientRect().width'), 'Preview stays inside its cell at 200% CSS zoom');
    same(0, nonGetRequests, 'No writes from interactions');
    same(requestsBefore, requestCount, 'No requests from selection changes or reset');
    await command('Emulation.setDeviceMetricsOverride', { width: 1280, height: 900, deviceScaleFactor: 1, mobile: false });
    await navigate('edit');
    await until(() => evaluate('document.querySelector(".pjh5pwp-preview-frame").contentDocument.querySelector("main")'), 'existing administration preview');
    same(2, await evaluate('document.querySelectorAll("iframe").length'), 'Administration retains saved preview and Core comparison');
    same(33, await evaluate('document.querySelectorAll("[data-pjh5pwp-color]").length'), 'Administration retains all editable color fields');
    same(false, await evaluate('document.querySelector("iframe").contentDocument.body.classList.contains("pjh5pwp-preview-compact")'), 'Administration keeps full-size sample mode');
    same('rgb(249, 251, 255)', await evaluate('getComputedStyle(document.querySelectorAll("iframe")[1].contentDocument.querySelector("main")).backgroundColor'), 'Administration Core comparison unchanged');
    const adminBefore = await evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor');
    await delay(100); const adminRequests = requestCount;
    await evaluate('(()=>{const f=document.querySelector("[data-pjh5pwp-color=\\"--h5p-theme-background\\"]");f.value="#abc";f.dispatchEvent(new Event("input",{bubbles:true}));})()');
    same('rgb(170, 187, 204)', await evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor'), 'Administration live color editing preserved');
    same('rgb(249, 251, 255)', await evaluate('getComputedStyle(document.querySelectorAll("iframe")[1].contentDocument.querySelector("main")).backgroundColor'), 'Editing cannot affect Core comparison');
    await evaluate('document.querySelector(".pjh5pwp-form").reset()'); await delay(30);
    same(adminBefore, await evaluate('getComputedStyle(document.querySelector("iframe").contentDocument.querySelector("main")).backgroundColor'), 'Administration reset preserved');
    same(adminRequests, requestCount, 'Administration preview interactions remain local');
    console.log('Profile preview Chromium static fixtures: ' + checks + ' assertions passed; no WordPress bootstrap or live site.');
  } catch (error) { console.error('Browser check failed:', error); throw error;
  } finally {
    if (closeBrowser) { await Promise.race([closeBrowser().catch(() => {}), delay(1500)]); }
    if (socket) { socket.close(); }
    if (browser && browser.exitCode === null) { await delay(500); if (browser.exitCode === null) { browser.kill(); } }
    if (server) { server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }
    // Only the exact temporary profile created above is eligible for recursive removal.
    const resolved = path.resolve(profile);
    if (path.dirname(resolved) !== path.resolve(os.tmpdir()) || !path.basename(resolved).startsWith('pjh5pwp-profile-')) { throw new Error('Unsafe test profile cleanup path'); }
    // Windows browser launchers can leave detached children holding profile files.
    // Match only this exact test profile, never an ordinary user browser/session.
    if (process.platform === 'win32') {
      execFileSync('powershell.exe', ['-NoProfile', '-Command', '$testProfile = $env:PJH5PWP_TEST_PROFILE; Get-CimInstance Win32_Process | Where-Object { ($_.Name -eq "msedge.exe" -or $_.Name -eq "chrome.exe") -and $_.CommandLine -and $_.CommandLine.Contains($testProfile) } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }'], { windowsHide: true, env: { ...process.env, PJH5PWP_TEST_PROFILE: resolved }, stdio: 'pipe' });
      await delay(300);
    }
    fs.rmSync(resolved, { recursive: true, force: true, maxRetries: 3, retryDelay: 100 });
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
