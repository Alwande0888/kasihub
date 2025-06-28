<?php
session_start();
if (!isset($_SESSION['seller_id']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: view_order.php");
  exit();
}
require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

$order_id   = (int)$_GET['id'];
$seller_id  = $_SESSION['seller_id'];

$stmt = $conn->prepare("
  SELECT 
    o.id, o.quantity, o.total_price, o.order_date,
    c.name AS customer_name, c.whatsapp_number, c.address,
    p.name AS product_name, p.price,
    COALESCE(dm.method_name, 'N/A') AS delivery_method
  FROM orders o
  JOIN products p ON o.product_id = p.id
  JOIN customers c ON o.customer_id = c.id
  LEFT JOIN delivery_methods dm ON o.delivery_method_id = dm.id
  WHERE o.id = ? AND p.seller_id = ?
");
$stmt->bind_param("ii", $order_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
  header("Location: view_order.php");
  exit();
}
$o = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order #<?php echo $o['id']; ?> Details – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h1>Order #<?php echo $o['id']; ?> Details</h1>
  <ul class="list-group mt-4">
    <li class="list-group-item"><strong>Customer:</strong> <?php echo htmlspecialchars($o['customer_name']); ?></li>
    <li class="list-group-item"><strong>Product:</strong> <?php echo htmlspecialchars($o['product_name']); ?> (R<?php echo number_format($o['price'],2); ?>)</li>
    <li class="list-group-item"><strong>Quantity:</strong> <?php echo $o['quantity']; ?></li>
    <li class="list-group-item"><strong>Total Paid:</strong> R<?php echo number_format($o['total_price'],2); ?></li>
    <li class="list-group-item"><strong>Delivery Method:</strong> <?php echo htmlspecialchars($o['delivery_method']); ?></li>
    <li class="list-group-item"><strong>Address:</strong> <?php echo htmlspecialchars($o['address']); ?></li>
    <li class="list-group-item"><strong>Order Date:</strong> <?php echo date("Y-m-d H:i", strtotime($o['order_date'])); ?></li>
    <li class="list-group-item">
      <strong>Chat Buyer:</strong>
      <a 
        href="https://wa.me/<?php echo preg_replace('/\D/','',$o['whatsapp_number']); ?>?text=<?php 
          echo urlencode("Hi {$o['customer_name']}, about your order #{$o['id']}"); 
        ?>" 
        target="_blank" 
        class="btn btn-sm btn-success"
      >
        WhatsApp
      </a>
    </li>
  </ul>
  <a href="view_order.php" class="btn btn-link mt-4">&larr; Back to Orders</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>
