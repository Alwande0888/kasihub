<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;


if (!isset($_SESSION['seller_id'])) {
  header('Location: login.php');
  exit;
}
$seller_id = (int)$_SESSION['seller_id'];

// Fetch all orders for this seller (no tracking_link)
$stmt = $conn->prepare("
  SELECT 
    o.id,
    p.name            AS product_name,
    o.quantity,
    dm.method_name    AS delivery_method,
    o.payment_method,
    o.tracking_status,
    c.phone           AS buyer_phone,
    o.order_date
  FROM orders o
  JOIN products p          ON p.id = o.product_id
  JOIN delivery_methods dm ON dm.id = o.delivery_method_id
  JOIN customers c         ON c.id = o.customer_id
  WHERE p.seller_id = ?
  ORDER BY o.order_date DESC
");
$stmt->bind_param('i',$seller_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Orders • KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1>Your Orders</h1>
    <a href="dashboard.php" class="btn btn-link mb-4">&larr; Back to Dashboard</a>

    <table class="table table-striped">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Delivery</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Dispatch</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($orders as $o): ?>
        <tr>
          <td><?= $o['id'] ?></td>
          <td><?= htmlspecialchars($o['product_name'], ENT_QUOTES) ?></td>
          <td><?= $o['quantity'] ?></td>
          <td><?= htmlspecialchars($o['delivery_method'], ENT_QUOTES) ?></td>
          <td><?= strtoupper(htmlspecialchars($o['payment_method'], ENT_QUOTES)) ?></td>
          <td>
            <?= ucfirst(
                 htmlspecialchars(
                   $o['tracking_status'] ?? 'Not booked',
                   ENT_QUOTES
                 )
               ) ?>
          </td>
          <td>
            <form method="POST" action="book_delivery.php" class="d-flex">
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
              <select name="courier_service" class="form-select form-select-sm me-2" required>
                <option value="">Choose</option>
                <option value="uber">Uber Courier</option>
                <option value="paxi">PAXI Store-to-Store</option>
              </select>
              <button type="submit" class="btn btn-warning btn-sm">Book</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>




