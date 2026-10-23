<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pathao_client.php';
include_once 'includes/header.php';

$settingsFile = '../settings.json';
$settings     = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?? [] : [];

$success = $error = '';

// ── Save credentials ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pathao'])) {
    csrf_verify();
    $settings['pathao_client_id']     = trim($_POST['pathao_client_id']     ?? '');
    $settings['pathao_client_secret'] = trim($_POST['pathao_client_secret'] ?? '');
    $settings['pathao_store_id']      = trim($_POST['pathao_store_id']       ?? '');
    $settings['pathao_default_city']  = (int)($_POST['pathao_default_city']  ?? 0);
    $settings['pathao_default_zone']  = (int)($_POST['pathao_default_zone']  ?? 0);
    $settings['pathao_sandbox']       = isset($_POST['pathao_sandbox']) ? true : false;
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    $success = 'Pathao settings saved.';
    // Re-read
    $settings = json_decode(file_get_contents($settingsFile), true) ?? [];
}

$pathao      = new PathaoClient($settings);
$isConfigured = $pathao->isConfigured();

// Test connection on request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    csrf_verify();
    try {
        $cities = $pathao->getCities();
        $success = 'Connection successful — ' . count($cities) . ' cities returned from Pathao.';
    } catch (RuntimeException $e) {
        $error = 'Connection failed: ' . htmlspecialchars($e->getMessage());
    }
}
?>

<div class="mb-5 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Pathao Courier</h2>
        <p class="text-slate-500 text-sm mt-1">Configure Pathao API and manage delivery settings.</p>
    </div>
    <?php if ($isConfigured): ?>
    <span class="text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-full">
        <i class="fas fa-check-circle mr-1"></i>Connected
    </span>
    <?php else: ?>
    <span class="text-xs font-bold bg-rose-100 text-rose-600 border border-rose-200 px-3 py-1.5 rounded-full">
        <i class="fas fa-times-circle mr-1"></i>Not configured
    </span>
    <?php endif; ?>
</div>

<?php if ($success): ?>
<div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm font-medium px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-check-circle flex-shrink-0"></i> <?= $success ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm font-medium px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-times-circle flex-shrink-0"></i> <?= $error ?>
</div>
<?php endif; ?>

<!-- ── API Credentials ────────────────────────────────────────────────────── -->
<form method="POST">
    <?= csrf_field() ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-5">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-3">
            <i class="fas fa-key text-slate-400"></i>
            <h2 class="font-bold text-slate-800">API Credentials</h2>
            <span class="text-xs text-slate-400 ml-1">From Pathao merchant portal → Developer API</span>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Client ID</label>
                <input type="text" name="pathao_client_id"
                       value="<?= htmlspecialchars($settings['pathao_client_id'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-600"
                       placeholder="Your Pathao client ID">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Client Secret</label>
                <input type="password" name="pathao_client_secret"
                       value="<?= htmlspecialchars($settings['pathao_client_secret'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-600"
                       placeholder="Your Pathao client secret">
            </div>
        </div>
        <div class="px-6 pb-5">
            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox" name="pathao_sandbox" <?= !empty($settings['pathao_sandbox']) ? 'checked' : '' ?>
                       class="w-4 h-4 rounded-lg accent-rose-600">
                <span class="text-sm font-medium text-slate-700">Use Sandbox environment
                    <span class="text-slate-400 font-normal">(for testing — uncheck for production)</span>
                </span>
            </label>
        </div>
    </div>

    <!-- ── Store & Delivery Defaults ──────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-5">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-3">
            <i class="fas fa-store text-slate-400"></i>
            <h2 class="font-bold text-slate-800">Store & Delivery Defaults</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">
                    Store ID
                    <button type="button" onclick="loadStores()" class="ml-2 text-brand-600 hover:underline font-normal lowercase">fetch stores</button>
                </label>
                <div class="flex gap-2">
                    <input type="number" name="pathao_store_id" id="store_id_input"
                           value="<?= htmlspecialchars($settings['pathao_store_id'] ?? '') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-600"
                           placeholder="e.g. 12345">
                </div>
                <div id="store-list" class="mt-2 space-y-1 hidden"></div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">
                    Default City
                    <button type="button" onclick="loadCities()" class="ml-2 text-brand-600 hover:underline font-normal lowercase">load cities</button>
                </label>
                <select name="pathao_default_city" id="default_city_select"
                        onchange="loadDefaultZones(this.value)"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-600 bg-white">
                    <option value="">— select city —</option>
                    <?php if (!empty($settings['pathao_default_city'])): ?>
                    <option value="<?= (int)$settings['pathao_default_city'] ?>" selected>
                        City ID: <?= (int)$settings['pathao_default_city'] ?>
                    </option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Default Zone</label>
                <select name="pathao_default_zone" id="default_zone_select"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-600 bg-white">
                    <option value="">— select zone —</option>
                    <?php if (!empty($settings['pathao_default_zone'])): ?>
                    <option value="<?= (int)$settings['pathao_default_zone'] ?>" selected>
                        Zone ID: <?= (int)$settings['pathao_default_zone'] ?>
                    </option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" name="save_pathao"
                class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition shadow flex items-center gap-2">
            <i class="fas fa-save"></i> Save Settings
        </button>
        <?php if ($isConfigured): ?>
        <button type="submit" name="test_connection"
                class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition shadow flex items-center gap-2">
            <i class="fas fa-plug"></i> Test Connection
        </button>
        <?php endif; ?>
    </div>
