<?php


// for debugging. my apologies sir. 
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

session_start();
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
$customer_id = (int)$_SESSION['customer_id'];

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

// Fetch this customer’s orders
$stmt = $conn->prepare("
  SELECT 
    o.id,
    p.name           AS product_name,
    o.quantity,
    dm.method_name   AS delivery_name,
    o.order_date,
    o.payment_method,
    COALESCE(o.tracking_status,'Not dispatched') AS tracking_status
  FROM orders o
  JOIN products p          ON p.id = o.product_id
  JOIN delivery_methods dm ON dm.id = o.delivery_method_id
  WHERE o.customer_id = ?
  ORDER BY o.order_date DESC
");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Orders — KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>🛒 My Orders</h1>
      <a href="browseproducts.php" class="btn btn-outline-secondary">
        ← Back to Browse
      </a>
    </div>

    <?php if ($res->num_rows === 0): ?>
      <div class="alert alert-info">You haven’t placed any orders yet.</div>
    <?php else: ?>
      <table class="table table-striped">
        <thead class="table-dark">
          <tr>
            <th>Order #</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Delivery</th>
            <th>Date</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Track</th>
          </tr>
        </thead>
        <tbody>
          <?php while($o = $res->fetch_assoc()): ?>
          <tr>
            <td><?= $o['id'] ?></td>
            <td><?= htmlspecialchars($o['product_name'], ENT_QUOTES) ?></td>
            <td><?= $o['quantity'] ?></td>
            <td><?= htmlspecialchars($o['delivery_name'], ENT_QUOTES) ?></td>
            <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
            <td><?= strtoupper(htmlspecialchars($o['payment_method'], ENT_QUOTES)) ?></td>
            <td><?= htmlspecialchars(ucfirst($o['tracking_status']), ENT_QUOTES) ?></td>
            <td>
              <a href="track_order.php?order_id=<?= $o['id'] ?>" 
                 class="btn btn-sm btn-primary">
                Track
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>s
</body>
</html>
