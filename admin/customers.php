<?php
// CSV export — must happen before any output
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    include_once 'includes/functions.php';
    $ordersFile = '../orders.json';
    $orders     = file_exists($ordersFile) ? json_decode(file_get_contents($ordersFile), true) ?? [] : [];

    $customerMap = [];
    foreach ($orders as $order) {
        $phone   = trim($order['customer']['phone'] ?? '');
        $name    = trim($order['customer']['name']  ?? '');
        $address = trim($order['customer']['address'] ?? '');
        $total   = (int)($order['total'] ?? 0);
        $date    = $order['date'] ?? '';
        $status  = $order['status'] ?? '';
        if ($phone === '') continue;
        $key = bd_tel($phone) ?: $phone;
        if (!isset($customerMap[$key])) {
            $customerMap[$key] = ['phone'=>$phone,'name'=>$name,'address'=>$address,'orders'=>0,'spent'=>0,'last_order'=>$date,'last_status'=>$status];
        }
        $customerMap[$key]['orders']++;
        $customerMap[$key]['spent'] += $total;
        if ($date > $customerMap[$key]['last_order']) {
            $customerMap[$key]['last_order']  = $date;
            $customerMap[$key]['last_status'] = $status;
            $customerMap[$key]['name']    = $name    ?: $customerMap[$key]['name'];
            $customerMap[$key]['address'] = $address ?: $customerMap[$key]['address'];
        }
    }
    uasort($customerMap, fn($a,$b) => strcmp($b['last_order'], $a['last_order']));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="customers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, ['Name', 'Phone', 'Address', 'Orders', 'Total Spent (BDT)', 'Last Order Date', 'Last Status']);
    foreach ($customerMap as $c) {
        fputcsv($out, [
            $c['name'],
            $c['phone'],
            $c['address'],
            $c['orders'],
            $c['spent'],
            substr($c['last_order'], 0, 16),
            $c['last_status'],
        ]);
    }
    fclose($out);
    exit;
}

include_once 'includes/header.php';

$ordersFile = '../orders.json';
$orders     = file_exists($ordersFile) ? json_decode(file_get_contents($ordersFile), true) ?? [] : [];

// Build customer map keyed by phone
$customerMap = [];
foreach ($orders as $order) {
    $phone   = trim($order['customer']['phone'] ?? '');
    $name    = trim($order['customer']['name']  ?? '');
    $address = trim($order['customer']['address'] ?? '');
    $total   = (int)($order['total'] ?? 0);
    $date    = $order['date'] ?? '';
    $status  = $order['status'] ?? '';

    if ($phone === '') continue;

    // Bug #13: normalize phone to +880 form so variants of the same
    // number (01XXXXXXXXX vs +8801XXXXXXXXX) don't create duplicate entries
    $normalizedPhone = bd_tel($phone);
    $key = $normalizedPhone ?: $phone;

    if (!isset($customerMap[$key])) {
        $customerMap[$key] = [
            'phone'       => $phone,
            'name'        => $name,
            'address'     => $address,
            'orders'      => 0,
            'spent'       => 0,
            'last_order'  => $date,
            'last_status' => $status,
        ];
    }
    $customerMap[$key]['orders']++;
    $customerMap[$key]['spent'] += $total;
    if ($date > $customerMap[$key]['last_order']) {
        $customerMap[$key]['last_order']  = $date;
        $customerMap[$key]['last_status'] = $status;
        $customerMap[$key]['name']        = $name ?: $customerMap[$key]['name'];
        $customerMap[$key]['address']     = $address ?: $customerMap[$key]['address'];
    }
}

// Sort by last order date desc
uasort($customerMap, fn($a,$b) => strcmp($b['last_order'], $a['last_order']));

$totalCustomers   = count($customerMap);
$repeatCustomers  = count(array_filter($customerMap, fn($c) => $c['orders'] > 1));
?>

<div class="mb-5 flex items-center justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Customers</h2>
        <p class="text-slate-500 text-sm mt-1"><?= $totalCustomers ?> unique customers from orders</p>
    </div>
    <?php if ($totalCustomers > 0): ?>
    <a href="customers.php?export=csv"
       class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold px-4 py-2.5 rounded-xl text-sm transition shadow-sm flex-shrink-0">
        <i class="fas fa-file-csv text-sm"></i>
        <span class="hidden sm:inline">Export CSV</span>
        <span class="sm:hidden">CSV</span>
    </a>
    <?php endif; ?>
</div>

<!-- Stats row -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-5">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
        <div class="text-2xl font-black text-teal-600"><?= $totalCustomers ?></div>
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1">Total Customers</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
        <div class="text-2xl font-black text-brand-600"><?= $repeatCustomers ?></div>
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1">Repeat Buyers</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center col-span-2 md:col-span-1">
        <div class="text-2xl font-black text-brand-600"><?= count($orders) ?></div>
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1">Total Orders</div>
    </div>
</div>

<?php if (empty($customerMap)): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
    <i class="fas fa-users text-5xl mb-3 block text-slate-200"></i>
    No customers yet — orders will populate this list.
</div>
<?php else: ?>

