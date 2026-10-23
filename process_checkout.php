<?php
ob_start(); // Bug #9: buffer any stray output so header() always works

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ordersFile = 'orders.json';

    // Open with exclusive lock to prevent concurrent writes losing orders
    $fp = fopen($ordersFile, 'c+');
    if (!$fp) {
        header("Location: checkout.php?error=server");
        exit;
    }
    flock($fp, LOCK_EX);
    $raw    = stream_get_contents($fp);
    $orders = json_decode($raw, true) ?: [];

    // Support both landing page field names (billing_*) and checkout page (customer_*)
    $customer_name    = htmlspecialchars($_POST['billing_name']    ?? $_POST['customer_name']    ?? '');
    $customer_phone   = htmlspecialchars($_POST['billing_phone']   ?? $_POST['customer_phone']   ?? '');
    $customer_address = htmlspecialchars($_POST['billing_address'] ?? $_POST['customer_address'] ?? '');
    $customer_comment = htmlspecialchars($_POST['customer_comment'] ?? '');

    // Support landing page delivery_zone (text) and checkout shipping_area (integer)
    if (isset($_POST['delivery_zone'])) {
        $zone = $_POST['delivery_zone']; // Bug #5: validate delivery_zone
        $shipping_cost = $zone === 'inside_dhaka' ? 60 : 120; // only two valid values
        $order_source = 'landing_page';
    } else {
        $shipping_cost = isset($_POST['shipping_area']) ? (int)$_POST['shipping_area'] : 150;
        $order_source = 'website';
    }

    // Dynamically parse the cart data sent from checkout.php via Javascript
    $cartData = isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : [];

    $subtotal = 0;
    $items = [];

    // Loop through dynamic cart data — handle both qty (website) and quantity (landing page)
    if (is_array($cartData)) {
        foreach ($cartData as $item) {
            $qty = (int)($item['qty'] ?? $item['quantity'] ?? 1);
            $itemTotal = (int)$item['price'] * $qty;
            $subtotal += $itemTotal;

            $items[] = [
                'name'  => htmlspecialchars($item['name']),
                'qty'   => $qty,
                'price' => (int)$item['price'],
                'total' => $itemTotal,
                'image' => htmlspecialchars($item['image'] ?? ''),
            ];
        }
    }

    $total = $subtotal + $shipping_cost;

    $newOrder = [
        'id' => 'ORD-' . rand(10000, 99999),
        'date' => date('Y-m-d h:i A'),
        'source' => $order_source,
        'customer' => [
            'name' => $customer_name,
            'phone' => $customer_phone,
            'address' => $customer_address,
            'comment' => $customer_comment,
        ],
        'shipping_cost' => $shipping_cost,
        'subtotal' => $subtotal,
        'total' => $total,
        'payment_method' => $_POST['payment_method'] ?? 'Cash on Delivery',
        'status' => 'Pending',
        'items' => $items
    ];

    // Add new order to the top of the array
    array_unshift($orders, $newOrder);

    // Write atomically inside the lock, then release
    $json = json_encode($orders, JSON_PRETTY_PRINT);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    // Redirect to success
    ob_end_clean();
    header("Location: checkout_success.php?order_id=" . $newOrder['id']);
    exit;
} else {
    ob_end_clean();
    header("Location: checkout.php");
    exit;
}
?>