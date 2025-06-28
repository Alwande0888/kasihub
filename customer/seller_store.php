<?php

session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;


if (empty($_GET['seller_id'])) {
    die('Store not specified.');
}
$sid = (int)$_GET['seller_id'];

// Fetch store info 
$stmt = $conn->prepare("
  SELECT store_name,
         address,
         profile_image,
         facebook_link,
         instagram_link,
         tiktok_link,
         whatsapp_number,
         phone
    FROM sellers
   WHERE id = ?
   LIMIT 1
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$stmt->bind_result(
  $store_name,
  $address,
  $profile_image,
  $facebook,
  $instagram,
  $tiktok,
  $whatsapp_number,
  $phone
);
$stmt->fetch();
$stmt->close();
if (!$store_name) {
    die('Store not found.');
}

//Fetch products
$stmt = $conn->prepare("
  SELECT id,
         Name          AS product_name,
         description,
         price,
         image
    FROM products
   WHERE seller_id = ?
   ORDER BY id DESC
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

//Fetch rating stats
$stmt = $conn->prepare("
  SELECT ROUND(AVG(rating),1) AS avg_rating,
         COUNT(*)            AS total_reviews
    FROM store_reviews
   WHERE seller_id = ?
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

//Fetch recent reviews
$stmt = $conn->prepare("
  SELECT sr.rating,
         sr.review_text,
         sr.created_at,
         c.name AS customer_name
    FROM store_reviews sr
    JOIN customers c ON sr.customer_id = c.id
   WHERE sr.seller_id = ?
   ORDER BY sr.created_at DESC
   LIMIT 5
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($store_name) ?> • KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .store-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; }
    .store-header img { border-radius:50%; width:80px; height:80px; object-fit:cover; }
    .rating { font-size:1.25rem; margin-bottom:2rem; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">

    <!-- Store Header -->
    <div class="store-header">
      <?php if ($profile_image): ?>
        <img src="../images/<?= htmlspecialchars($profile_image) ?>" alt="Logo">
      <?php endif; ?>
      <div>
        <h1><?= htmlspecialchars($store_name) ?></h1>
        <p class="mb-1"><?= nl2br(htmlspecialchars($address)) ?></p>
        <p class="mb-0">
          <?php if ($facebook): ?>
            <a href="<?= htmlspecialchars($facebook) ?>" target="_blank">Facebook</a>
          <?php endif; ?>
          <?php if ($instagram): ?>
            • <a href="<?= htmlspecialchars($instagram) ?>" target="_blank">Instagram</a>
          <?php endif; ?>
          <?php if ($tiktok): ?>
            • <a href="<?= htmlspecialchars($tiktok) ?>" target="_blank">TikTok</a>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <!-- Contact Buttons -->
    <div class="mb-4">
      <?php if ($whatsapp_number): 
        $wa = preg_replace('/\D/','',$whatsapp_number); ?>
        <a href="https://wa.me/<?= $wa ?>" target="_blank" class="btn btn-success btn-sm me-2">
          💬 Chat on WhatsApp
        </a>
      <?php endif; ?>
      <?php if ($phone): ?>
        <a href="tel:<?= htmlspecialchars($phone) ?>" class="btn btn-primary btn-sm">
          📞 Call <?= htmlspecialchars($phone) ?>
        </a>
      <?php endif; ?>
    </div>

    <!-- Ratings Summary -->
    <div class="rating">
      ⭐ <?= $stats['avg_rating'] ?: '0.0' ?> (<?= $stats['total_reviews'] ?> reviews)
    </div>

    <!-- Products Grid -->
    <h3 class="mb-3">Products</h3>
    <?php if ($products->num_rows === 0): ?>
      <div class="alert alert-info">No products available.</div>
    <?php else: ?>
      <div class="row gy-4 mb-5">
        <?php while ($p = $products->fetch_assoc()): ?>
          <div class="col-12 col-sm-6 col-lg-4">
            <div class="card h-100">
              <?php 
            $imgName = basename($p['image']);
            $imgUrl  = "/images/{$imgName}";
          ?>
          <?php if ($p['image']): ?>
            <img 
              src="<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>" 
              class="card-img-top" 
              style="height:400px; object-fit:cover;" 
              alt="<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>"
            >
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= htmlspecialchars($p['product_name']) ?></h5>
                <?php if ($p['description']): ?>
                  <p class="card-text"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                <?php endif; ?>
                <p class="fw-bold mt-auto">R<?= number_format($p['price'],2) ?></p>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

    <!-- Recent Reviews -->
    <h3 class="mb-3">Recent Reviews</h3>
    <?php if ($reviews->num_rows === 0): ?>
      <div class="alert alert-secondary">No reviews yet.</div>
    <?php else: ?>
      <?php while ($r = $reviews->fetch_assoc()): ?>
        <div class="border p-3 mb-3">
          <p class="mb-1">
            <strong><?= htmlspecialchars($r['customer_name']) ?></strong> — <?= $r['rating'] ?> ★
          </p>
          <p><?= nl2br(htmlspecialchars($r['review_text'])) ?></p>
          <p class="text-muted small"><?= date('d M Y', strtotime($r['created_at'])) ?></p>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>

    <a href="browseproducts.php" class="btn btn-secondary mt-4">← Back to Marketplace</a>
  </div>
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>

