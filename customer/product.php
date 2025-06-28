<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    header('Location: browseproducts.php?error=invalid');
    exit;
}

// Query the product
$sql = "
  SELECT 
    p.*,
    c.name        AS category,
    s.store_name,
    s.whatsapp_number
  FROM products p
  JOIN categories c ON p.category_id = c.id
  JOIN sellers    s ON p.seller_id   = s.id
  WHERE p.id = ?
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    header('Location: browseproducts.php?error=notfound');
    exit;
}
$p = $res->fetch_assoc();
$stmt->close();


$deliveryMethods = [];
$dmRes = $conn->query("SELECT id, method_name, cost FROM delivery_methods ORDER BY id");
while ($dm = $dmRes->fetch_assoc()) {
    $deliveryMethods[] = $dm;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($p['name'], ENT_QUOTES) ?> – KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <a href="browseproducts.php" class="btn btn-link mb-4">&larr; Back to Browse</a>
    <div class="row g-4">

      <!-- IMAGE COLUMN -->
      <div class="col-md-6">
          
        <?php if (!empty($p['image'])): 
      // get just the filename
      $imgName = basename($p['image']);
      // build the URL into your public_html/images folder
      $imgUrl  = "/images/{$imgName}";
    ?>
      <div class="card shadow-sm">
        <img 
          src="<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>" 
          class="card-img-top" 
          alt="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
          style="object-fit:cover; height:400px; width:100%;"
        />
      </div>
    <?php else: ?>
      <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height:400px;">
        No Image Available
     </div>
    <?php endif;  ?>
      </div>

      
      <!-- DETAILS & FORM COLUMN -->
<div class="col-md-6">
  <div class="card h-100 shadow-sm">
    <div class="card-body">
      <h3 class="card-title"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></h3>
      <p class="text-muted">
        <?= htmlspecialchars($p['category'], ENT_QUOTES) ?> • 
        <a href="seller_store.php?seller_id=<?= $p['seller_id'] ?>">
          <?= htmlspecialchars($p['store_name'], ENT_QUOTES) ?>
        </a>
      </p>
      <h4 class="fw-bold text-primary">R <?= number_format($p['price'], 2) ?></h4>
      <p class="card-text"><?= nl2br(htmlspecialchars($p['description'], ENT_QUOTES)) ?></p>

      <?php if (!empty($p['whatsapp_number']) && !empty($p['name'])): ?>
  <a 
    href="https://wa.me/<?= preg_replace('/\D/', '', $p['whatsapp_number']) ?>?text=Hi%20I%20saw%20<?= urlencode($p['name']) ?>%20on%20KasiHub%20and%20I'm%20interested"
    class="btn btn-success mb-4"
    target="_blank"
  >
    💬 Chat via WhatsApp
  </a>
<?php else: ?>
  <div class="alert alert-warning">This seller has no WhatsApp contact listed.</div>
<?php endif; ?>

      <form action="order.php" method="POST" class="row gy-3">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">

        <div class="col-6">
          <label class="form-label">Quantity</label>
          <input type="number" name="quantity" value="1" min="1" class="form-control" required>
        </div>

        <div class="col-6">
          <label class="form-label">Delivery Method</label>
          <select name="delivery_id" class="form-select" required>
            <option value="">Choose…</option>
            <?php foreach ($deliveryMethods as $dm): ?>
              <option value="<?= $dm['id'] ?>">
                <?= htmlspecialchars($dm['method_name']) ?> (R<?= number_format($dm['cost'],2) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Payment Type</label>
          <select name="payment_type" class="form-select" required>
            <option value="">Choose…</option>
            <option value="cod">Cash on Delivery</option>
            <option value="eft">EFT (Bank Transfer)</option>
            <option value="eWallet">CashSend / eWallet</option>
          </select>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary w-100">Place Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

        <h3 class="fw-bold">R <?= number_format($p['price'],2) ?></h3>
        <p><?= nl2br(htmlspecialchars($p['description'], ENT_QUOTES)) ?></p>

        <a 
          href="https://wa.me/<?= preg_replace('/\D/','',$p['whatsapp_number']) ?>?text=Hi%20I%20saw%20<?= urlencode($p['name']) ?>%20on%20KasiHub%20and%20I'm%20interested"
          class="btn btn-success mb-4"
          target="_blank"
        >
          💬 Chat via WhatsApp
        </a>

        <form action="order.php" method="POST" class="row gy-3">
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">

          <div class="col-auto">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" value="1" min="1" class="form-control" required>
          </div>

          <div class="col-auto">
            <label class="form-label">Delivery Method</label>
            <select name="delivery_id" class="form-select" required>
              <option value="">Choose…</option>
              <?php foreach ($deliveryMethods as $dm): ?>
                <option value="<?= $dm['id'] ?>">
                  <?= htmlspecialchars($dm['method_name']) ?> (R<?= number_format($dm['cost'],2) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-auto">
            <label class="form-label">Payment Type</label>
            <select name="payment_type" class="form-select" required>
              <option value="">Choose…</option>
              <option value="cod">Cash on Delivery</option>
              <option value="eft">EFT (Bank Transfer)</option>
              <option value="eWallet">CashSend / eWallet</option>
            </select>
          </div>

          <div class="col-12">
            <button type="submit" class="btn btn-primary">Place Order</button>
          </div>
        </form>
      </div>

    </div>
  </div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>

</html>
