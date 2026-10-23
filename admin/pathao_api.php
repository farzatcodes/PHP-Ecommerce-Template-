<?php
/**
 * Pathao AJAX endpoint — returns JSON for all Pathao operations.
 * Actions: get_cities | get_zones | get_stores | send_order
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pathao_client.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$settingsFile = '../settings.json';
$settings     = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?? [] : [];

$pathao = new PathaoClient($settings);

if (!$pathao->isConfigured()) {
    echo json_encode(['error' => 'Pathao credentials not configured. Go to Pathao Settings first.']);
    exit;
}

try {
    switch ($action) {

        case 'get_cities':
            $cities = $pathao->getCities();
            echo json_encode(['ok' => true, 'cities' => $cities]);
            break;

        case 'get_zones':
            $cityId = (int)($_GET['city_id'] ?? 0);
            if (!$cityId) { echo json_encode(['error' => 'city_id required']); exit; }
            $zones = $pathao->getZones($cityId);
            echo json_encode(['ok' => true, 'zones' => $zones]);
            break;

        case 'get_stores':
            $stores = $pathao->getStores();
            echo json_encode(['ok' => true, 'stores' => $stores]);
            break;

        case 'send_order':
            // CSRF + POST only
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['error' => 'POST required']); exit;
            }
            csrf_verify();

            $orderId        = trim($_POST['order_id'] ?? '');
            $cityId         = (int)($_POST['city_id']  ?? 0);
            $zoneId         = (int)($_POST['zone_id']  ?? 0);
            $storeId        = (int)($_POST['store_id'] ?? (int)($settings['pathao_store_id'] ?? 0));
            $weight         = (float)($_POST['item_weight'] ?? 0.5);
            $delivType      = (int)($_POST['delivery_type'] ?? 48);
            $addressOverride = trim($_POST['address'] ?? '');

            if (!$orderId || !$cityId || !$zoneId || !$storeId) {
                echo json_encode(['error' => 'Missing required fields (order, city, zone, store).']); exit;
            }
            if (strlen($addressOverride) < 10) {
                echo json_encode(['error' => 'Address must be at least 10 characters.']); exit;
            }

            // Load the order
            $ordersFile = '../orders.json';
            $orders     = file_exists($ordersFile) ? json_decode(file_get_contents($ordersFile), true) ?? [] : [];
            $orderIndex = null;
            $order      = null;
            foreach ($orders as $i => $o) {
                if ($o['id'] === $orderId) { $orderIndex = $i; $order = $o; break; }
            }
            if (!$order) {
                echo json_encode(['error' => "Order #{$orderId} not found."]); exit;
            }

            // Build Pathao create-order payload
            $totalQty = 0;
            foreach ($order['items'] as $item) $totalQty += (int)($item['qty'] ?? 1);

            // Pathao expects 01XXXXXXXXX format (11 digits, no +880 prefix)
            $rawPhone = $order['customer']['phone'] ?? '';
            $e164     = bd_tel($rawPhone); // +8801XXXXXXXXX
            if ($e164 && str_starts_with($e164, '+880')) {
                $pathaoPhone = '0' . substr($e164, 4); // strip +880, prepend 0
            } else {
                $pathaoPhone = preg_replace('/[^0-9]/', '', $rawPhone);
            }

            $payload = [
                'store_id'           => $storeId,
                'merchant_order_id'  => $order['id'],
                'recipient_name'     => $order['customer']['name']    ?? '',
                'recipient_phone'    => $pathaoPhone,
                'recipient_address'  => $addressOverride ?: ($order['customer']['address'] ?? ''),
                'recipient_city'     => $cityId,
                'recipient_zone'     => $zoneId,
                'delivery_type'      => $delivType,
                'item_type'          => 2,
                'item_quantity'      => $totalQty ?: 1,
                'item_weight'        => $weight,
                'amount_to_collect'  => (int)($order['total'] ?? 0),
                'item_description'   => mb_substr(implode(', ', array_column($order['items'], 'name')), 0, 200),
            ];

            if (!empty($order['customer']['comment'])) {
                $payload['special_instruction'] = $order['customer']['comment'];
            }

            $result = $pathao->createOrder($payload);

            // Pathao returns consignment_id on success
            $consignmentId = $result['data']['consignment_id']
                          ?? $result['consignment_id']
                          ?? null;

            if ($consignmentId) {
                // Persist consignment_id back to orders.json
                $orders[$orderIndex]['pathao_consignment_id'] = $consignmentId;
                $orders[$orderIndex]['pathao_status']         = $result['data']['order_status'] ?? 'Pending';
                $fp = fopen($ordersFile, 'c+');
                flock($fp, LOCK_EX);
                $existing = json_decode(stream_get_contents($fp), true) ?? [];
                foreach ($existing as $j => $o) {
                    if ($o['id'] === $orderId) {
                        $existing[$j]['pathao_consignment_id'] = $consignmentId;
                        $existing[$j]['pathao_status']         = $result['data']['order_status'] ?? 'Pending';
                        break;
                    }
                }
                ftruncate($fp, 0); rewind($fp);
                fwrite($fp, json_encode($existing, JSON_PRETTY_PRINT));
                fflush($fp);
                flock($fp, LOCK_UN); fclose($fp);

                echo json_encode(['ok' => true, 'consignment_id' => $consignmentId, 'pathao_status' => $result['data']['order_status'] ?? 'Pending']);
            } else {
                // Extract field-level validation errors if present
                $msg = $result['message'] ?? $result['error'] ?? 'Unknown error';
                if (!empty($result['errors']) && is_array($result['errors'])) {
                    $details = [];
                    foreach ($result['errors'] as $field => $msgs) {
                        $details[] = $field . ': ' . (is_array($msgs) ? implode(', ', $msgs) : $msgs);
                    }
                    $msg .= ' — ' . implode(' | ', $details);
                }
                echo json_encode(['error' => 'Pathao rejected: ' . $msg, '_debug' => $result]);
            }
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (RuntimeException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
