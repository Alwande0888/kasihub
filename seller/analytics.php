<?php
session_start();
if (!isset($_SESSION['seller_id'])) {
  header("Location: login.php");
  exit();
}

require_once __DIR__ . '/../backend/db_connect.php';
$conn = $mysqli;

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE seller_id=?");
$stmt->bind_param("i", $_SESSION['seller_id']);
$stmt->execute(); $stmt->bind_result($total_products); $stmt->fetch(); $stmt->close();

// Total orders
$stmt = $conn->prepare("
  SELECT COUNT(*) 
  FROM orders o 
  JOIN products p ON o.product_id=p.id 
  WHERE p.seller_id=?
");
$stmt->bind_param("i", $_SESSION['seller_id']);
$stmt->execute(); $stmt->bind_result($total_orders); $stmt->fetch(); $stmt->close();

// Total revenue
$stmt = $conn->prepare("
  SELECT COALESCE(SUM(o.total_price),0) 
  FROM orders o 
  JOIN products p ON o.product_id=p.id 
  WHERE p.seller_id=?
");
$stmt->bind_param("i", $_SESSION['seller_id']);
$stmt->execute(); $stmt->bind_result($total_revenue); $stmt->fetch(); $stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h1>📊 Analytics</h1>
  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card text-center">
        <div class="card-body">
          <h5>Total Products</h5>
          <p class="display-6"><?php echo $total_products; ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center">
        <div class="card-body">
          <h5>Total Orders</h5>
          <p class="display-6"><?php echo $total_orders; ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center">
        <div class="card-body">
          <h5>Total Revenue (R)</h5>
          <p class="display-6"><?php echo number_format($total_revenue,2); ?></p>
        </div>
      </div>
    </div>
  </div>
  <a href="dashboard.php" class="btn btn-link mt-4">&larr; Back to Dashboard</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>
