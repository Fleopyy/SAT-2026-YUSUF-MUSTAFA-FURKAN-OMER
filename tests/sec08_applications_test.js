const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { spawnSync } = require('node:child_process');

// Render the actual template without loading auth, headers or a database connection.
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'admin/pages/applications.php'), 'utf8');
assert(!/on(?:click|submit)="[^"\r\n]*isletme_adi/.test(source));
assert(!source.includes('addslashes('));
const php = process.env.PHP_BINARY || 'C:/xampp/php/php.exe';
const render = spawnSync(php, [], { cwd: root, encoding: 'utf8', input: String.raw`<?php
$source = file_get_contents('admin/pages/applications.php');
$auth = file_get_contents('admin/includes/auth.php');
preg_match('/function e\(string \$value\): string \{.*?\n\}/s', $auth, $helper);
eval($helper[0]);
function canCurrentUser($permission) { return true; }
$names = ["Tek ' tirnak", 'Cift " tirnak', "Ters \\ egik \\", '<img src=x onerror="globalThis.xss=1"> & < >', "Satir\nsonu\r\nve\rson", "\\');globalThis.xss=1;//", "\" data-evil=\"yes", '</script><script>globalThis.xss=1</script>'];
$applications = [];
foreach ($names as $i => $name) {
    $applications[] = ['id' => 101 + $i, 'isletme_adi' => $name, 'isletme_sahibi' => '', 'telefon' => '', 'eposta' => '', 'sehir' => '', 'ilce' => '', 'olusturulma_tarihi' => '2026-01-01', 'durum' => 'pending'];
}
$detailApp = $applications[3];
$currentTab = 'pending'; $search = ''; $counts = array_fill_keys(['all', 'pending', 'active', 'rejected', 'suspended'], 0);
$csrfToken = 'sec08-test-token';
$body = substr($source, strpos($source, '?>') + 2);
$body = str_replace("<?php include __DIR__ . '/../includes/footer.php'; ?>", '', $body);
ob_start(); eval('?>' . $body); $html = ob_get_clean();
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($dom);
$result = [];
foreach (['data-reject-business', 'data-approve-business', 'data-close-reject-modal'] as $attribute) {
    $result[$attribute] = [];
    foreach ($xpath->query('//*[@' . $attribute . ']') as $node) {
        $item = ['name' => $node->getAttribute('data-business-name'), 'id' => $node->getAttribute('data-business-id')];
        foreach (['business_id', 'action', 'csrf_token'] as $field) {
            $inputs = $xpath->query('.//input[@name="' . $field . '"]', $node);
            if ($inputs->length) $item[$field] = $inputs->item(0)->getAttribute('value');
        }
        if ($node->hasAttribute('onclick') || $node->hasAttribute('onsubmit') || $node->hasAttribute('data-evil')) throw new Exception('Attribute injection');
        $result[$attribute][] = $item;
    }
}
if ($dom->getElementsByTagName('script')->length !== 1 || $dom->getElementsByTagName('img')->length !== 0) throw new Exception('HTML injection');
$result['names'] = $names;
echo json_encode($result, JSON_THROW_ON_ERROR);
` });
assert.equal(render.status, 0, render.stderr);
const data = JSON.parse(render.stdout);
function element(item = {}) {
    return { dataset: { businessName: item.name, businessId: item.id }, style: {}, value: '', textContent: '', listeners: {},
        addEventListener(event, fn) { this.listeners[event] = fn; } };
}
const groups = Object.fromEntries(Object.entries(data).filter(([key]) => key.startsWith('data-')).map(([key, items]) => [key, items.map(element)]));
const ids = Object.fromEntries(['rejectBusinessId', 'rejectBusinessName', 'rejectModal'].map(id => [id, element()]));
let accepted = false;
let message;
const context = { document: { querySelectorAll: selector => groups[selector.slice(1, -1)], getElementById: id => ids[id] }, confirm: text => { message = text; return accepted; } };
vm.createContext(context);
vm.runInContext(source.match(/<script>([\s\S]*?)<\/script>/)[1], context);
assert.equal(groups['data-reject-business'].length, data.names.length + 1);
assert.equal(groups['data-approve-business'].length, data.names.length);
for (let i = 0; i < data.names.length; i++) {
    const name = data.names[i];
    const form = groups['data-approve-business'][i];
    assert.equal(form.dataset.businessName, name);
    assert.equal(data['data-approve-business'][i].business_id, String(101 + i));
    assert.equal(data['data-approve-business'][i].action, 'approve');
    assert.equal(data['data-approve-business'][i].csrf_token, 'sec08-test-token');
    for (accepted of [false, true]) {
        let prevented = false;
        form.listeners.submit({ preventDefault() { prevented = true; } });
        assert.equal(prevented, !accepted);
        assert(message.startsWith(name + ' '));
    }
}
for (const button of groups['data-reject-business']) {
    button.listeners.click();
    assert.equal(ids.rejectBusinessId.value, button.dataset.businessId);
    assert.equal(ids.rejectBusinessName.textContent, button.dataset.businessName);
    assert.equal(ids.rejectModal.style.display, 'flex');
    for (const close of groups['data-close-reject-modal']) {
        close.listeners.click();
        assert.equal(ids.rejectModal.style.display, 'none');
    }
}
assert.equal(context.xss, undefined);
assert(source.includes('name="business_id" id="rejectBusinessId"'));
assert(source.includes('name="action" value="reject"'));
assert(source.includes("requirePermission('businesses.view')"));
for (const permission of ['approve', 'reject', 'suspend']) assert(source.includes(`canCurrentUser('businesses.${permission}')`));
console.log(`SEC-08 passed: ${data.names.length} special-character names; approval/confirmation cancellation, list/detail rejection, modal cancellation, business IDs, CSRF and permission guards. No database connection.`);
