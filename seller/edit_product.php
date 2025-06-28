<?php
session_start();
if (!isset($_SESSION['seller_id'])) {
  header("Location: login.php");
  exit();
}

ini_set('display_errors',1);
error_reporting(E_ALL);
require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

$errors = [];
$seller_id = $_SESSION['seller_id'];

// 1) Get product ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: view_products.php");
  exit();
}
$product_id = (int)$_GET['id'];

// This fetches product information 
$stmt = $conn->prepare("
  SELECT name, description, price, image, category_id 
  FROM products 
  WHERE id = ? AND seller_id = ?
");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
  header("Location: view_products.php");
  exit();
}
$p = $result->fetch_assoc();

//  form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = trim($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $newImage    = $p['image'];

    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName  = $_FILES['image']['tmp_name'];
        $origName = basename($_FILES['image']['name']);
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','gif'];

        if (in_array($ext, $allowed)) {
            $newImage = uniqid('img_').'.'.$ext;
            move_uploaded_file($tmpName, __DIR__ . '/../images/' . $newImage);
        } else {
            $errors[] = "Only JPG, PNG & GIF allowed.";
        }
    }

    
    if (!$name || !$description || !$price || !$category_id) {
        $errors[] = "All fields except image are required.";
    }

    if (empty($errors)) {
        $upd = $conn->prepare("
          UPDATE products
          SET name=?, description=?, price=?, image=?, category_id=?
          WHERE id=? AND seller_id=?
        ");
        $upd->bind_param(
          "ssdssii",
          $name,
          $description,
          $price,
          $newImage,
          $category_id,
          $product_id,
          $seller_id
        );
        if ($upd->execute()) {
          header("Location: view_products.php");
          exit();
        } else {
          $errors[] = "Could not update product.";
        }
    }
}

// Fetch categories for dropdown
$cats = $conn->query("SELECT id, name FROM categories");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Product – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2 class="mb-4">✏️ Edit Product</h2>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
    </div>
  <?php endif; ?>
  <form method="POST" action="" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Name *</label>
      <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($p['name']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description *</label>
      <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($p['description']); ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Price (R) *</label>
      <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($p['price']); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Category *</label>
      <select name="category_id" class="form-select" required>
        <?php while($c = $cats->fetch_assoc()): ?>
          <option value="<?php echo $c['id']; ?>" <?php if($c['id']==$p['category_id']) echo 'selected';?>>
            <?php echo htmlspecialchars($c['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Current Image</label><br>
      <?php if($p['image'] && file_exists(__DIR__.'/../images/'.$p['image'])): ?>
        <img src="../images/<?php echo htmlspecialchars($p['image']);?>" style="max-width:150px;"><br><br>
      <?php else: ?>
        <div>No image uploaded.</div><br>
      <?php endif; ?>
      <label class="form-label">Replace Image</label>
      <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
    </div>
    <button type="submit" class="btn btn-primary">Update Product</button>
    <a href="view_products.php" class="btn btn-link">Cancel</a>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!--Your Main JavaScript File -->
  <script src="main.js"></script>
</body>
</html>
