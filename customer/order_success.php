<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId < 1) {
    die('Invalid order.');
}

// Fetch full order details, now including seller_id
$sql = "
  SELECT 
    o.id           AS order_id,
    o.order_date,
    o.quantity,
    o.payment_method,
    o.payment_ref,
    dm.method_name AS delivery_method,
    dm.cost        AS delivery_cost,
    p.name         AS product_name,
    p.price        AS unit_price,
    s.id           AS seller_id,
    s.whatsapp_number
  FROM orders o
  JOIN products p               ON p.id = o.product_id
  JOIN delivery_methods dm      ON dm.id = o.delivery_method_id
  JOIN sellers s                ON s.id = p.seller_id
  WHERE o.id = ? AND o.customer_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $orderId, $_SESSION['customer_id']);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    die('Order not found.');
}
$order = $res->fetch_assoc();
$stmt->close();

// Compute totals
$subtotal   = $order['unit_price'] * $order['quantity'];
$delivery   = $order['delivery_cost'];
$total      = $subtotal + $delivery;

// WhatsApp link (send proof of payment)
$waNumber   = preg_replace('/\D/','',$order['whatsapp_number']);
$waMessage  = urlencode("Hi, I’m sending proof of payment for order #{$order['order_id']} (ref: {$order['payment_ref']}). Thanks!");

// --- Leave-a-Store-Review logic ---
$storeReviewed = false;
$reviewSuccess = false;
$reviewErrors  = [];

// check if this customer already reviewed this store
$chk = $conn->prepare("
  SELECT COUNT(*) 
    FROM store_reviews 
   WHERE seller_id=? 
     AND customer_id=?
");
$chk->bind_param('ii', $order['seller_id'], $_SESSION['customer_id']);
$chk->execute();
$chk->bind_result($cnt);
$chk->fetch();
$storeReviewed = ($cnt > 0);
$chk->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_review'])) {
    $rating   = intval($_POST['rating'] ?? 0);
    $review   = trim($_POST['review_text'] ?? '');
    if ($rating < 1 || $rating > 5) {
        $reviewErrors[] = "Please select a rating between 1 and 5.";
    }
    if (empty($review)) {
        $reviewErrors[] = "Please enter your review.";
    }
    if (empty($reviewErrors)) {
        $ins = $conn->prepare("
          INSERT INTO store_reviews
            (seller_id, customer_id, rating, review_text)
          VALUES (?,?,?,?)
        ");
        $ins->bind_param(
          'iiis',
          $order['seller_id'],
          $_SESSION['customer_id'],
          $rating,
          $review
        );
        if ($ins->execute()) {
            $reviewSuccess = true;
            $storeReviewed = true;
        } else {
            $reviewErrors[] = "Failed to save review.";
        }
        $ins->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order #<?= $order['order_id'] ?> Receipt • KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print { .no-print { display: none!important; } }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card mx-auto" style="max-width:600px">
      <div class="card-body">
        <h2 class="card-title">Order Receipt</h2>
        <p class="text-muted">
          Order #<?= $order['order_id'] ?> placed on 
          <?= date('d M Y, H:i', strtotime($order['order_date'])) ?>
        </p>

        <table class="table">
          <tr><th>Product</th><td><?= htmlspecialchars($order['product_name']) ?></td></tr>
          <tr><th>Unit Price</th><td>R<?= number_format($order['unit_price'],2) ?></td></tr>
          <tr><th>Quantity</th><td><?= $order['quantity'] ?></td></tr>
          <tr><th>Delivery</th>
            <td><?= htmlspecialchars($order['delivery_method']) ?>
            (R<?= number_format($delivery,2) ?>)</td>
          </tr>
          <tr><th>Payment Type</th>
            <td><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></td>
          </tr>
          <?php if($order['payment_method'] !== 'cod'): ?>
            <tr><th>Payment Ref</th>
              <td><?= htmlspecialchars($order['payment_ref']) ?></td>
            </tr>
          <?php endif; ?>
          <tr class="fw-bold"><th>Total</th>
            <td>R<?= number_format($total,2) ?></td>
          </tr>
        </table>

        <div class="d-flex justify-content-between flex-wrap no-print mb-4">
          <a href="https://wa.me/<?= $waNumber ?>?text=<?= $waMessage ?>"
             class="btn btn-success mb-2" target="_blank">
            💬 Chat with Seller
          </a>

          <button onclick="window.print()"
                  class="btn btn-outline-primary mb-2">
            🖨️ Print Receipt
          </button>

          <a href="browseproducts.php"
             class="btn btn-secondary mb-2">
            🏠 Back to Home
          </a>

          <!-- 🚚 Track Order button -->
          <a href="track_order.php?order_id=<?= $order['order_id'] ?>"
             class="btn btn-info text-white mb-2">
            🚚 Track Order
          </a>
        </div>

        <?php if ($reviewSuccess): ?>
          <div class="alert alert-success">Thank you! Your review has been submitted.</div>
        <?php endif; ?>

        <?php if (!$storeReviewed): ?>
          <h5>Leave a Store Review</h5>
          <?php if ($reviewErrors): ?>
            <div class="alert alert-danger">
              <?php foreach ($reviewErrors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
            </div>
          <?php endif; ?>
          <form method="POST" class="mt-3 no-print">
            <input type="hidden" name="leave_review" value="1">
            <div class="mb-3">
              <label class="form-label">Rating</label>
              <select name="rating" class="form-select" required>
                <option value="">Choose…</option>
                <?php for($i=5; $i>=1; $i--): ?>
                  <option value="<?=$i?>"><?=$i?> star<?=$i>1?'s':''?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Review</label>
              <textarea name="review_text" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
          </form>
        <?php endif; ?>

      </div>
    </div>
  </div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>



