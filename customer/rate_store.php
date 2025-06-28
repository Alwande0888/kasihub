<?php
session_start();

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer = $_SESSION['customer_id'];
$sellerId = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
if (!$sellerId) die('Invalid store.');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $rating = intval($_POST['rating']);
    $text   = trim($_POST['review_text']);
    if ($rating<1 || $rating>5) die('Invalid rating.');

    $sql = "INSERT INTO store_reviews (seller_id, customer_id, rating, review_text)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiis', $sellerId, $customer, $rating, $text);
    if (!$stmt->execute()) die('DB Error: '.$stmt->error);

    header("Location: seller_store.php?seller_id={$sellerId}&reviewed=1");
    exit;
}


?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rate Store • KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width:600px;">
    <h1 class="mb-4">Rate This Store</h1>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Rating</label>
        <select name="rating" class="form-select" required>
          <option value="">Select…</option>
          <?php for($i=1;$i<=5;$i++): ?>
            <option value="<?=$i?>"><?=$i?> Star<?=$i>1?'s':''?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Review (optional)</label>
        <textarea name="review_text" class="form-control" rows="4"></textarea>
      </div>
      <button class="btn btn-primary" type="submit">Submit Review</button>
      <a href="seller_store.php?seller_id=<?=$sellerId?>" class="btn btn-link">Cancel</a>
    </form>
  </div>
  <!-- Bootstrap 5 JS (for dropdowns, modals, etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>
