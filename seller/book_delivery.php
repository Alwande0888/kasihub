<?php
session_start();
if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../backend/db_connect.php';
$conn = $mysqli;

$seller_id = (int)$_SESSION['seller_id'];
$order_id  = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$courier   = $_POST['courier_service'] ?? '';

if ($order_id < 1 || !in_array($courier, ['uber','paxi'])) {
    die('Invalid dispatch parameters.');
}


$stmt = $conn->prepare("
  SELECT 
    s.address AS pickup,
    c.address AS dropoff
  FROM orders o
  JOIN products p ON p.id = o.product_id
  JOIN sellers  s ON s.id = p.seller_id
  JOIN customers c ON c.id = o.customer_id
  WHERE o.id = ? 
    AND p.seller_id = ?
  LIMIT 1
");
$stmt->bind_param('ii', $order_id, $seller_id);
$stmt->execute();
$stmt->bind_result($pickup, $dropoff);
if (!$stmt->fetch()) {
    die('Order not found or access denied.');
}
$stmt->close();


if ($courier === 'uber') {
    // Uber booking
    $ch = curl_init('https://api.uber.com/v1/requests');
    $body = json_encode([
      'start_address' => $pickup,
      'end_address'   => $dropoff,
      'product_id'    => UBER_PRODUCT_ID
    ]);
    curl_setopt_array($ch,[
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $body,
      CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer ".UBER_TOKEN,
        "Content-Type: application/json"
      ],
      CURLOPT_RETURNTRANSFER => true
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $tracking_id  = $resp['request_id']        ?? null;
    $tracking_url = $resp['tracking']['url']    ?? null;
}
else {
    // PAXI booking
    $ch = curl_init('https://api.paxi.co.za/v1/book');
    $body = json_encode([
      'username' => PAXI_USER,
      'apikey'   => PAXI_APIKEY,
      'pickup'   => $pickup,
      'dropoff'  => $dropoff
    ]);
    curl_setopt_array($ch,[
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $body,
      CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
      CURLOPT_RETURNTRANSFER => true
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $tracking_id  = $resp['booking_id']     ?? null;
    $tracking_url = $resp['tracking_url']   ?? null;
}

// SAVE to orders
$upd = $conn->prepare("
  UPDATE orders
     SET courier_service = ?,
         tracking_id     = ?,
         tracking_url    = ?,
         tracking_status = 'pending'
   WHERE id = ?
");
$upd->bind_param('sssi', $courier, $tracking_id, $tracking_url, $order_id);
$upd->execute();
$upd->close();

// Redirect back to dashboard
header("Location: dashboard.php?dispatched=1");
exit;

