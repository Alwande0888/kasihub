<?php


session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

if (!isset($_SESSION['seller_id'])) {
    header('Location: login.php');
    exit;
}

require_once(__DIR__ . '/../backend/db_connect.php');

$conn = $mysqli;

$sid = (int) $_SESSION['seller_id'];

// Must display all the seller's products
$sql = "
  SELECT
    p.id,
    p.Name           AS product_name,
    p.description,
    p.price,
    p.image,
    c.name           AS category_name
  FROM products p
  LEFT JOIN categories c ON p.category_id = c.id
  WHERE p.seller_id = ?
    AND p.deleted   = 0
  ORDER BY p.id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('DB prepare failed: ' . $conn->error);
}
$stmt->bind_param('i', $sid);
if (!$stmt->execute()) {
    die('DB execute failed: ' . $stmt->error);
}

$res = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Products • KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1 class="mb-4">📦 My Products</h1>
    <div class="mb-4">
      <a href="add_product.php" class="btn btn-success">+ Add New Product</a>
      <a href="dashboard.php"     class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <?php if ($res->num_rows === 0): ?>
      <div class="alert alert-info">You haven’t added any products yet.</div>
    <?php else: ?>
      <div class="row gy-4">
        <?php while ($p = $res->fetch_assoc()): ?>
          <div class="col-md-4">
            <div class="card h-100">
             <?php 
  $imgName = basename($p['image']);
  $imgUrl  = "/images/{$imgName}";
?>
<?php if (!empty($p['image'])): ?>
  <img 
    src="<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>" 
    class="card-img-top" 
    style="object-fit:cover; height:400px; width:100%;"
    alt="<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>"
  >
<?php else: ?>
  <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height:200px;">
    No Image
  </div>
<?php endif; ?>

              <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= htmlspecialchars($p['product_name']) ?></h5>
                <p class="card-subtitle text-muted mb-2">
                  <?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?>
                </p>
                <?php if ($p['description']): ?>
                  <p class="card-text"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                <?php endif; ?>
                <p class="fw-bold mt-auto">R <?= number_format($p['price'],2) ?></p>
              </div>

              <div class="card-footer bg-white">
                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="delete_product.php?id=<?= $p['id'] ?>"
                   onclick="return confirm('Delete this product?');"
                   class="btn btn-danger btn-sm">Delete</a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>










