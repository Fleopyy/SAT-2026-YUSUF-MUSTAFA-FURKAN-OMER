const fs = require('fs');
const vm = require('vm');
const assert = require('assert/strict');
const source = fs.readFileSync(require('path').join(__dirname, '../includes/cookie_banner.php'), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
let reloads = 0;
const inputs = {};
const document = {
  cookie: '', readyState: 'loading',
  addEventListener() {},
  getElementById(id) { return inputs[id] || null; }
};
const location = { protocol: 'http:', reload() { reloads++; } };
const context = { document, location, window: { location, dispatchEvent() {} }, setTimeout() {}, CustomEvent: function() {} };
vm.createContext(context);
vm.runInContext(script, context);
const consent = context.BitirasCookie;
assert.equal(consent.getConsent(), null);
document.cookie = 'bitiras_cookie_consent=%broken';
assert.equal(consent.getConsent(), null);
function set(value) { document.cookie = 'bitiras_cookie_consent=' + encodeURIComponent(JSON.stringify(value)); }
set({ version: 1, functional: true, updated_at: '2000-01-01' });
assert.equal(consent.hasConsent('functional'), false);
set({ version: 1, functional: 'true', updated_at: new Date().toISOString() });
assert.equal(consent.hasConsent('functional'), false);
set({ version: 1, functional: true, updated_at: new Date(Date.now() + 86400000).toISOString() });
assert.equal(consent.hasConsent('functional'), false);
document.cookie = '';
consent.acceptAll();
assert.equal(consent.hasConsent('functional'), true);
assert.equal(reloads, 1);
consent.rejectAll();
assert.equal(consent.hasConsent('functional'), false);
assert.equal(consent.hasConsent('analytics'), false);
assert.equal(consent.hasConsent('marketing'), false);
assert.equal(reloads, 2);
assert.match(document.cookie, /SameSite=Lax/);
assert.doesNotMatch(document.cookie, /; Secure/);
location.protocol = 'https:';
consent.rejectAll();
assert.match(document.cookie, /; Secure/);
assert.equal(reloads, 2);
console.log('Cookie consent: 15 assertions passed (VM, no network).');
