<?php
include_once 'includes/header.php';
require_once __DIR__ . '/includes/pathao_client.php';

$settingsFile = '../settings.json';
$settings     = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?? [] : [];
$pathao       = new PathaoClient($settings);

$ordersFile  = '../orders.json';
$orders      = file_exists($ordersFile) ? json_decode(file_get_contents($ordersFile), true) : [];
$orderId     = $_GET['id'] ?? '';
$orderIndex  = null;
$order       = null;

foreach ($orders as $index => $o) {
    if ($o['id'] === $orderId) {
        $orderIndex = $index;
        $order = $o;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && $orderIndex !== null) {
    csrf_verify(); // Bug #16
    $orders[$orderIndex]['status'] = $_POST['status'];
    file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
    $order   = $orders[$orderIndex];
    $success = 'Order status updated successfully.';
}

if (!$order) {
    echo "<div class='p-4 text-rose-600'>Order not found. <a href='orders.php' class='underline'>← Back</a></div>";
    include_once 'includes/footer.php';
    exit;
}

$statusColors = [
    'Pending'    => 'bg-amber-100 text-amber-700 border-amber-200',
    'Processing' => 'bg-brand-100 text-brand-700 border-brand-200',
    'Completed'  => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'Cancelled'  => 'bg-rose-100 text-rose-700 border-rose-200',
];
$sc = $statusColors[$order['status'] ?? ''] ?? 'bg-slate-100 text-slate-700 border-slate-200';
?>

<!-- Back + Title -->
<div class="mb-5 flex items-center gap-3">
    <a href="orders.php"
       class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:border-slate-300 transition shadow-sm">
        <i class="fas fa-arrow-left text-sm"></i>
    </a>
    <div class="min-w-0">
        <h2 class="text-lg font-bold text-slate-900 leading-tight">Order #<?= htmlspecialchars($order['id']) ?></h2>
        <div class="flex items-center gap-2 mt-0.5">
            <span class="text-xs px-2 py-0.5 rounded-full border font-bold <?= $sc ?>"><?= htmlspecialchars($order['status'] ?? '') ?></span>
            <?php if(($order['source'] ?? '') === 'landing_page'): ?>
                <span class="text-xs font-bold bg-brand-100 text-brand-700 px-2 py-0.5 rounded-full border border-brand-200">Landing Page</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(isset($success)): ?>
<div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm font-medium px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-check-circle flex-shrink-0"></i> <?= $success ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- ── Items Ordered ──────────────────────────────────────────── -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h2 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-shopping-cart text-slate-400 text-sm"></i> Items Ordered
            </h2>
        </div>

        <!-- Mobile: stacked items -->
        <div class="md:hidden divide-y divide-slate-100">
            <?php foreach($order['items'] as $item): ?>
            <div class="flex items-center gap-3 px-4 py-3">
                <?php if(!empty($item['image'])): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>"
                         class="w-12 h-12 object-cover rounded-xl border border-slate-200 flex-shrink-0"
                         onerror="this.style.display='none'">
                <?php else: ?>
                    <div class="w-12 h-12 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-box text-slate-300"></i>
                    </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="text-xs text-slate-500 mt-0.5">Qty: <?= htmlspecialchars($item['qty']) ?> · ৳<?= number_format($item['price']) ?> each</div>
                </div>
                <div class="flex-shrink-0 font-bold text-slate-900 text-sm">৳<?= number_format($item['total']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop: table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs uppercase text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3 font-bold">Item</th>
                        <th class="px-5 py-3 font-bold">Qty</th>
                        <th class="px-5 py-3 font-bold">Price</th>
                        <th class="px-5 py-3 font-bold text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($order['items'] as $item): ?>
                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                        <td class="px-5 py-3.5 text-sm font-medium text-slate-800"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="px-5 py-3.5 text-sm text-slate-600"><?= htmlspecialchars($item['qty']) ?></td>
                        <td class="px-5 py-3.5 text-sm text-slate-600">৳<?= number_format($item['price']) ?></td>
                        <td class="px-5 py-3.5 text-sm font-bold text-slate-800 text-right">৳<?= number_format($item['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Order totals -->
        <div class="px-5 py-4 border-t border-slate-100 space-y-2 bg-slate-50">
            <div class="flex justify-between text-sm text-slate-500">
                <span>Subtotal</span>
                <span>৳<?= number_format($order['subtotal']) ?></span>
            </div>
            <div class="flex justify-between text-sm text-slate-500">
                <span>Shipping</span>
                <span>৳<?= number_format($order['shipping_cost']) ?></span>
            </div>
            <div class="flex justify-between text-base font-black text-slate-900 pt-2 border-t border-slate-200">
                <span>Grand Total</span>
                <span class="text-brand-600">৳<?= number_format($order['total']) ?></span>
            </div>
        </div>
    </div>

    <!-- ── Sidebar ────────────────────────────────────────────────── -->
    <div class="space-y-5">

        <!-- Update Status -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                <h2 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-sync-alt text-slate-400 text-sm"></i> Update Status
                </h2>
            </div>
            <div class="px-5 py-4">
                <form method="POST" class="flex flex-col gap-3">
                    <?= csrf_field() ?>
                    <select name="status"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600 bg-white">
                        <option value="Pending"    <?= $order['status']==='Pending'    ?'selected':'' ?>>Pending</option>
                        <option value="Processing" <?= $order['status']==='Processing' ?'selected':'' ?>>Processing</option>
                        <option value="Hold"       <?= $order['status']==='Hold'       ?'selected':'' ?>>Hold</option>
                        <option value="Completed"  <?= $order['status']==='Completed'  ?'selected':'' ?>>Completed</option>
                        <option value="Cancelled"  <?= $order['status']==='Cancelled'  ?'selected':'' ?>>Cancelled</option>
                    </select>
                    <button type="submit"
                            class="w-full bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white font-bold py-2.5 px-4 rounded-xl transition text-sm">
                        <i class="fas fa-check mr-1.5"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                <h2 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-user text-slate-400 text-sm"></i> Customer
                </h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                        <?= strtoupper(substr($order['customer']['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($order['customer']['name']) ?></div>
                        <a href="tel:<?= bd_tel($order['customer']['phone'] ?? '') ?>"
                           class="text-sm font-bold text-brand-600 hover:underline"><?= htmlspecialchars($order['customer']['phone'] ?? '') ?></a>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1.5">Shipping Address</div>
                    <p class="text-sm text-slate-700 bg-slate-50 rounded-xl p-3 border border-slate-100 leading-relaxed">
                        <?= nl2br(htmlspecialchars($order['customer']['address'])) ?>
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm pt-1">
                    <span class="text-slate-500">Payment</span>
                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($order['payment_method']) ?></span>
                </div>

                <?php if(!empty($order['customer']['comment'])): ?>
                <div class="pt-2 border-t border-slate-100">
                    <div class="text-xs font-bold text-amber-500 uppercase tracking-wide mb-1.5">Customer Note</div>
                    <p class="text-sm text-amber-700 bg-amber-50 rounded-xl p-3 border border-amber-100 leading-relaxed">
                        <?= nl2br(htmlspecialchars($order['customer']['comment'])) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pathao Delivery -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h2 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-truck text-amber-500 text-sm"></i> Pathao Delivery
                </h2>
                <?php if ($pathao->isConfigured()): ?>
                    <span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-200">API Ready</span>
                <?php else: ?>
                    <a href="pathao.php" class="text-[10px] font-bold bg-brand-50 text-brand-600 px-2 py-0.5 rounded-full border border-brand-100 hover:bg-brand-100">Setup</a>
                <?php endif; ?>
            </div>
            <div class="px-5 py-4">
                <?php if (!empty($order['pathao_consignment_id'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 mb-3">
                    <p class="text-xs font-bold text-emerald-700 uppercase tracking-wide mb-1">Consignment ID</p>
                    <div class="flex items-center gap-2">
                        <span class="font-black text-emerald-800 text-sm"><?= htmlspecialchars($order['pathao_consignment_id']) ?></span>
                        <button type="button" onclick="copyText('<?= addslashes($order['pathao_consignment_id']) ?>',this)"
                                class="text-emerald-400 hover:text-emerald-600 transition">
                            <i class="fas fa-copy text-xs"></i>
                        </button>
                    </div>
                    <?php if (!empty($order['pathao_status'])): ?>
                    <p class="text-xs text-emerald-600 mt-1">Status: <strong><?= htmlspecialchars($order['pathao_status']) ?></strong></p>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="openPathaoModal('<?= htmlspecialchars($order['id']) ?>')"
                        class="w-full bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 font-bold text-sm py-2 rounded-xl transition">
                    <i class="fas fa-redo text-xs mr-1.5"></i> Re-send to Pathao
                </button>
                <?php elseif ($pathao->isConfigured()): ?>
                <button type="button" onclick="openPathaoModal('<?= htmlspecialchars($order['id']) ?>')"
                        class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-sm py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                    <i class="fas fa-truck"></i> Send to Pathao
                </button>
                <?php else: ?>
                <a href="pathao.php"
                   class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm py-2.5 rounded-xl transition">
                    <i class="fas fa-cog mr-1.5"></i> Configure Pathao First
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Pathao Modal -->
<div id="pathao-modal"
     class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-truck text-amber-500"></i> Send to Pathao
            </h2>
            <button type="button" onclick="closePathaoModal()" class="text-slate-400 hover:text-slate-700 w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold tracking-wide mb-1">Order</p>
                <p id="pm-order-id" class="font-bold text-slate-900 text-sm">#<?= htmlspecialchars($order['id']) ?></p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">City *</label>
                    <select id="pm-city" onchange="pmLoadZones(this.value)"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="">Loading…</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Zone *</label>
                    <select id="pm-zone"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="">— pick city first —</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">
                    Recipient Address *
                    <span class="font-normal text-slate-400 lowercase">(min 10 chars)</span>
                </label>
                <textarea id="pm-address" rows="2"
                          class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                          placeholder="Full delivery address…"><?= htmlspecialchars($order['customer']['address'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Weight (kg)</label>
                    <input type="number" id="pm-weight" value="0.5" min="0.1" step="0.1"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Delivery</label>
                    <select id="pm-delivery-type"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="48">Normal (48h)</option>
                        <option value="12">On-demand (12h)</option>
                    </select>
                </div>
            </div>
            <div id="pm-error" class="hidden text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2"></div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex gap-3">
            <button type="button" onclick="closePathaoModal()"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 rounded-xl text-sm transition">
                Cancel
            </button>
            <button type="button" id="pm-submit" onclick="submitPathaoOrder()"
                    class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 rounded-xl text-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-truck"></i> Send to Pathao
            </button>
        </div>
    </div>
</div>

<script>
var PM_DEFAULT_CITY = <?= (int)($settings['pathao_default_city'] ?? 0) ?>;
var PM_DEFAULT_ZONE = <?= (int)($settings['pathao_default_zone'] ?? 0) ?>;
var PM_STORE_ID     = <?= (int)($settings['pathao_store_id']     ?? 0) ?>;
var PM_CSRF         = '<?= csrf_token() ?>';
var pmOrderId       = '<?= htmlspecialchars($order['id']) ?>';
var pmCitiesLoaded  = false;

function openPathaoModal(orderId) {
    pmOrderId = orderId;
    document.getElementById('pm-error').classList.add('hidden');
    document.getElementById('pm-submit').disabled = false;
    document.getElementById('pm-submit').innerHTML = '<i class="fas fa-truck"></i> Send to Pathao';
    var modal = document.getElementById('pathao-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (!pmCitiesLoaded) { pmCitiesLoaded = true; pmLoadCities(); }
}
function closePathaoModal() {
    var modal = document.getElementById('pathao-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('pathao-modal').addEventListener('click', function(e) {
    if (e.target === this) closePathaoModal();
});

function pmLoadCities() {
    fetch('pathao_api.php?action=get_cities')
        .then(r=>r.json())
        .then(function(data) {
            var sel = document.getElementById('pm-city');
            if (data.error) { sel.innerHTML='<option value="">Error</option>'; return; }
            sel.innerHTML = '<option value="">— select city —</option>';
            (data.cities||[]).forEach(function(c) {
                sel.innerHTML += '<option value="'+c.city_id+'"'+(c.city_id==PM_DEFAULT_CITY?' selected':'')+'>'+c.city_name+'</option>';
            });
            if (PM_DEFAULT_CITY) pmLoadZones(PM_DEFAULT_CITY);
        });
}
function pmLoadZones(cityId) {
    if (!cityId) { document.getElementById('pm-zone').innerHTML='<option value="">— pick city first —</option>'; return; }
    var sel = document.getElementById('pm-zone');
    sel.innerHTML = '<option>Loading…</option>';
    fetch('pathao_api.php?action=get_zones&city_id='+cityId)
        .then(r=>r.json())
        .then(function(data) {
            if (data.error) { sel.innerHTML='<option value="">Error</option>'; return; }
            sel.innerHTML = '<option value="">— select zone —</option>';
            (data.zones||[]).forEach(function(z) {
                sel.innerHTML += '<option value="'+z.zone_id+'"'+(z.zone_id==PM_DEFAULT_ZONE?' selected':'')+'>'+z.zone_name+'</option>';
            });
        });
}
function submitPathaoOrder() {
    var cityId  = document.getElementById('pm-city').value;
    var zoneId  = document.getElementById('pm-zone').value;
    var address = document.getElementById('pm-address').value.trim();
    var weight  = document.getElementById('pm-weight').value;
    var delType = document.getElementById('pm-delivery-type').value;
    var errBox  = document.getElementById('pm-error');
    var btn     = document.getElementById('pm-submit');
    errBox.classList.add('hidden');
    if (!cityId || !zoneId)  { errBox.textContent='Please select city and zone.'; errBox.classList.remove('hidden'); return; }
    if (address.length < 10) { errBox.textContent='Address must be at least 10 characters.'; errBox.classList.remove('hidden'); return; }
    if (!PM_STORE_ID)        { errBox.textContent='Store ID not set — go to Pathao Settings.'; errBox.classList.remove('hidden'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    var body = new FormData();
    body.append('action','send_order'); body.append('csrf_token',PM_CSRF);
    body.append('order_id',pmOrderId); body.append('city_id',cityId);
    body.append('zone_id',zoneId);     body.append('store_id',PM_STORE_ID);
    body.append('item_weight',weight); body.append('delivery_type',delType);
    body.append('address',address);
    fetch('pathao_api.php',{method:'POST',body:body})
        .then(r=>r.json())
        .then(function(data) {
            if (data.error) {
                errBox.textContent=data.error; errBox.classList.remove('hidden');
                btn.disabled=false; btn.innerHTML='<i class="fas fa-truck"></i> Send to Pathao';
            } else {
                closePathaoModal();
                window.location.reload();
            }
        });
}
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var icon = btn.querySelector('i');
        icon.className='fas fa-check text-xs text-emerald-500';
        setTimeout(function(){ icon.className='fas fa-copy text-xs'; }, 1500);
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>
