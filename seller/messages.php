<?php
session_start();
if (!isset($_SESSION['seller_id'])) {
  header("Location: login.php");
  exit();
}
require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

// Fetchs orders + customer WhatsApp
$stmt = $conn->prepare("
  SELECT 
    o.id AS order_id,
    c.name AS customer_name,
    c.whatsapp_number
  FROM orders o
  JOIN products p ON o.product_id = p.id
  JOIN customers c ON o.customer_id = c.id
  WHERE p.seller_id = ?
  ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $_SESSION['seller_id']);
$stmt->execute();
$chats = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Messages – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h1>💬 Messages</h1>
  <?php if ($chats->num_rows === 0): ?>
    <div class="alert alert-info">No orders yet—so no chats to show.</div>
  <?php else: ?>
    <table class="table table-striped mt-4">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>Chat</th></tr>
      </thead>
      <tbody>
      <?php while($m = $chats->fetch_assoc()): ?>
        <tr>
          <td><?php echo $m['order_id']; ?></td>
          <td><?php echo htmlspecialchars($m['customer_name']); ?></td>
          <td>
            <a 
              href="https://wa.me/<?php echo preg_replace('/\D/', '', $m['whatsapp_number']); ?>?text=<?php 
                echo urlencode("Hi {$m['customer_name']}, I'm {$_SESSION['store_name']} about order #{$m['order_id']}"); 
              ?>" 
              target="_blank" 
              class="btn btn-success btn-sm"
            >
              WhatsApp
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>
  <a href="dashboard.php" class="btn btn-link">&larr; Back to Dashboard</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!--Your Main JavaScript File -->
  <script src="main.js"></script>
</body>
</html>
