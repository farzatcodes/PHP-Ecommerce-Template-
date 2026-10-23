<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // Try remember-me cookie before redirecting to login
    if (!empty($_COOKIE['admin_remember'])) {
        $parts = explode(':', $_COOKIE['admin_remember'], 2);
        if (count($parts) === 2) {
            [$cookieAdminId, $cookieRawToken] = $parts;
            require_once __DIR__ . '/../db.php';
            $stmt = $pdo->prepare("SELECT id, username, remember_token, remember_expires FROM `admins` WHERE id = ?");
            $stmt->execute([(int)$cookieAdminId]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin
                && !empty($admin['remember_token'])
                && hash_equals($admin['remember_token'], hash('sha256', $cookieRawToken))
                && strtotime($admin['remember_expires'] ?? '0') > time()
            ) {
                $_SESSION['admin_logged_in']  = true;
                $_SESSION['admin_id']         = $admin['id'];
                $_SESSION['admin_username']   = $admin['username'];
            } else {
                setcookie('admin_remember', '', ['expires' => time() - 3600, 'path' => '/']);
                header('Location: login.php'); exit;
            }
        } else {
            setcookie('admin_remember', '', ['expires' => time() - 3600, 'path' => '/']);
            header('Location: login.php'); exit;
        }
    } else {
        header('Location: login.php');
        exit;
    }
}
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);

function navActive($pages) {
    global $currentPage;
    return in_array($currentPage, (array)$pages);
}
function pageTitle() {
    global $currentPage;
    $map = [
        'index.php'         => 'Dashboard',
        'orders.php'        => 'Orders',
        'view_order.php'    => 'Order Details',
        'products.php'      => 'Products',
        'add_product.php'   => 'Add Product',
        'edit_product.php'  => 'Edit Product',
        'categories.php'    => 'Categories',
        'landing_pages.php' => 'Landing Pages',
        'manage_landing.php'=> 'Landing Editor',
        'customers.php'     => 'Customers',
        'employees.php'     => 'Employees',
        'pixel.php'         => 'Pixel & Tracking',
        'pathao.php'        => 'Pathao Courier',
    ];
    return $map[$currentPage] ?? 'Admin';
}
function pageSubtitle() {
    global $currentPage;
    $map = [
        'index.php'         => 'Your store at a glance',
        'orders.php'        => 'Track, filter and fulfil customer orders',
        'view_order.php'    => 'Order summary, customer and shipment',
        'products.php'      => 'Everything in your catalogue',
        'add_product.php'   => 'Create a new product listing',
        'edit_product.php'  => 'Update product details',
        'categories.php'    => 'Organise how products are grouped',
        'landing_pages.php' => 'Single-product sales pages',
        'manage_landing.php'=> 'Build and publish a landing page',
        'customers.php'     => 'People who have ordered from you',
        'employees.php'     => 'Team members and access',
        'pixel.php'         => 'Analytics and ad tracking codes',
        'pathao.php'        => 'Courier integration settings',
    ];
    return $map[$currentPage] ?? '';
}

// Pending-order badge for the sidebar (cheap: skipped if the file is huge)
$navPending = 0;
$ordersPath = __DIR__ . '/../../orders.json';
if (is_file($ordersPath) && filesize($ordersPath) < 8 * 1024 * 1024) {
    $navOrders = json_decode(file_get_contents($ordersPath), true);
    if (is_array($navOrders)) {
        foreach ($navOrders as $o) { if (($o['status'] ?? '') === 'Pending') $navPending++; }
    }
}

$adminName    = $_SESSION['admin_username'] ?? 'Admin';
$adminInitial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($adminName, 0, 1)) : strtoupper(substr($adminName, 0, 1));

// Grouped navigation: section label => items
$navGroups = [
    'Overview' => [
        ['href'=>'index.php',         'icon'=>'fa-chart-pie',     'label'=>'Dashboard',       'pages'=>['index.php']],
    ],
    'Sales' => [
        ['href'=>'orders.php',        'icon'=>'fa-receipt',       'label'=>'Orders',          'pages'=>['orders.php','view_order.php'], 'badge'=>$navPending],
        ['href'=>'customers.php',     'icon'=>'fa-user-group',    'label'=>'Customers',       'pages'=>['customers.php']],
    ],
    'Catalogue' => [
        ['href'=>'products.php',      'icon'=>'fa-cube',          'label'=>'Products',        'pages'=>['products.php','add_product.php','edit_product.php']],
        ['href'=>'categories.php',    'icon'=>'fa-folder-tree',   'label'=>'Categories',      'pages'=>['categories.php']],
    ],
    'Marketing' => [
        ['href'=>'landing_pages.php', 'icon'=>'fa-rocket',        'label'=>'Landing pages',   'pages'=>['landing_pages.php','manage_landing.php']],
        ['href'=>'pixel.php',         'icon'=>'fa-crosshairs',    'label'=>'Pixel & tracking','pages'=>['pixel.php']],
    ],
    'Operations' => [
        ['href'=>'pathao.php',        'icon'=>'fa-truck-fast',    'label'=>'Pathao courier',  'pages'=>['pathao.php']],
        ['href'=>'employees.php',     'icon'=>'fa-id-badge',      'label'=>'Employees',       'pages'=>['employees.php']],
    ],
];

