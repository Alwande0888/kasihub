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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = trim($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $seller_id   = $_SESSION['seller_id'];

    // Handle the image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName  = $_FILES['image']['tmp_name'];
        $origName = basename($_FILES['image']['name']);
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','gif'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } else {
            // Create a unique filename to avoid collisions
            $newName = uniqid('img_').'.'.$ext;
            $dest    = __DIR__ . '/../images/' . $newName;

            if (!move_uploaded_file($tmpName, $dest)) {
                $errors[] = "Failed to move uploaded file.";
            }
        }
    } else {
        $newName = null; 
    }

    
    if (!$name || !$description || !$price || !$category_id) {
        $errors[] = "Please fill in all required fields.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO products
              (name, description, price, image, category_id, seller_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssdsii",
            $name,
            $description,
            $price,
            $newName,
            $category_id,
            $seller_id
        );
        if ($stmt->execute()) {
            header("Location: view_products.php");
            exit();
        } else {
            $errors[] = "Failed to add product.";
        }
    }
}

// Fetch categories for the dropdown
$cats = $conn->query("SELECT id, name FROM categories");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Product – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2 class="mb-4">+ Add New Product</h2>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
    </div>
  <?php endif; ?>
  
  <form method="POST" action="" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="name" class="form-label">Product Name *</label>
      <input id="name" type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label for="description" class="form-label">Description *</label>
      <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
    </div>
    <div class="mb-3">
      <label for="price" class="form-label">Price (R) *</label>
      <input id="price" type="number" step="0.01" name="price" class="form-control" required>
    </div>
    <div class="mb-3">
      <label for="category" class="form-label">Category *</label>
      <select id="category" name="category_id" class="form-select" required>
        <option value="">Select category</option>
        <?php while($c = $cats->fetch_assoc()): ?>
          <option value="<?php echo $c['id']; ?>">
            <?php echo htmlspecialchars($c['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label for="image" class="form-label">Product Image (optional)</label>
      <input id="image" type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
    </div>
    <button type="submit" class="btn btn-success">Save Product</button>
    <a href="dashboard.php" class="btn btn-link">Back to Dashboard</a>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>
