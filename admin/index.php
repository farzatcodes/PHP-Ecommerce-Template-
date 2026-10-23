<?php
$ordersFile   = '../orders.json';
$productsFile = '../products.json';
$landingFile  = '../landing_pages.json';

$orders       = file_exists($ordersFile)   ? json_decode(file_get_contents($ordersFile),   true) ?? [] : [];
$products     = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) ?? [] : [];
$products     = array_values(array_filter($products, fn($p) => empty($p['archived']))); // active only
$landingPages = file_exists($landingFile)  ? json_decode(file_get_contents($landingFile),  true) ?? [] : [];

$totalOrders      = count($orders);
$totalRevenue     = 0;
$pendingOrders    = 0;
$processingOrders = 0;
$completedOrders  = 0;
$cancelledOrders  = 0;
$holdOrders       = 0;
$landingOrders    = 0;
$todayStr         = date('Y-m-d');
$todayOrders      = 0;
$todayRevenue     = 0;

// Last 7 days (oldest -> newest) for the activity chart
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $days[$d] = ['label' => date('D', strtotime($d)), 'count' => 0];
}

foreach ($orders as $order) {
    $status = $order['status'] ?? '';
    if ($status === 'Pending')    $pendingOrders++;
    if ($status === 'Processing') $processingOrders++;
    if ($status === 'Completed')  { $completedOrders++; $totalRevenue += (int)($order['total'] ?? 0); }
    if ($status === 'Cancelled')  $cancelledOrders++;
    if ($status === 'Hold')       $holdOrders++;
    if (($order['source'] ?? '') === 'landing_page') $landingOrders++;
    $day = substr($order['date'] ?? '', 0, 10);
    if ($day === $todayStr) {
        $todayOrders++;
        if ($status === 'Completed') $todayRevenue += (int)($order['total'] ?? 0);
    }
    if (isset($days[$day])) $days[$day]['count']++;
}
$maxDay   = max(1, ...array_column($days, 'count'));
$weekSum  = array_sum(array_column($days, 'count'));

