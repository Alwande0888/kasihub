<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

//  Auth check
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
$customer_id = (int)$_SESSION['customer_id'];


$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id < 1) {
    header('Location: browseproducts.php?error=invalid');
    exit;
}

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

$stmt = $conn->prepare("
  SELECT 
    o.id,
    p.name              AS product_name,
    o.quantity,
    dm.method_name      AS delivery_method,
    o.payment_method,
    o.tracking_status,
    o.tracking_url,
    o.tracking_code,
    o.order_date
  FROM orders o
  JOIN products p          ON p.id = o.product_id
  JOIN delivery_methods dm ON dm.id = o.delivery_method_id
  WHERE o.id = ? 
    AND o.customer_id = ?
  LIMIT 1
");
$stmt->bind_param('ii', $order_id, $customer_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    header('Location: browseproducts.php?error=notfound');
    exit;
}
$order = $res->fetch_assoc();
$stmt->close();


$status = $order['tracking_status'] ?? 'Not dispatched';
$link   = $order['tracking_url'];
$code   = $order['tracking_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Track Order #<?= $order_id ?> — KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1>🚚 Tracking for Order #<?= $order_id ?></h1>
    <p>
      <strong>Product:</strong> <?= htmlspecialchars($order['product_name'], ENT_QUOTES) ?> × <?= $order['quantity'] ?>
    </p>
    <p>
      <strong>Delivery Method:</strong> <?= htmlspecialchars($order['delivery_method'], ENT_QUOTES) ?>
    </p>
    <p>
      <strong>Status:</strong> <?= htmlspecialchars(ucfirst($status), ENT_QUOTES) ?>
    </p>

    <?php if ($link): ?>
      <div class="alert alert-info">
        <a href="<?= htmlspecialchars($link, ENT_QUOTES) ?>" target="_blank" class="btn btn-primary">
          🚀 View Live Tracking
        </a>
      </div>
    <?php elseif ($code): ?>
      <div class="alert alert-warning">
        <strong>Your PAXI Code:</strong> <?= htmlspecialchars($code, ENT_QUOTES) ?>
      </div>
    <?php else: ?>
      <div class="alert alert-secondary">
        Tracking information not available yet.
      </div>
    <?php endif; ?>

    <a href="browseproducts.php" class="btn btn-link mt-4">&larr; Back to Browse Products</a>
  </div>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</html>



