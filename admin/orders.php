<?php
// Bug #16: bootstrap session + helpers before header.php is loaded
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/functions.php';

$jsonFile     = '../orders.json';
$orders       = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) ?? [] : [];
$settingsFile = '../settings.json';
$settings     = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?? [] : [];

$flashMsg  = '';
$flashType = '';

$validStatuses = ['Pending', 'Processing', 'Completed', 'Cancelled', 'Hold'];

// ── Bulk status update ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_status'], $_POST['order_ids'])) {
    csrf_verify(); // Bug #16
    $newStatus   = $_POST['bulk_status'];
    $selectedIds = (array)$_POST['order_ids'];

    if (in_array($newStatus, $validStatuses) && !empty($selectedIds)) {
        $updated = 0;
        foreach ($orders as &$order) {
            if (in_array($order['id'], $selectedIds)) {
                $order['status'] = $newStatus;
                $updated++;
            }
        }
        unset($order);
        file_put_contents($jsonFile, json_encode($orders, JSON_PRETTY_PRINT));
        $flashMsg  = $updated . ' order' . ($updated !== 1 ? 's' : '') . ' marked as <strong>' . htmlspecialchars($newStatus) . '</strong>.';
        $flashType = 'green';
    } else {
        $flashMsg  = 'Invalid action.';
        $flashType = 'red';
    }
}

// ── Date / status filters ───────────────────────────────────────────────────
// Bug #12: validate date strings to prevent arbitrary string injection
$today  = date('Y-m-d');
// Default to today when landing with no filters at all
$noFiltersSet = !isset($_GET['date_from']) && !isset($_GET['date_to']) && !isset($_GET['status']);
$rawFrom = trim($_GET['date_from'] ?? ($noFiltersSet ? $today : ''));
$rawTo   = trim($_GET['date_to']   ?? ($noFiltersSet ? $today : ''));
$filterDateFrom = (DateTime::createFromFormat('Y-m-d', $rawFrom) !== false) ? $rawFrom : '';
$filterDateTo   = (DateTime::createFromFormat('Y-m-d', $rawTo)   !== false) ? $rawTo   : '';
$filterStatus   = trim($_GET['status'] ?? '');

// Newest first
$ordersDisplay = array_reverse($orders);

if ($filterDateFrom !== '') {
    $ordersDisplay = array_filter($ordersDisplay, fn($o) => substr($o['date'] ?? '', 0, 10) >= $filterDateFrom);
}
if ($filterDateTo !== '') {
    $ordersDisplay = array_filter($ordersDisplay, fn($o) => substr($o['date'] ?? '', 0, 10) <= $filterDateTo);
}
if ($filterStatus !== '' && in_array($filterStatus, $validStatuses)) {
    $ordersDisplay = array_filter($ordersDisplay, fn($o) => ($o['status'] ?? '') === $filterStatus);
}
$ordersDisplay = array_values($ordersDisplay);

$statusColors = [
    'Pending'    => ['pill'=>'bg-amber-100 text-amber-700 border-amber-200', 'dot'=>'bg-amber-400'],
    'Processing' => ['pill'=>'bg-brand-100 text-brand-700 border-brand-200',      'dot'=>'bg-brand-400'],
    'Completed'  => ['pill'=>'bg-emerald-100 text-emerald-700 border-emerald-200',   'dot'=>'bg-emerald-400'],
    'Cancelled'  => ['pill'=>'bg-rose-100 text-rose-700 border-rose-200',         'dot'=>'bg-rose-400'],
    'Hold'       => ['pill'=>'bg-amber-100 text-amber-700 border-amber-200','dot'=>'bg-amber-400'],
];

include 'includes/header.php';
?>

<div class="mb-4 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Orders</h2>
        <p class="text-slate-500 text-sm mt-0.5">
            <?= count($ordersDisplay) ?> order<?= count($ordersDisplay)!==1?'s':'' ?>
            <?= ($filterDateFrom||$filterDateTo||$filterStatus) ? '<span class="text-brand-600 font-semibold">(filtered)</span>' : '' ?>
        </p>
    </div>
</div>

<?php if ($flashMsg): ?>
<div class="mb-4 bg-<?= $flashType ?>-50 border-l-4 border-<?= $flashType ?>-500 text-<?= $flashType ?>-800 text-sm font-medium px-4 py-3 rounded-xl flex items-start gap-2">
    <i class="fas fa-<?= $flashType==='green'?'check':'exclamation' ?>-circle mt-0.5 flex-shrink-0"></i>
    <span><?= $flashMsg ?></span>