$recentOrders = $orders;
usort($recentOrders, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
$recentOrders = array_slice($recentOrders, 0, 8);

$hour     = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$kpis = [
    ['label'=>'Revenue',        'value'=>'৳' . number_format($totalRevenue), 'hint'=>$completedOrders . ' completed orders', 'icon'=>'fa-sack-dollar', 'chip'=>'bg-emerald-50 text-emerald-600', 'href'=>'orders.php?status=Completed'],
    ['label'=>'Total orders',   'value'=>number_format($totalOrders),        'hint'=>$todayOrders . ' placed today',          'icon'=>'fa-receipt',      'chip'=>'bg-brand-50 text-brand-600',   'href'=>'orders.php'],
    ['label'=>'Needs action',   'value'=>number_format($pendingOrders),      'hint'=>'Pending orders',                         'icon'=>'fa-hourglass-half','chip'=>'bg-amber-50 text-amber-600',     'href'=>'orders.php?status=Pending'],
    ['label'=>'Products',       'value'=>number_format(count($products)),    'hint'=>count($landingPages) . ' landing pages',  'icon'=>'fa-cube',         'chip'=>'bg-slate-100 text-slate-700',  'href'=>'products.php'],
];

$statusData = [
    ['label'=>'Pending',    'count'=>$pendingOrders,    'bar'=>'bg-amber-400'],
    ['label'=>'Processing', 'count'=>$processingOrders, 'bar'=>'bg-sky-400'],
    ['label'=>'Hold',       'count'=>$holdOrders,       'bar'=>'bg-slate-400'],
    ['label'=>'Completed',  'count'=>$completedOrders,  'bar'=>'bg-emerald-500'],
    ['label'=>'Cancelled',  'count'=>$cancelledOrders,  'bar'=>'bg-rose-400'],
];

include 'includes/header.php';
?>

<!-- Greeting -->
<section class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 sm:text-[26px]"><?= $greeting ?>, <?= htmlspecialchars($adminName) ?> <span class="inline-block">👋</span></h2>
        <p class="mt-1 text-sm text-slate-500"><?= date('l, F j') ?> &middot; <?= $todayOrders ?> order<?= $todayOrders === 1 ? '' : 's' ?> today<?= $todayRevenue > 0 ? ' &middot; ৳' . number_format($todayRevenue) . ' completed' : '' ?></p>
    </div>
    <?php if ($pendingOrders > 0): ?>
    <a href="orders.php?status=Pending" class="inline-flex items-center gap-2 self-start rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20 transition hover:bg-amber-100">
        <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span></span>
        <?= $pendingOrders ?> pending order<?= $pendingOrders === 1 ? '' : 's' ?> waiting
        <i class="fas fa-arrow-right text-xs"></i>
    </a>
    <?php endif; ?>
</section>

<!-- KPI cards -->
<section class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
    <?php foreach ($kpis as $k): ?>
    <a href="<?= $k['href'] ?>" class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:p-5">
        <div class="flex items-start justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= $k['label'] ?></p>
            <span class="grid h-9 w-9 place-items-center rounded-xl <?= $k['chip'] ?>"><i class="fas <?= $k['icon'] ?> text-sm"></i></span>
        </div>
        <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-[28px]"><?= $k['value'] ?></p>
        <p class="mt-1 text-xs text-slate-500"><?= $k['hint'] ?></p>
    </a>
    <?php endforeach; ?>
</section>

<!-- Activity + status -->
<section class="mb-6 grid gap-4 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm lg:col-span-2">
        <div class="mb-5 flex items-start justify-between">
            <div>
                <h3 class="font-semibold text-slate-900">Orders — last 7 days</h3>
                <p class="mt-0.5 text-xs text-slate-500"><?= $weekSum ?> order<?= $weekSum === 1 ? '' : 's' ?> this week</p>
            </div>
            <a href="orders.php" class="text-xs font-semibold text-brand-600 hover:text-brand-800">All orders <i class="fas fa-arrow-right text-[10px]"></i></a>
        </div>
        <?php if ($weekSum === 0): ?>
        <p class="mb-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500"><i class="fas fa-circle-info mr-1.5 text-slate-400"></i>No orders in the last 7 days — bars will fill in as new orders arrive.</p>
        <?php endif; ?>
        <div class="flex h-40 items-end gap-2 sm:gap-4 sm:h-44" role="img" aria-label="Bar chart of orders per day for the last seven days">
            <?php foreach ($days as $date => $d):
                $h = $d['count'] > 0 ? max(8, round(($d['count'] / $maxDay) * 100)) : 3;
                $isToday = $date === $todayStr;
            ?>
            <div class="flex h-full flex-1 flex-col items-center justify-end gap-2" title="<?= date('D, j M', strtotime($date)) ?>: <?= $d['count'] ?> orders">
                <span class="text-xs font-semibold <?= $d['count'] ? 'text-slate-700' : 'text-slate-300' ?>"><?= $d['count'] ?></span>
                <div class="w-full max-w-[44px] rounded-t-lg <?= $isToday ? 'bg-brand-600' : ($d['count'] ? 'bg-brand-200' : 'bg-slate-100') ?> transition-all" style="height:<?= $h ?>%"></div>
                <span class="text-[11px] font-medium <?= $isToday ? 'text-brand-600' : 'text-slate-400' ?>"><?= $d['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <h3 class="font-semibold text-slate-900">Order status</h3>
        <p class="mt-0.5 text-xs text-slate-500">Share of all <?= $totalOrders ?> orders</p>
        <div class="mt-5 flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
            <?php foreach ($statusData as $s): $pct = $totalOrders > 0 ? ($s['count'] / $totalOrders) * 100 : 0; if ($pct <= 0) continue; ?>
                <div class="<?= $s['bar'] ?>" style="width:<?= round($pct, 2) ?>%" title="<?= $s['label'] ?>: <?= $s['count'] ?>"></div>
            <?php endforeach; ?>
        </div>
        <ul class="mt-5 space-y-1">
            <?php foreach ($statusData as $s): $pct = $totalOrders > 0 ? round(($s['count'] / $totalOrders) * 100) : 0; ?>
            <li>
                <a href="orders.php?status=<?= urlencode($s['label']) ?>" class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm transition hover:bg-slate-50">
                    <span class="h-2.5 w-2.5 rounded-full <?= $s['bar'] ?>"></span>
                    <span class="flex-1 text-slate-600"><?= $s['label'] ?></span>
                    <span class="font-semibold text-slate-900"><?= $s['count'] ?></span>
                    <span class="w-9 text-right text-xs text-slate-400"><?= $pct ?>%</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- Recent orders + side column -->
<section class="grid gap-4 lg:grid-cols-3">
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-900">Recent orders</h3>
                <p class="mt-0.5 text-xs text-slate-500">Latest <?= count($recentOrders) ?> placed</p>
            </div>
            <a href="orders.php" class="text-xs font-semibold text-brand-600 hover:text-brand-800">View all <i class="fas fa-arrow-right text-[10px]"></i></a>
        </div>

        <?php if (empty($recentOrders)): ?>
        <div class="px-5 py-14 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400"><i class="fas fa-inbox text-xl"></i></span>
            <p class="mt-3 text-sm font-medium text-slate-700">No orders yet</p>
            <p class="text-xs text-slate-500">New orders will show up here.</p>
        </div>
        <?php else: ?>

        <!-- Desktop table -->
        <div class="hidden overflow-x-auto md:block thin-scroll">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Total</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($recentOrders as $order): $p = $order['customer']['phone'] ?? ''; ?>
                    <tr class="transition hover:bg-slate-50/70">
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-slate-900">#<?= htmlspecialchars($order['id']) ?></p>
                            <p class="whitespace-nowrap text-xs text-slate-400"><?= htmlspecialchars($order['date'] ?? '') ?></p>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($order['customer']['name'] ?? '—') ?></p>
                            <?php if ($p): ?><a href="tel:<?= bd_tel($p) ?>" class="text-xs text-brand-600 hover:underline"><?= htmlspecialchars($p) ?></a><?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5 font-semibold text-slate-900">৳<?= number_format($order['total'] ?? 0) ?></td>
                        <td class="px-5 py-3.5">
                            <?= status_pill($order['status'] ?? '') ?>
                            <?php if (($order['source'] ?? '') === 'landing_page'): ?>
                                <span class="ml-1 rounded-md bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700">Landing</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="view_order.php?id=<?= htmlspecialchars($order['id']) ?>" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">View <i class="fas fa-chevron-right text-[9px]"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile list -->
        <ul class="divide-y divide-slate-100 md:hidden">
        <?php foreach ($recentOrders as $order): $p = $order['customer']['phone'] ?? ''; ?>
            <li>
                <a href="view_order.php?id=<?= htmlspecialchars($order['id']) ?>" class="flex items-center gap-3 px-4 py-3.5 active:bg-slate-50">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500"><i class="fas fa-receipt text-sm"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">#<?= htmlspecialchars($order['id']) ?> · <?= htmlspecialchars($order['customer']['name'] ?? '—') ?></p>
                        <p class="truncate text-xs text-slate-400"><?= htmlspecialchars($order['date'] ?? '') ?></p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-bold text-slate-900">৳<?= number_format($order['total'] ?? 0) ?></p>
                        <div class="mt-1"><?= status_pill($order['status'] ?? '') ?></div>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold text-slate-900">Quick actions</h3>
            <div class="grid gap-1.5">
                <?php $qas = [
                    ['href'=>'orders.php',        'icon'=>'fa-receipt', 'chip'=>'bg-amber-50 text-amber-600',   'title'=>'Review orders',    'sub'=>$pendingOrders . ' pending'],
                    ['href'=>'add_product.php',   'icon'=>'fa-plus',    'chip'=>'bg-brand-50 text-brand-600', 'title'=>'Add a product',    'sub'=>count($products) . ' in catalogue'],
                    ['href'=>'manage_landing.php','icon'=>'fa-rocket',  'chip'=>'bg-brand-50 text-brand-600', 'title'=>'New landing page', 'sub'=>count($landingPages) . ' live'],
                    ['href'=>'pixel.php',         'icon'=>'fa-crosshairs','chip'=>'bg-slate-100 text-slate-600','title'=>'Tracking & pixels','sub'=>'Analytics setup'],
                ];
                foreach ($qas as $qa): ?>
                <a href="<?= $qa['href'] ?>" class="group flex items-center gap-3 rounded-xl p-2 transition hover:bg-slate-50">
                    <span class="grid h-9 w-9 place-items-center rounded-lg <?= $qa['chip'] ?>"><i class="fas <?= $qa['icon'] ?> text-xs"></i></span>
                    <span class="min-w-0 flex-1 leading-tight">
                        <span class="block text-sm font-semibold text-slate-800"><?= $qa['title'] ?></span>
                        <span class="block text-xs text-slate-500"><?= $qa['sub'] ?></span>
                    </span>
                    <i class="fas fa-chevron-right text-[10px] text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Landing pages</h3>
                <a href="landing_pages.php" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Manage</a>
            </div>
            <?php if (empty($landingPages)): ?>
                <div class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center">
                    <p class="text-sm text-slate-500">No landing pages yet.</p>
                    <a href="manage_landing.php" class="mt-1 inline-block text-sm font-semibold text-brand-600 hover:text-brand-800">Create one →</a>
                </div>
            <?php else: ?>
                <ul class="space-y-1.5">
                <?php foreach (array_slice($landingPages, 0, 4) as $lp): ?>
                    <li class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800"><?= htmlspecialchars($lp['page_title'] ?? '') ?></p>
                            <p class="truncate font-mono text-[11px] text-slate-400">/landing/<?= htmlspecialchars($lp['slug'] ?? '') ?></p>
                        </div>
                        <a href="/landing/<?= htmlspecialchars($lp['slug'] ?? '') ?>/" target="_blank" rel="noopener" aria-label="Open page" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-white hover:text-brand-600 hover:shadow-sm"><i class="fas fa-arrow-up-right-from-square text-xs"></i></a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
