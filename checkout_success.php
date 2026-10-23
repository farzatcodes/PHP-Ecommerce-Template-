<?php
$settingsFile = __DIR__ . '/settings.json';
$siteSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?? [] : [];
include_once 'includes/header.php';
?>

<main class="bg-cream-soft min-h-[60vh] flex items-center justify-center py-12">
    <div class="bg-white p-8 rounded-xl shadow-sm max-w-md w-full text-center border border-gray-200">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-green-500 mx-auto mb-6">
            <i class="fas fa-check-circle text-4xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2 bengali-text">অর্ডার সম্পন্ন হয়েছে!</h1>
        <p class="text-gray-600 mb-6">Thank you for your purchase. We have received your order.</p>
        
        <div class="bg-gray-50 p-4 rounded-lg mb-8 border border-gray-100">
            <span class="block text-sm text-gray-500 mb-1">Order ID</span>
            <span class="block text-xl font-bold text-[#DB1456]"><?= htmlspecialchars($_GET['order_id'] ?? 'N/A') ?></span>
        </div>

        <a href="index.php" class="inline-block bg-[#DB1456] text-white font-bold py-3 px-8 rounded-lg hover:bg-[#B80E43] transition-colors">
            Return to Home
        </a>
    </div>
</main>

<script>
    // Empty the user's local storage cart now that the order has been placed successfully
    localStorage.removeItem('wellcarebd_cart');
</script>

<?php
// Purchase tracking. Looks up the real order so the total is correct.
$orderId    = (string)($_GET['order_id'] ?? '');
$orderTotal = 0;
$orderItems = [];
$ordersFile = __DIR__ . '/orders.json';
if ($orderId !== '' && file_exists($ordersFile)) {
    $allOrders = json_decode(file_get_contents($ordersFile), true) ?: [];
    foreach ($allOrders as $o) {
        if ((string)($o['id'] ?? '') === $orderId) {
            $orderTotal = (float)($o['total'] ?? 0);
            $orderItems = $o['items'] ?? [];
            break;
        }
    }
}
$viaGtm = function_exists('pixelViaGtm') && pixelViaGtm($siteSettings);
$guard  = 'purchase_fired_' . $orderId;
?>
<?php if ($viaGtm && $orderId !== ''): ?>
<!-- Purchase -> dataLayer (Google Tag Manager fires the Meta pixel; once per order) -->
<script data-cfasync="false">
(function(){
    try { if (sessionStorage.getItem(<?= json_encode($guard) ?>)) return; sessionStorage.setItem(<?= json_encode($guard) ?>, '1'); } catch (e) {}
    trackPixel('Purchase', {
        value: <?= json_encode($orderTotal) ?>,
        currency: 'BDT',
        transaction_id: <?= json_encode($orderId) ?>,
        content_type: 'product',
        num_items: <?= (int)array_sum(array_map(fn($i) => (int)($i['qty'] ?? 1), $orderItems)) ?>
    });
})();
</script>
<?php elseif (!$viaGtm && !empty($siteSettings['pixel_purchase'])):
    $purchaseCode = str_replace(
        ["'{{order_total}}'", '"{{order_total}}"', '{{order_total}}', '{{order_id}}'],
        [$orderTotal, $orderTotal, $orderTotal, htmlspecialchars($orderId, ENT_QUOTES)],
        $siteSettings['pixel_purchase']
    );
    // once-per-order guard so a page refresh doesn't double-count
    $purchaseCode = preg_replace_callback('#<script([^>]*)>(.*?)</script>#is', function ($m) use ($guard) {
        return '<script' . $m[1] . '>(function(){try{if(sessionStorage.getItem(' . json_encode($guard) . '))return;'
             . 'sessionStorage.setItem(' . json_encode($guard) . ',"1");}catch(e){}'
             . 'try{' . $m[2] . '}catch(e){}})();</script>';
    }, $purchaseCode);
?>
<!-- @purchase pixel (direct pixel; fires once per order) -->
<?php echo $purchaseCode; ?>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>