</div>
<?php endif; ?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<form method="GET" action="orders.php" id="filter-form"
      class="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[130px]">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">From Date</label>
        <input type="date" name="date_from" id="input-date-from"
               value="<?= htmlspecialchars($filterDateFrom) ?>"
               onchange="document.getElementById('filter-form').submit()"
               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 bg-white">
    </div>
    <div class="flex-1 min-w-[130px]">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">To Date</label>
        <input type="date" name="date_to" id="input-date-to"
               value="<?= htmlspecialchars($filterDateTo) ?>"
               onchange="document.getElementById('filter-form').submit()"
               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 bg-white">
    </div>
    <div class="flex-1 min-w-[120px]">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Status</label>
        <select name="status" onchange="this.form.submit()"
                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 bg-white">
            <option value="">All Statuses</option>
            <?php foreach($validStatuses as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="flex gap-2 flex-wrap">
        <button type="button" onclick="setToday()"
                class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-3 py-2 rounded-xl text-sm transition flex items-center gap-1.5">
            <i class="fas fa-calendar-day text-xs"></i> Today
        </button>
        <a href="orders.php?date_from=&date_to=&status="
           class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-sm transition flex items-center gap-1.5">
            <i class="fas fa-list text-xs"></i> All
        </a>
        <?php if($filterStatus): ?>
        <a href="orders.php?date_from=<?= urlencode($filterDateFrom) ?>&date_to=<?= urlencode($filterDateTo) ?>"
           class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-sm transition flex items-center gap-1.5">
            <i class="fas fa-times text-xs"></i> Clear Status
        </a>
        <?php endif; ?>
    </div>
</form>
<script>
function setToday() {
    var today = new Date().toISOString().slice(0,10);
    document.getElementById('input-date-from').value = today;
    document.getElementById('input-date-to').value   = today;
    document.getElementById('filter-form').submit();
}
</script>

<!-- Bulk action bar -->
<div id="bulk-bar"
     class="hidden sticky top-14 md:top-0 z-30 bg-slate-900 text-white rounded-2xl px-4 py-3 mb-4 shadow-xl">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <span class="font-bold text-sm flex-1">
            <span id="bulk-count">0</span> order<span id="bulk-plural">s</span> selected
        </span>
        <div class="flex items-center gap-2 flex-wrap">
            <select id="bulk-status-select"
                    class="bg-slate-800 border border-slate-600 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-slate-400 flex-1 sm:flex-none">
                <option value="">-- Pick status --</option>
                <?php foreach($validStatuses as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="applyBulk()"
                    class="bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-sm font-bold px-5 py-2 rounded-xl transition">
                Apply
            </button>
            <button type="button" onclick="clearSelection()"
                    class="text-slate-400 hover:text-white text-sm font-bold px-3 py-2 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<!-- Hidden bulk-submit form -->
<form id="bulk-form" method="POST" action="orders.php">
    <?= csrf_field() ?>
    <input type="hidden" name="bulk_status" id="bulk-status-input">
    <div id="bulk-ids-container"></div>
</form>

<!-- ═══ DESKTOP TABLE ═══════════════════════════════════════════════════════ -->
<div class="hidden md:block bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="select-all-desktop"
                               class="w-4 h-4 rounded-lg border-slate-300 cursor-pointer accent-rose-600"
                               onchange="toggleAll(this,'desktop')">
                    </th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Product(s)</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ordersDisplay)): ?>
            <tr>
                <td colspan="8" class="px-4 py-16 text-center text-slate-400">
                    <i class="fas fa-box-open text-5xl mb-3 block text-slate-200"></i>
                    <?= ($filterDateFrom||$filterDateTo||$filterStatus) ? 'No orders match the current filter.' : 'No orders placed yet.' ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($ordersDisplay as $order):
                $sc        = $statusColors[$order['status'] ?? ''] ?? ['pill'=>'bg-slate-100 text-slate-700 border-slate-200'];
                $items     = $order['items'] ?? [];
                $firstImg  = $items[0]['image'] ?? '';
                $extraCount = max(0, count($items) - 1);
                $totalQty   = array_sum(array_column($items, 'qty'));
            ?>
            <tr class="order-row border-b border-slate-100 hover:bg-slate-50 transition" data-id="<?= htmlspecialchars($order['id']) ?>">
                <td class="px-4 py-3">
                    <input type="checkbox" value="<?= htmlspecialchars($order['id']) ?>"
                           class="row-check-desktop row-check w-4 h-4 rounded-lg border-slate-300 cursor-pointer accent-rose-600"
                           onchange="onRowCheck()">
                </td>
                <td class="px-4 py-3">
                    <div class="font-bold text-slate-900 text-sm whitespace-nowrap">#<?= htmlspecialchars($order['id']) ?></div>
                    <?php if (($order['source'] ?? '') === 'landing_page'): ?>
                        <span class="text-[10px] font-bold bg-brand-100 text-brand-700 px-1.5 py-0.5 rounded-full border border-brand-200">Landing</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <?php if ($firstImg): ?>
                            <div class="relative flex-shrink-0">
                                <img src="<?= htmlspecialchars($firstImg) ?>"
                                     class="w-12 h-12 object-cover rounded-xl border border-slate-200 bg-slate-50"
                                     onerror="this.style.display='none'">
                                <?php if ($extraCount > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-slate-700 text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center">+<?= $extraCount ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="w-12 h-12 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-box text-slate-300 text-lg"></i>
                            </div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <?php foreach (array_slice($items, 0, 2) as $item): ?>
                            <div class="text-sm font-medium text-slate-800 truncate max-w-[160px]"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="text-xs text-slate-400">Qty: <?= (int)$item['qty'] ?> · ৳<?= number_format($item['price']) ?></div>
                            <?php endforeach; ?>
                            <?php if ($extraCount > 1): ?><div class="text-xs text-slate-400">+<?= $extraCount-1 ?> more</div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($order['customer']['name'] ?? '—') ?></div>
                    <?php $dp = $order['customer']['phone'] ?? ''; ?>
                    <div class="flex items-center gap-1 mt-0.5">
                        <a href="tel:<?= bd_tel($dp) ?>" class="text-xs text-brand-600 hover:underline"><i class="fas fa-phone-alt mr-1"></i><?= htmlspecialchars($dp) ?></a>
                        <button type="button" onclick="copyText('<?= addslashes(htmlspecialchars($dp)) ?>',this)" title="Copy number"
                                class="text-slate-300 hover:text-brand-500 transition ml-0.5 flex-shrink-0">
                            <i class="fas fa-copy text-[10px]"></i>
                        </button>
                    </div>
                    <?php $addr = $order['customer']['address'] ?? ''; if ($addr): ?>
                    <div class="text-xs text-slate-400 mt-0.5 max-w-[180px] truncate" title="<?= htmlspecialchars($addr) ?>">
                        <i class="fas fa-map-marker-alt text-[9px] mr-0.5"></i><?= htmlspecialchars($addr) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <?php
                    $rawDate  = $order['date'] ?? '';
                    $dateOnly = substr($rawDate, 0, 10);
                    $timeOnly = substr($rawDate, 11, 5);
                    $isToday  = $dateOnly === date('Y-m-d');
                ?>
                <td class="px-4 py-3 whitespace-nowrap">
                    <?php if ($isToday): ?>
                        <span class="inline-block text-[10px] font-black bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full mb-0.5">Today</span><br>
                    <?php endif; ?>
                    <span class="text-xs font-semibold text-slate-700"><?= htmlspecialchars($dateOnly) ?></span>
                    <?php if ($timeOnly): ?>
                        <span class="text-xs text-slate-400 ml-1"><?= htmlspecialchars($timeOnly) ?></span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 font-bold text-slate-900">৳<?= number_format($order['total'] ?? 0) ?></td>
                <td class="px-4 py-3">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold border <?= $sc['pill'] ?>"><?= htmlspecialchars($order['status'] ?? '') ?></span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <?php if (!empty($order['pathao_consignment_id'])): ?>
                        <span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-full whitespace-nowrap">
                            <i class="fas fa-truck mr-0.5"></i><?= htmlspecialchars($order['pathao_consignment_id']) ?>
                        </span>
                        <?php else: ?>
                        <button type="button"
                                onclick="openPathaoModal('<?= htmlspecialchars($order['id']) ?>',this)"
                                data-address="<?= htmlspecialchars($order['customer']['address'] ?? '') ?>"
                                class="inline-flex items-center gap-1 text-[10px] font-bold text-white bg-amber-500 hover:bg-amber-600 px-2.5 py-1 rounded-full transition whitespace-nowrap">
                            <i class="fas fa-truck text-[9px]"></i> Pathao
                        </button>
                        <?php endif; ?>
                        <a href="view_order.php?id=<?= htmlspecialchars($order['id']) ?>"
                           class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-brand-600 transition">
                            Details <i class="fas fa-chevron-right text-[10px]"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ MOBILE CARD LIST ════════════════════════════════════════════════════ -->
<div class="md:hidden space-y-3">

    <?php if (empty($ordersDisplay)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-400">
        <i class="fas fa-box-open text-4xl mb-3 block text-slate-200"></i>
        <?= ($filterDateFrom||$filterDateTo||$filterStatus) ? 'No orders match this filter.' : 'No orders placed yet.' ?>
    </div>
    <?php else: ?>

    <!-- Mobile Select All -->
    <div class="bg-white rounded-2xl border border-slate-200 px-4 py-3 flex items-center justify-between">
        <label class="flex items-center gap-2.5 cursor-pointer text-sm font-semibold text-slate-700">
            <input type="checkbox" id="select-all-mobile"
                   class="w-4 h-4 rounded-lg border-slate-300 accent-rose-600"
                   onchange="toggleAll(this,'mobile')">
            Select All
        </label>
        <span class="text-xs text-slate-400"><?= count($ordersDisplay) ?> orders</span>
    </div>

    <?php foreach ($ordersDisplay as $order):
        $sc        = $statusColors[$order['status'] ?? ''] ?? ['pill'=>'bg-slate-100 text-slate-700'];
        $items     = $order['items'] ?? [];
        $firstImg  = $items[0]['image'] ?? '';
        $totalQty  = array_sum(array_column($items, 'qty'));
        $mPhone    = $order['customer']['phone'] ?? '';
    ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden order-card">
        <!-- Card header -->
        <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
            <input type="checkbox" value="<?= htmlspecialchars($order['id']) ?>"
                   class="row-check-mobile row-check w-4 h-4 rounded-lg border-slate-300 accent-rose-600 flex-shrink-0"
                   onchange="onRowCheck()">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-900 text-sm">#<?= htmlspecialchars($order['id']) ?></span>
                    <?php if (($order['source'] ?? '') === 'landing_page'): ?>
                        <span class="text-[10px] font-bold bg-brand-100 text-brand-700 px-1.5 py-0.5 rounded-full">Landing</span>
                    <?php endif; ?>
                </div>
                <?php
                    $mRawDate  = $order['date'] ?? '';
                    $mDateOnly = substr($mRawDate, 0, 10);
                    $mTimeOnly = substr($mRawDate, 11, 5);
                    $mIsToday  = $mDateOnly === date('Y-m-d');
                ?>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <?php if ($mIsToday): ?>
                        <span class="text-[10px] font-black bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full">Today</span>
                    <?php endif; ?>
                    <span class="text-xs text-slate-400"><?= htmlspecialchars($mDateOnly) ?> <?= htmlspecialchars($mTimeOnly) ?></span>
                </div>
            </div>
            <span class="flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-bold <?= $sc['pill'] ?>">
                <?= htmlspecialchars($order['status'] ?? '') ?>
            </span>
        </div>

        <!-- Card body -->
        <div class="px-4 py-3 flex gap-3">
            <!-- Product image with qty badge -->
            <div class="flex-shrink-0">
                <?php if ($firstImg): ?>
                    <div class="relative">
                        <img src="<?= htmlspecialchars($firstImg) ?>"
                             class="w-16 h-16 object-cover rounded-xl border border-slate-200"
                             onerror="this.parentElement.innerHTML='<div class=\'w-16 h-16 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center\'><i class=\'fas fa-box text-slate-300 text-xl\'></i></div>'">
                        <span class="absolute -top-1 -right-1 bg-emerald-500 text-white text-[10px] font-black min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center"><?= $totalQty ?></span>
                    </div>
                <?php else: ?>
                    <div class="relative w-16 h-16 bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center">
                        <i class="fas fa-box text-slate-300 text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-emerald-500 text-white text-[10px] font-black min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center"><?= $totalQty ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Customer info -->
            <div class="flex-1 min-w-0">
                <div class="text-xs text-slate-500 truncate mb-0.5"><?= htmlspecialchars($items[0]['name'] ?? '—') ?><?php if(count($items)>1): ?> <span class="text-slate-400">+<?= count($items)-1 ?></span><?php endif; ?></div>
                <div class="font-bold text-slate-900 text-sm leading-tight"><?= htmlspecialchars($order['customer']['name'] ?? '—') ?></div>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <a href="tel:<?= bd_tel($mPhone) ?>"
                       onclick="event.stopPropagation()"
                       class="text-sm font-black text-brand-600 leading-tight active:opacity-70">
                        <?= htmlspecialchars($mPhone) ?>
                    </a>
                    <button type="button" onclick="event.stopPropagation();copyText('<?= addslashes(htmlspecialchars($mPhone)) ?>',this)" title="Copy"
                            class="text-slate-300 hover:text-brand-500 active:text-emerald-500 transition">
                        <i class="fas fa-copy text-xs"></i>
                    </button>
                </div>
                <div class="text-xs text-slate-500 mt-0.5 line-clamp-2 leading-snug">
                    <?= htmlspecialchars($order['customer']['address'] ?? '') ?>
                </div>
            </div>

            <!-- Total + action -->
            <div class="flex-shrink-0 flex flex-col items-end justify-between min-w-[70px]">
                <div class="text-right">
                    <div class="text-xs text-slate-400">Total</div>
                    <div class="font-black text-slate-900 text-sm">৳<?= number_format($order['total'] ?? 0) ?></div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Ship: ৳<?= number_format($order['shipping_cost'] ?? 0) ?></div>
                </div>
                <div class="flex flex-col items-end gap-1.5 mt-2">
                    <?php if (!empty($order['pathao_consignment_id'])): ?>
                    <span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-full whitespace-nowrap">
                        <i class="fas fa-truck mr-0.5"></i><?= htmlspecialchars($order['pathao_consignment_id']) ?>
                    </span>
                    <?php else: ?>
                    <button type="button"
                            onclick="openPathaoModal('<?= htmlspecialchars($order['id']) ?>',this)"
                            data-address="<?= htmlspecialchars($order['customer']['address'] ?? '') ?>"
                            class="inline-flex items-center gap-1 text-[10px] font-bold text-white bg-amber-500 active:bg-amber-600 px-2.5 py-1 rounded-full transition">
                        <i class="fas fa-truck text-[9px]"></i> Pathao
                    </button>
                    <?php endif; ?>
                    <a href="view_order.php?id=<?= htmlspecialchars($order['id']) ?>"
                       class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 bg-brand-50 border border-brand-100 px-3 py-1.5 rounded-xl active:bg-brand-100 transition">
                        View <i class="fas fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var icon = btn.querySelector('i');
        icon.className = 'fas fa-check text-[10px] text-emerald-500';
        setTimeout(function(){ icon.className = 'fas fa-copy text-[10px]'; }, 1500);
    });
}
function getChecked() {
    return [...document.querySelectorAll('.row-check:checked')];
}
function onRowCheck() {
    var checked = getChecked();
    var total   = document.querySelectorAll('.row-check').length;
    ['desktop','mobile'].forEach(function(v){
        var sa = document.getElementById('select-all-'+v);
        if(!sa) return;
        sa.indeterminate = checked.length > 0 && checked.length < total;
        sa.checked       = checked.length === total && total > 0;
    });
    updateBulkBar(checked.length);
}
function toggleAll(master, variant) {
    document.querySelectorAll('.row-check-'+variant).forEach(function(cb){ cb.checked = master.checked; });
    var other = document.getElementById('select-all-'+(variant==='mobile'?'desktop':'mobile'));
    if(other) other.checked = master.checked;
    updateBulkBar(master.checked ? document.querySelectorAll('.row-check').length : 0);
}
function clearSelection() {
    document.querySelectorAll('.row-check').forEach(function(cb){ cb.checked = false; });
    ['select-all-desktop','select-all-mobile'].forEach(function(id){
        var el = document.getElementById(id);
        if(el){ el.checked = false; el.indeterminate = false; }
    });
    updateBulkBar(0);
}
function updateBulkBar(count) {
    var bar = document.getElementById('bulk-bar');
    document.getElementById('bulk-count').textContent = count;
    document.getElementById('bulk-plural').textContent = count===1?'':'s';
    if(count>0){ bar.classList.remove('hidden'); bar.classList.add('flex'); }
    else        { bar.classList.add('hidden');    bar.classList.remove('flex'); }
}
function applyBulk() {
    var status = document.getElementById('bulk-status-select').value;
    if(!status){ alert('Please choose a status.'); return; }
    var ids = getChecked().map(function(cb){ return cb.value; });
    if(!ids.length){ alert('No orders selected.'); return; }
    document.getElementById('bulk-status-input').value = status;
    var c = document.getElementById('bulk-ids-container');
    c.innerHTML = '';
    ids.forEach(function(id){
        var inp = document.createElement('input');
        inp.type='hidden'; inp.name='order_ids[]'; inp.value=id;
        c.appendChild(inp);
    });
    document.getElementById('bulk-form').submit();
}
</script>

