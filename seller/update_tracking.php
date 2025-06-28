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

$order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
if ($order_id < 1) {
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url  = trim($_POST['tracking_url']   ?? '');
    $code = trim($_POST['tracking_code']  ?? '');
    
    $status = $url || $code ? 'in transit' : 'pending';

    $upd = $conn->prepare("
        UPDATE orders
           SET tracking_url    = ?,
               tracking_code   = ?,
               tracking_status = ?
         WHERE id = ?
    ");
    $upd->bind_param('sssi', $url, $code, $status, $order_id);
    if (!$upd->execute()) {
        die('DB Error: ' . $upd->error);
    }
    header('Location: dashboard.php?tracking_updated=1');
    exit;
}

// On GET: fetch existing values
$stmt = $conn->prepare("
    SELECT p.name,
           o.tracking_url,
           o.tracking_code
      FROM orders o
      JOIN products p ON p.id = o.product_id
     WHERE o.id = ?
");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$stmt->bind_result($prod_name, $existing_url, $existing_code);
if (!$stmt->fetch()) {
    die('Order not found.');
}
$stmt->close();


$existing_url  = $existing_url  ?? '';
$existing_code = $existing_code ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Update Tracking • KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
  <div class="container" style="max-width:500px;">
    <h2>Update Tracking for Order #<?= $order_id ?></h2>
    <p><strong>Product:</strong> <?= htmlspecialchars($prod_name, ENT_QUOTES) ?></p>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Uber Tracking URL</label>
        <input 
          type="url" 
          name="tracking_url" 
          class="form-control" 
          placeholder="https://m.uber.com/..." 
          value="<?= htmlspecialchars($existing_url, ENT_QUOTES) ?>">
        <small class="text-muted">Paste the Uber tracking link here (if using Uber).</small>
      </div>
      <div class="mb-3">
        <label class="form-label">PAXI Tracking Code</label>
        <input 
          type="text" 
          name="tracking_code" 
          class="form-control"
          placeholder="e.g. PAXI12345"
          value="<?= htmlspecialchars($existing_code, ENT_QUOTES) ?>">
        <small class="text-muted">Or enter the PAXI store-to-store code.</small>
      </div>
      <button class="btn btn-primary">Save & Notify</button>
      <a href="dashboard.php" class="btn btn-link">Cancel</a>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>