<!-- ── DESKTOP TABLE ──────────────────────────────────────────────── -->
<div class="hidden md:block bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <th class="px-5 py-3 font-bold">Customer</th>
                    <th class="px-5 py-3 font-bold">Phone</th>
                    <th class="px-5 py-3 font-bold">Address</th>
                    <th class="px-5 py-3 font-bold">Orders</th>
                    <th class="px-5 py-3 font-bold">Total Spent</th>
                    <th class="px-5 py-3 font-bold">Last Order</th>
                    <th class="px-5 py-3 font-bold">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($customerMap as $c):
                $statusColors = [
                    'Pending'    => 'bg-amber-100 text-amber-700',
                    'Processing' => 'bg-brand-100 text-brand-700',
                    'Completed'  => 'bg-emerald-100 text-emerald-700',
                    'Cancelled'  => 'bg-rose-100 text-rose-700',
                ];
                $sc = $statusColors[$c['last_status']] ?? 'bg-slate-100 text-slate-600';
            ?>
            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                            <?= strtoupper(mb_substr($c['name'] ?: '?', 0, 1)) ?>
                        </div>
                        <span class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($c['name'] ?: '—') ?></span>
                    </div>
                </td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-1.5">
                        <a href="tel:<?= bd_tel($c['phone']) ?>" class="text-sm font-bold text-brand-600 hover:underline">
                            <i class="fas fa-phone-alt text-xs mr-1"></i><?= htmlspecialchars($c['phone']) ?>
                        </a>
                        <button type="button" onclick="copyPhone('<?= addslashes(htmlspecialchars($c['phone'])) ?>',this)" title="Copy number"
                                class="text-slate-300 hover:text-brand-500 transition">
                            <i class="fas fa-copy text-xs"></i>
                        </button>
                    </div>
                </td>
                <td class="px-5 py-3.5 text-sm text-slate-500 max-w-[200px]">
                    <div class="truncate" title="<?= htmlspecialchars($c['address']) ?>"><?= htmlspecialchars($c['address'] ?: '—') ?></div>
                </td>
                <td class="px-5 py-3.5">
                    <span class="font-bold text-slate-900"><?= $c['orders'] ?></span>
                    <?php if($c['orders'] > 1): ?>
                        <span class="ml-1 text-xs font-bold text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded-full">Repeat</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3.5 font-bold text-slate-900">৳<?= number_format($c['spent']) ?></td>
                <td class="px-5 py-3.5 text-xs text-slate-500"><?= htmlspecialchars(substr($c['last_order'], 0, 16)) ?></td>
                <td class="px-5 py-3.5">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $sc ?>"><?= htmlspecialchars($c['last_status']) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── MOBILE CARD LIST ───────────────────────────────────────────── -->
<div class="md:hidden space-y-3">
<?php foreach ($customerMap as $c):
    $statusColors = [
        'Pending'    => 'bg-amber-100 text-amber-700',
        'Processing' => 'bg-brand-100 text-brand-700',
        'Completed'  => 'bg-emerald-100 text-emerald-700',
        'Cancelled'  => 'bg-rose-100 text-rose-700',
    ];
    $sc = $statusColors[$c['last_status']] ?? 'bg-slate-100 text-slate-600';
?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="flex items-center gap-3 px-4 py-3.5">
        <!-- Avatar -->
        <div class="w-11 h-11 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-black text-base flex-shrink-0">
            <?= strtoupper(mb_substr($c['name'] ?: '?', 0, 1)) ?>
        </div>
        <!-- Info -->
        <div class="flex-1 min-w-0">
            <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($c['name'] ?: '—') ?></div>
            <div class="flex items-center gap-1.5 mt-0.5">
                <a href="tel:<?= bd_tel($c['phone']) ?>"
                   class="text-sm font-black text-brand-600">
                    <i class="fas fa-phone-alt text-xs mr-1"></i><?= htmlspecialchars($c['phone']) ?>
                </a>
                <button type="button" onclick="copyPhone('<?= addslashes(htmlspecialchars($c['phone'])) ?>',this)" title="Copy"
                        class="text-slate-300 hover:text-brand-500 active:text-emerald-500 transition">
                    <i class="fas fa-copy text-xs"></i>
                </button>
            </div>
            <?php if ($c['address']): ?>
            <div class="text-xs text-slate-400 mt-0.5 line-clamp-1"><?= htmlspecialchars($c['address']) ?></div>
            <?php endif; ?>
        </div>
        <!-- Stats -->
        <div class="flex-shrink-0 text-right">
            <div class="font-black text-slate-900 text-sm">৳<?= number_format($c['spent']) ?></div>
            <div class="text-xs text-slate-400"><?= $c['orders'] ?> order<?= $c['orders']!==1?'s':'' ?></div>
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full <?= $sc ?> mt-0.5 inline-block"><?= $c['last_status'] ?></span>
        </div>
    </div>
    <?php if($c['orders'] > 1): ?>
    <div class="border-t border-slate-100 px-4 py-2 bg-brand-50">
        <span class="text-xs font-bold text-brand-600"><i class="fas fa-star text-[10px] mr-1"></i>Repeat customer — <?= $c['orders'] ?> orders</span>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<script>
function copyPhone(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var icon = btn.querySelector('i');
        icon.className = 'fas fa-check text-xs text-emerald-500';
        setTimeout(function(){ icon.className = 'fas fa-copy text-xs'; }, 1500);
    });
}
</script>

<?php include_once 'includes/footer.php'; ?>