<!-- ══ Pathao Send Modal ══════════════════════════════════════════════════ -->
<div id="pathao-modal"
     class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">

        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-truck text-amber-500"></i> Send to Pathao
            </h2>
            <button type="button" onclick="closePathaoModal()" class="text-slate-400 hover:text-slate-700 transition w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="px-5 py-4 space-y-4">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold tracking-wide mb-1">Order</p>
                <p id="pm-order-id" class="font-bold text-slate-900 text-sm"></p>
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
                          placeholder="Full delivery address…"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Item Weight (kg)</label>
                    <input type="number" id="pm-weight" value="0.5" min="0.1" step="0.1"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Delivery Type</label>
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
                    class="flex-1 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold py-2.5 rounded-xl text-sm transition flex items-center justify-center gap-2">
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
var pmOrderId       = '';
var pmCitiesLoaded  = false;

function openPathaoModal(orderId, btn) {
    pmOrderId = orderId;
    document.getElementById('pm-order-id').textContent = '#' + orderId;
    document.getElementById('pm-error').classList.add('hidden');
    document.getElementById('pm-submit').disabled = false;
    document.getElementById('pm-submit').innerHTML = '<i class="fas fa-truck"></i> Send to Pathao';
    // Pre-fill address from order data
    var addr = (btn && btn.dataset.address) ? btn.dataset.address : '';
    document.getElementById('pm-address').value = addr;
    var modal = document.getElementById('pathao-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (!pmCitiesLoaded) {
        pmCitiesLoaded = true;
        pmLoadCities();
    }
}
function closePathaoModal() {
    var modal = document.getElementById('pathao-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
// Close on backdrop click
document.getElementById('pathao-modal').addEventListener('click', function(e) {
    if (e.target === this) closePathaoModal();
});

function pmLoadCities() {
    fetch('pathao_api.php?action=get_cities')
        .then(r=>r.json())
        .then(function(data) {
            var sel = document.getElementById('pm-city');
            if (data.error) { sel.innerHTML = '<option value="">Error: ' + data.error + '</option>'; return; }
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
    fetch('pathao_api.php?action=get_zones&city_id=' + cityId)
        .then(r=>r.json())
        .then(function(data) {
            if (data.error) { sel.innerHTML = '<option value="">Error</option>'; return; }
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
    if (!cityId || !zoneId)    { errBox.textContent = 'Please select both city and zone.'; errBox.classList.remove('hidden'); return; }
    if (address.length < 10)   { errBox.textContent = 'Address must be at least 10 characters.'; errBox.classList.remove('hidden'); return; }
    if (!PM_STORE_ID)          { errBox.textContent = 'Store ID not set — configure it in Pathao Settings.'; errBox.classList.remove('hidden'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';

    var body = new FormData();
    body.append('action',        'send_order');
    body.append('csrf_token',    PM_CSRF);
    body.append('order_id',      pmOrderId);
    body.append('city_id',       cityId);
    body.append('zone_id',       zoneId);
    body.append('store_id',      PM_STORE_ID);
    body.append('item_weight',   weight);
    body.append('delivery_type', delType);
    body.append('address',       address);

    fetch('pathao_api.php', { method: 'POST', body: body })
        .then(r=>r.json())
        .then(function(data) {
            if (data.error) {
                errBox.textContent = data.error;
                errBox.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-truck"></i> Send to Pathao';
            } else {
                closePathaoModal();
                // Refresh page to show consignment ID
                window.location.reload();
            }
        })
        .catch(function(e) {
            errBox.textContent = 'Request failed: ' + e.message;
            errBox.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-truck"></i> Send to Pathao';
        });
}
</script>

<?php include 'includes/footer.php'; ?>