function renderNav(array $groups, bool $closeOnClick = false): void {
    foreach ($groups as $label => $items) {
        echo '<div class="mb-5"><p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">' . htmlspecialchars($label) . '</p><ul class="space-y-0.5">';
        foreach ($items as $it) {
            $active = navActive($it['pages']);
            $cls = $active
                ? 'bg-brand-50 text-brand-700 font-semibold'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 font-medium';
            $iconCls = $active ? 'text-brand-600' : 'text-slate-400 group-hover:text-slate-600';
            echo '<li><a href="' . $it['href'] . '"' . ($closeOnClick ? ' onclick="closeSidebarFn()"' : '')
               . ($active ? ' aria-current="page"' : '')
               . ' class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors ' . $cls . '">'
               . '<i class="fas ' . $it['icon'] . ' w-5 text-center text-[15px] ' . $iconCls . '"></i>'
               . '<span class="flex-1">' . htmlspecialchars($it['label']) . '</span>';
            if (!empty($it['badge'])) {
                echo '<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">' . (int)$it['badge'] . '</span>';
            }
            echo '</a></li>';
        }
        echo '</ul></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= pageTitle() ?> — ThirstyBD Admin</title>
    <meta name="theme-color" content="#0A0A0A">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/branding/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="../assets/branding/favicon-192.png">
    <link rel="apple-touch-icon" href="../assets/branding/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: {
        fontFamily: { sans: ['Inter','ui-sans-serif','system-ui','sans-serif'] },
        colors: {
            brand: { 50:'#FFF1F5',100:'#FFE1EA',200:'#FFC2D4',300:'#FF8FB0',400:'#FF5A8F',500:'#F5106C',600:'#DB1456',700:'#B80E43',800:'#8F0B35',900:'#660826' },
            peach: '#F8A996', cream: { DEFAULT:'#F9EFEA', soft:'#FBF6F2' }, ink: { DEFAULT:'#0A0A0A', soft:'#161616' }
        }
    } } };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { color-scheme: light; }
        * { -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; letter-spacing: -0.003em; }
        h1, h2, h3 { letter-spacing: -0.015em; }
        :focus-visible { outline: 2px solid #DB1456; outline-offset: 2px; }
        ::selection { background: #FFC2D4; color: #660826; }
        .sidebar-drawer { transition: transform 0.28s cubic-bezier(.4,0,.2,1); }
        .overlay-bg     { transition: opacity 0.28s ease; }
        .thin-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
        .thin-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .thin-scroll::-webkit-scrollbar-track { background: transparent; }
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        @media (prefers-reduced-motion: reduce) { * { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; scroll-behavior: auto !important; } }

        /* ── Shared form components (used by product / category / landing forms) ── */
        .wp-card {
            background: #fff;
            border: 1px solid rgb(226 232 240 / 0.9);
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
        }
        .wp-input {
            display: block;
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            padding: 0.575rem 0.85rem;
            font-size: 0.875rem;
            color: #0f172a;
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .wp-input::placeholder { color: #94a3b8; }
        .wp-input:hover { border-color: #94a3b8; }
        .wp-input:focus {
            outline: none;
            border-color: #F5106C;
            box-shadow: 0 0 0 4px rgb(245 16 108 / 0.22);
        }
        .wp-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: #DB1456;
            color: #fff;
            border-radius: 0.625rem;
            font-weight: 600;
            transition: background-color .15s, box-shadow .15s, transform .15s;
            cursor: pointer;
            border: none;
            box-shadow: 0 1px 2px rgb(219 20 86 / 0.35);
        }
        .wp-button:hover { background: #B80E43; box-shadow: 0 4px 12px rgb(219 20 86 / 0.30); }
        .wp-button:active { transform: translateY(1px); }

        /* Description rich-text editor */
        .desc-tool-btn {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 0.5rem; color: #475569; font-size: 0.8rem;
            cursor: pointer; border: none; background: transparent;
            transition: background-color .15s, color .15s;
        }
        .desc-tool-btn:hover { background: #DB1456; color: #fff; }
        #descEditor { min-height: 12rem; max-height: 28rem; overflow-y: auto; }
        #descEditor img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 0.5rem 0; }
        #descEditor:empty:before { content: attr(data-placeholder); color: #94a3b8; }
    </style>
</head>
<body class="bg-cream-soft text-slate-800 min-h-screen">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-semibold focus:shadow-lg">Skip to content</a>

<!-- ═══ DESKTOP SIDEBAR ═══════════════════════════════════════════════ -->
<aside class="hidden lg:flex w-64 flex-col fixed inset-y-0 left-0 z-40 bg-white border-r border-slate-200">
    <a href="index.php" class="h-16 flex items-center gap-3 px-5 bg-ink border-b border-white/10 border-r border-r-white/10" aria-label="ThirstyBD admin home">
        <img src="../assets/branding/logo-wordmark.png" alt="ThirstyBD" width="560" height="112" class="h-7 w-auto">
        <span class="rounded-md bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white/70">Admin</span>
    </a>
    <nav class="flex-1 overflow-y-auto thin-scroll px-3 py-5" aria-label="Main">
        <?php renderNav($navGroups); ?>
    </nav>
    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-2.5">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700"><?= htmlspecialchars($adminInitial) ?></span>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-sm font-semibold text-slate-800"><?= htmlspecialchars($adminName) ?></p>
                <p class="text-[11px] text-slate-400">Administrator</p>
            </div>
            <a href="logout.php" title="Sign out" aria-label="Sign out" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-white hover:text-rose-600 hover:shadow-sm">
                <i class="fas fa-arrow-right-from-bracket text-sm"></i>
            </a>
        </div>
    </div>
</aside>

<!-- ═══ MOBILE DRAWER ═════════════════════════════════════════════════ -->
<div id="sidebarOverlay" class="overlay-bg fixed inset-0 z-50 hidden bg-slate-900/50 opacity-0 backdrop-blur-sm lg:hidden"></div>
<aside id="sidebar" class="sidebar-drawer fixed left-0 top-0 z-50 flex h-full w-72 -translate-x-full flex-col bg-white shadow-2xl lg:hidden" aria-label="Menu">
    <div class="flex h-16 items-center justify-between bg-ink border-b border-white/10 px-4">
        <img src="../assets/branding/logo-wordmark.png" alt="ThirstyBD" width="560" height="112" class="h-7 w-auto">
        <button id="closeSidebar" aria-label="Close menu" class="grid h-10 w-10 place-items-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white"><i class="fas fa-xmark"></i></button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4 thin-scroll"><?php renderNav($navGroups, true); ?></nav>
    <div class="border-t border-slate-100 p-3">
        <a href="logout.php" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-rose-50 hover:text-rose-600">
            <i class="fas fa-arrow-right-from-bracket w-5 text-center"></i> Sign out
        </a>
    </div>
</aside>

<!-- ═══ MAIN ══════════════════════════════════════════════════════════ -->
<div class="lg:pl-64 min-h-screen flex flex-col">

    <!-- Top bar -->
    <header class="sticky top-0 z-30 border-b border-white/10 bg-ink/95 backdrop-blur-md text-white">
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
            <button id="openSidebar" aria-label="Open menu" class="grid h-11 w-11 place-items-center rounded-lg text-slate-200 hover:bg-white/10 lg:hidden">
                <i class="fas fa-bars text-lg"></i>
            </button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold text-white sm:text-xl"><?= pageTitle() ?></h1>
                <?php if (pageSubtitle() !== ''): ?><p class="hidden truncate text-xs text-white/55 sm:block"><?= pageSubtitle() ?></p><?php endif; ?>
            </div>

            <a href="../index.php" target="_blank" rel="noopener" class="hidden items-center gap-2 rounded-lg border border-white/15 bg-white/5 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-white/30 hover:bg-white/10 sm:inline-flex">
                <i class="fas fa-arrow-up-right-from-square text-xs text-white/50"></i> View store
            </a>

            <details class="relative">
                <summary class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:bg-brand-700">
                    <i class="fas fa-plus text-xs"></i><span class="hidden sm:inline">Create</span>
                </summary>
                <div class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl">
                    <a href="add_product.php" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><i class="fas fa-cube w-4 text-center text-brand-500"></i> New product</a>
                    <a href="manage_landing.php" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><i class="fas fa-rocket w-4 text-center text-brand-500"></i> New landing page</a>
                    <a href="categories.php" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><i class="fas fa-folder-tree w-4 text-center text-emerald-500"></i> Manage categories</a>
                </div>
            </details>
        </div>
    </header>

    <!-- Page content -->
    <main id="main" class="flex-1 px-4 py-6 pb-28 sm:px-6 lg:px-8 lg:pb-10">
