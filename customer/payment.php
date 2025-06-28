<?php
session_start();

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

if (!isset($_SESSION['customer_id'])) {
  header('Location: login.php');
  exit;
}
if (!isset($_GET['order_id'])) {
  die("No order specified.");
}
$orderId = (int)$_GET['order_id'];


$stmt = $conn->prepare("
  SELECT o.payment_method, o.quantity, p.price, p.name 
    FROM orders o
    JOIN products p ON o.product_id = p.id
   WHERE o.id = ? AND o.buyer_id = ?
");
$stmt->bind_param('ii',$orderId,$_SESSION['customer_id']);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
  die("Order not found.");
}
$order = $res->fetch_assoc();
$stmt->close();

// Calculate total
$total = $order['quantity'] * $order['price'];
$method = $order['payment_method']; // 'eft' or 'ewallet' or 'cod'
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment • KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h1 class="mb-4">Complete Your Payment</h1>
  <p>
    <strong>Order #<?= $orderId ?></strong><br>
    <strong>Product:</strong> <?= htmlspecialchars($order['name']) ?><br>
    <strong>Quantity:</strong> <?= $order['quantity'] ?><br>
    <strong>Total:</strong> R<?= number_format($total,2) ?>
  </p>

  <form action="payment_process.php" method="POST" class="mt-4">
    <input type="hidden" name="order_id" value="<?= $orderId ?>">

    <?php if ($method === 'eft'): ?>
      <h4>Bank EFT Details</h4>
      <p>Please make a payment of <strong>R<?= number_format($total,2) ?></strong> to:</p>
      <ul>
        <li><strong>Bank:</strong> Absa</li>
        <li><strong>Account Name:</strong> KasiHub Vendors</li>
        <li><strong>Account Number:</strong> 123456789</li>
        <li><strong>Branch Code:</strong> 632005</li>
      </ul>
      <div class="mb-3">
        <label class="form-label">Your Account Name</label>
        <input type="text" name="eft_name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">EFT Reference Number</label>
        <input type="text" name="eft_ref" class="form-control" required>
      </div>

    <?php elseif ($method === 'ewallet'): ?>
      <h4>CashSend / eWallet</h4>
      <p>Send <strong>R<?= number_format($total,2) ?></strong> to our mobile wallet:</p>
      <ul>
        <li><strong>Provider:</strong> CashSend</li>
        <li><strong>Mobile Number:</strong> 083 123 4567</li>
      </ul>
      <div class="mb-3">
        <label class="form-label">Your Mobile Number (for callback)</label>
        <input type="text" name="wallet_number" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Transaction Code</label>
        <input type="text" name="wallet_ref" class="form-control" required>
      </div>

    <?php else: ?>
      <p>No payment needed (Cash on Delivery). Click below to confirm.</p>
    <?php endif; ?>

    <button type="submit" class="btn btn-success">Confirm Payment</button>
  </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>
