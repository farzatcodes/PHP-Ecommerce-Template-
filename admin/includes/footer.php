    </main>

    <footer class="hidden px-8 pb-6 text-xs text-slate-400 lg:block">
        &copy; <?= date('Y') ?> ThirstyBD &middot; Admin console
    </footer>

    <!-- ═══ MOBILE BOTTOM NAV ════════════════════════════════════════ -->
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden" aria-label="Quick navigation">
        <div class="mx-auto flex max-w-xl">
            <?php
            $btm = [
                ['href'=>'index.php',     'icon'=>'fa-chart-pie',  'label'=>'Home',      'pages'=>['index.php']],
                ['href'=>'orders.php',    'icon'=>'fa-receipt',    'label'=>'Orders',    'pages'=>['orders.php','view_order.php']],
                ['href'=>'products.php',  'icon'=>'fa-cube',       'label'=>'Products',  'pages'=>['products.php','add_product.php','edit_product.php','categories.php']],
                ['href'=>'customers.php', 'icon'=>'fa-user-group', 'label'=>'Customers', 'pages'=>['customers.php']],
            ];
            $cp = basename($_SERVER['PHP_SELF']);
            foreach ($btm as $b):
                $on = in_array($cp, $b['pages']);
            ?>
            <a href="<?= $b['href'] ?>" <?= $on ? 'aria-current="page"' : '' ?>
               class="relative flex min-h-[56px] flex-1 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold transition-colors <?= $on ? 'text-brand-600' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($on): ?><span class="absolute top-0 h-0.5 w-8 rounded-full bg-brand-600"></span><?php endif; ?>
                <i class="fas <?= $b['icon'] ?> text-[17px]"></i>
                <?= $b['label'] ?>
            </a>
            <?php endforeach; ?>
            <button type="button" id="openSidebarBottom" class="flex min-h-[56px] flex-1 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold text-slate-400 transition-colors hover:text-slate-600">
                <i class="fas fa-ellipsis text-[17px]"></i>More
            </button>
        </div>
    </nav>

</div><!-- /.main wrapper -->

<script>
(function () {
    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) return;

    function openDrawer() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        requestAnimationFrame(function () { overlay.classList.remove('opacity-0'); });
        document.body.style.overflow = 'hidden';
    }
    window.closeSidebarFn = function () {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(function () { overlay.classList.add('hidden'); }, 280);
        document.body.style.overflow = '';
    };

    ['openSidebar', 'openSidebarBottom'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', openDrawer);
    });
    var closeBtn = document.getElementById('closeSidebar');
    if (closeBtn) closeBtn.addEventListener('click', window.closeSidebarFn);
    overlay.addEventListener('click', window.closeSidebarFn);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.closeSidebarFn(); });

    var startX = 0;
    sidebar.addEventListener('touchstart', function (e) { startX = e.changedTouches[0].clientX; }, { passive: true });
    sidebar.addEventListener('touchend', function (e) {
        if (startX - e.changedTouches[0].clientX > 60) window.closeSidebarFn();
    }, { passive: true });

    // Close the "Create" menu when clicking elsewhere
    document.addEventListener('click', function (e) {
        document.querySelectorAll('header details[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
})();
</script>
</body>
</html>
