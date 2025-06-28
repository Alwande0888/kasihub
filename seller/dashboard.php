<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;



// 1) Auth check
if (!isset($_SESSION['seller_id'])) {
  header('Location: login.php');
  exit;
}
$seller_id  = (int)$_SESSION['seller_id'];
$store_name = $_SESSION['store_name'] ?? 'My Store';

// 2) Stats queries

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$stmt->bind_result($total_products);
$stmt->fetch();
$stmt->close();

// Total orders & revenue
$stmt = $conn->prepare("
  SELECT 
    COUNT(o.id) AS total_orders,
    COALESCE(SUM(o.quantity * p.price + dm.cost), 0) AS total_revenue
  FROM orders o
  JOIN products p          ON p.id = o.product_id
  JOIN delivery_methods dm ON dm.id = o.delivery_method_id
  WHERE p.seller_id = ?
");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$stmt->bind_result($total_orders, $total_revenue);
$stmt->fetch();
$stmt->close();

// Store rating
$stmt = $conn->prepare("
  SELECT COALESCE(AVG(rating),0)
    FROM store_reviews
   WHERE seller_id = ?
");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$stmt->bind_result($avg_rating);
$stmt->fetch();
$stmt->close();

// 3) Recent orders (last 5)
$stmt = $conn->prepare("
  SELECT 
    o.id,
    p.name         AS product_name,
    o.quantity,
    dm.method_name AS delivery_method,
    o.payment_method,
    COALESCE(o.tracking_status,'Not booked') AS tracking_status,
    c.phone        AS buyer_phone
  FROM orders o
  JOIN products p          ON p.id = o.product_id
  JOIN delivery_methods dm ON dm.id = o.delivery_method_id
  JOIN customers c         ON c.id = o.customer_id
  WHERE p.seller_id = ?
  ORDER BY o.order_date DESC
  LIMIT 5
");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$res = $stmt->get_result();
$recent_orders = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Seller Dashboard • KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">

    <!-- Header + Nav Buttons -->
    <div class="d-flex align-items-center mb-4">
      <h1 class="me-auto">Welcome, <?= htmlspecialchars($store_name, ENT_QUOTES) ?></h1>
      <a href="add_product.php"   class="btn btn-success me-2">+ Add Product</a>
      <a href="view_products.php" class="btn btn-primary me-2">View My Products</a>
      <a href="view_order.php"    class="btn btn-warning me-2">View Orders</a>
      <a href="profile.php"       class="btn btn-info me-2">Edit Profile</a>
      <a href="logout.php"        class="btn btn-outline-danger">Logout</a>
    </div>

    <!-- Stats Cards Row -->
    <div class="row text-center mb-5">
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5>Total Products</h5>
            <p class="fs-2"><?= $total_products ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5>Total Orders</h5>
            <p class="fs-2"><?= $total_orders ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5>Total Revenue</h5>
            <p class="fs-2">R<?= number_format($total_revenue,2) ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5>Store Rating</h5>
            <p class="fs-2"><?= number_format($avg_rating,1) ?> ★</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick View Storefront -->
    <div class="mb-5">
      <a href="../customer/seller_store.php?seller_id=<?= $seller_id ?>"
         class="btn btn-outline-primary">👀 View My Storefront</a>
    </div>

    <!-- Recent Orders Table -->
    <h3>Recent Orders</h3>
    <table class="table table-striped">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Delivery</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($recent_orders as $o): ?>
        <tr>
          <td><?= $o['id'] ?></td>
          <td><?= htmlspecialchars($o['product_name'],ENT_QUOTES) ?></td>
          <td><?= $o['quantity'] ?></td>
          <td><?= htmlspecialchars($o['delivery_method'],ENT_QUOTES) ?></td>
          <td><?= strtoupper(htmlspecialchars($o['payment_method'],ENT_QUOTES)) ?></td>
          <td><?= ucfirst(htmlspecialchars($o['tracking_status'],ENT_QUOTES)) ?></td>
          <td class="d-flex">
            <?php 
              // WhatsApp link
              $wa = preg_replace('/\D/','',$o['buyer_phone']);
              $msg = urlencode("Hi, I’m following up on order #{$o['id']}");
            ?>
            <a href="https://wa.me/<?= $wa ?>?text=<?= $msg ?>"
               class="btn btn-success btn-sm me-2" target="_blank">
              Message Buyer
            </a>
            <a href="update_tracking.php?order_id=<?= $o['id'] ?>"
               class="btn btn-warning btn-sm">
              Update Tracking
            </a>
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