</form>

<!-- ── How to get credentials ─────────────────────────────────────────────── -->
<div class="mt-6 bg-brand-50 border border-brand-200 rounded-2xl p-5 text-sm text-brand-800">
    <p class="font-bold mb-2"><i class="fas fa-info-circle mr-1"></i> How to get your Pathao API credentials</p>
    <ol class="list-decimal list-inside space-y-1 text-brand-700">
        <li>Log in to your Pathao merchant account at <strong>merchant.pathao.com</strong></li>
        <li>Go to <strong>Courier → Developer API</strong></li>
        <li>Copy your <strong>Client ID</strong> and <strong>Client Secret</strong> — that's all you need</li>
        <li>Paste them above and click <strong>Save Settings</strong></li>
        <li>Click <strong>Test Connection</strong>, then <strong>Fetch Stores</strong> to get your Store ID</li>
        <li>Pick a default <strong>City</strong> and <strong>Zone</strong> for delivery</li>
    </ol>
</div>

<script>
function apiCall(params, cb) {
    var url = 'pathao_api.php?' + new URLSearchParams(params);
    fetch(url).then(r=>r.json()).then(cb).catch(e=>cb({error:e.message}));
}

function loadCities() {
    var sel = document.getElementById('default_city_select');
    sel.innerHTML = '<option>Loading…</option>';
    apiCall({action:'get_cities'}, function(data) {
        if (data.error) { alert(data.error); sel.innerHTML='<option value="">— error —</option>'; return; }
        var saved = <?= (int)($settings['pathao_default_city'] ?? 0) ?>;
        sel.innerHTML = '<option value="">— select city —</option>';
        (data.cities || []).forEach(function(c) {
            sel.innerHTML += '<option value="'+c.city_id+'"'+(c.city_id==saved?' selected':'')+'>'+c.city_name+'</option>';
        });
        if (saved) loadDefaultZones(saved);
    });
}

function loadDefaultZones(cityId) {
    if (!cityId) return;
    var sel = document.getElementById('default_zone_select');
    sel.innerHTML = '<option>Loading…</option>';
    apiCall({action:'get_zones', city_id:cityId}, function(data) {
        if (data.error) { alert(data.error); sel.innerHTML='<option value="">— error —</option>'; return; }
        var saved = <?= (int)($settings['pathao_default_zone'] ?? 0) ?>;
        sel.innerHTML = '<option value="">— select zone —</option>';
        (data.zones || []).forEach(function(z) {
            sel.innerHTML += '<option value="'+z.zone_id+'"'+(z.zone_id==saved?' selected':'')+'>'+z.zone_name+'</option>';
        });
    });
}

function loadStores() {
    var list = document.getElementById('store-list');
    list.innerHTML = '<p class="text-xs text-slate-400">Loading…</p>';
    list.classList.remove('hidden');
    apiCall({action:'get_stores'}, function(data) {
        if (data.error) { list.innerHTML = '<p class="text-xs text-rose-500">'+data.error+'</p>'; return; }
        if (!data.stores || !data.stores.length) { list.innerHTML = '<p class="text-xs text-slate-400">No stores found.</p>'; return; }
        list.innerHTML = '';
        (data.stores).forEach(function(s) {
            list.innerHTML += '<button type="button" onclick="document.getElementById(\'store_id_input\').value=\''+s.store_id+'\'" '
                + 'class="block w-full text-left text-xs px-2 py-1.5 rounded-lg bg-slate-50 hover:bg-brand-50 border border-slate-200 hover:border-brand-300 transition">'
                + '<span class="font-bold text-slate-700">'+s.store_name+'</span>'
                + ' <span class="text-slate-400">ID: '+s.store_id+'</span>'
                + '</button>';
        });
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>
