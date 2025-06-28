<?php
session_start();

ini_set('display_errors',1);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;
// Alias mysqli to $conn so existing code works without further edits
$conn = $mysqli;

//  Get filters from URL
$catFilter = isset($_GET['cat']) && is_numeric($_GET['cat'])
             ? intval($_GET['cat']) : 0;
$search    = isset($_GET['q'])
             ? $conn->real_escape_string(trim($_GET['q'])) : '';
$sort      = isset($_GET['sort'])
             ? $_GET['sort'] : '';  // one of: 'az','za','pl','ph'


$sql = "
  SELECT 
    p.id, p.name, p.description, p.price, p.image,
    c.name    AS category,
    s.id      AS seller_id,
    s.store_name
  FROM products p
  JOIN categories c ON p.category_id = c.id
  JOIN sellers    s ON p.seller_id   = s.id
";

$clauses = [];
if ($catFilter)     $clauses[] = "p.category_id = {$catFilter}";
if ($search !== '') $clauses[] = "p.name LIKE '%{$search}%'";
if ($clauses)       $sql .= " WHERE " . implode(' AND ', $clauses);

//  Apply sorting
switch ($sort) {
  case 'az': $sql .= " ORDER BY p.name ASC";   break;
  case 'za': $sql .= " ORDER BY p.name DESC";  break;
  case 'pl': $sql .= " ORDER BY p.price ASC";  break;
  case 'ph': $sql .= " ORDER BY p.price DESC"; break;
  default:    $sql .= " ORDER BY p.id DESC";   break;
}

$res = $conn->query($sql);

$catRes = $conn->query("SELECT id,name FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse Products – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link rel="stylesheet" href="customer.css">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1 class="mb-4">🛒 Browse Products</h1>
    <div class="d-flex justify-content-between mb-4">
      <a href="track_order_list.php" class="btn btn-secondary">My Orders</a>
      <a href="logout.php"           class="btn btn-outline-danger">Logout</a>
    </div>

    <!-- FILTER / SORT / SEARCH FORM -->
    <form class="row g-2 mb-4" method="GET" action="">
      <div class="col-md-3">
        <select name="cat" class="form-select">
          <option value="0">— All Categories —</option>
          <?php while($c = $catRes->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>"
            <?= $c['id']==$catFilter ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="sort" class="form-select">
          <option value="">— Sort By —</option>
          <option value="az" <?= $sort==='az' ? 'selected' : '' ?>>Name A→Z</option>
          <option value="za" <?= $sort==='za' ? 'selected' : '' ?>>Name Z→A</option>
          <option value="pl" <?= $sort==='pl' ? 'selected' : '' ?>>Price ↑</option>
          <option value="ph" <?= $sort==='ph' ? 'selected' : '' ?>>Price ↓</option>
        </select>
      </div>
      <div class="col-md-4 position-relative">
        <input type="text"
               name="q"
               class="form-control"
               placeholder="Search products…"
               value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
        <button type="submit"
                name="search_btn"
                class="btn btn-outline-primary position-absolute top-0 end-0 h-100"
                style="width:100px;">
          Search
        </button>
      </div>
      <div class="col-md-2">
        <button type="submit"
                name="filter_btn"
                class="btn btn-primary w-100">
          Apply
        </button>
      </div>
    </form>

    <!-- PRODUCTS GRID -->
    <?php if ($res && $res->num_rows > 0): ?>
      <div class="row g-4">
        <?php while($p = $res->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card h-100 shadow-sm animate__animated">
           <?php 
        
        $imgName = basename($p['image']);
        $imgUrl  = "/images/{$imgName}";
      ?>
      <img
        src="<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>"
        class="card-img-top"
        style="height:350px; object-fit:cover;"
        alt="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
      >

            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></h5>
              <p class="text-muted mb-1">
                <?= htmlspecialchars($p['category'], ENT_QUOTES) ?> •
                <a href="seller_store.php?seller_id=<?= $p['seller_id'] ?>">
                  <?= htmlspecialchars($p['store_name'], ENT_QUOTES) ?>
                </a>
              </p>
              <p class="fw-bold mb-3">R <?= number_format($p['price'], 2) ?></p>
              <a href="product.php?id=<?= $p['id'] ?>"
                 class="btn btn-primary mt-auto">View Details</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        No products found matching your criteria.
      </div>
    <?php endif; ?>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="main.js"></script>
</body>
</html>


