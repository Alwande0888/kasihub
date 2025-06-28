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
$sid = (int)$_SESSION['seller_id'];

$errors  = [];
$success = false;

// Fetch existing store info
$stmt = $conn->prepare("
  SELECT store_name,
         owner_name,
         phone,
         whatsapp_number,
         address,
         facebook_link,
         instagram_link,
         tiktok_link,
         profile_image
    FROM sellers
   WHERE id=?
   LIMIT 1
");
$stmt->bind_param('i',$sid);
$stmt->execute();
$stmt->bind_result(
  $store_name,
  $owner_name,
  $phone,
  $whatsapp,
  $address,
  $facebook,
  $instagram,
  $tiktok,
  $profile_image
);
$stmt->fetch();
$stmt->close();

// 3) Handle form POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $store_name = trim($_POST['store_name']);
  $owner_name = trim($_POST['owner_name']);
  $phone      = trim($_POST['phone']);
  $whatsapp   = trim($_POST['whatsapp']);
  $address    = trim($_POST['address']);
  $facebook   = trim($_POST['facebook']);
  $instagram  = trim($_POST['instagram_link']);
  $tiktok     = trim($_POST['tiktok_link']);

  //  image upload 
  if (!empty($_FILES['profile_image']['tmp_name']) && $_FILES['profile_image']['error']===UPLOAD_ERR_OK) {
    $tmp  = $_FILES['profile_image']['tmp_name'];
    $orig = basename($_FILES['profile_image']['name']);
    $ext  = strtolower(pathinfo($orig,PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
      $errors[] = "Profile image must be JPG/PNG/GIF";
    } else {
      if ($profile_image && file_exists(__DIR__.'/../images/'.$profile_image)) {
        unlink(__DIR__.'/../images/'.$profile_image);
      }
      $newfn = uniqid('store_').'.'.$ext;
      if (move_uploaded_file($tmp, __DIR__.'/../images/'.$newfn)) {
        $profile_image = $newfn;
      } else {
        $errors[] = "Failed to upload new image.";
      }
    }
  }

  if (!$store_name || !$owner_name) {
    $errors[] = "Store name and owner name are required.";
  }

  if (empty($errors)) {
    $upd = $conn->prepare("
      UPDATE sellers SET
        store_name      = ?,
        owner_name      = ?,
        phone           = ?,
        whatsapp_number = ?,
        address         = ?,
        facebook_link   = ?,
        instagram_link  = ?,
        tiktok_link     = ?,
        profile_image   = ?
      WHERE id = ?
    ");
    $upd->bind_param(
      'sssssssssi',
      $store_name,
      $owner_name,
      $phone,
      $whatsapp,
      $address,
      $facebook,
      $instagram,
      $tiktok,
      $profile_image,
      $sid
    );
    if ($upd->execute()) {
      $_SESSION['store_name'] = $store_name;
      $success = true;
    } else {
      $errors[] = "Database update failed.";
    }
    $upd->close();
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Store • KasiHub</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .preview { border:1px solid #ddd; padding:1rem; margin-top:2rem; background:#fff; }
    .preview img { max-width:100px; border-radius:50%; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width:700px;">

    <h1>Edit My Store</h1>

    <?php if($success): ?>
      <div class="alert alert-success">Store updated successfully!</div>
    <?php endif; ?>
    <?php if($errors): ?>
      <div class="alert alert-danger">
        <?php foreach($errors as $e) echo "<p>".htmlspecialchars($e)."</p>"; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Store Name *</label>
        <input type="text" name="store_name" class="form-control" required
               value="<?=htmlspecialchars($store_name)?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Owner Name *</label>
        <input type="text" name="owner_name" class="form-control" required
               value="<?=htmlspecialchars($owner_name)?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Profile Image</label><br>
        <?php if($profile_image && file_exists(__DIR__.'/../images/'.$profile_image)): ?>
          <img src="../images/<?=htmlspecialchars($profile_image)?>" class="mb-2"><br>
        <?php endif; ?>
        <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.gif">
      </div>
      <div class="mb-3">
        <label class="form-label">Phone Number</label>
        <input type="text" name="phone" class="form-control"
               value="<?=htmlspecialchars($phone)?>">
      </div>
      <div class="mb-3">
        <label class="form-label">WhatsApp Number</label>
        <input type="text" name="whatsapp" class="form-control"
               value="<?=htmlspecialchars($whatsapp)?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?=htmlspecialchars($address)?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Facebook URL</label>
        <input type="url" name="facebook" class="form-control"
               value="<?=htmlspecialchars($facebook)?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Instagram URL</label>
        <input type="url" name="instagram_link" class="form-control"
               value="<?=htmlspecialchars($instagram)?>">
      </div>
      <div class="mb-3">
        <label class="form-label">TikTok URL</label>
        <input type="url" name="tiktok_link" class="form-control"
               value="<?=htmlspecialchars($tiktok)?>">
      </div>

      <button class="btn btn-primary">Save Changes</button>
      <a href="dashboard.php" class="btn btn-link">Cancel</a>
    </form>

    <!-- Back to Dashboard -->
    <div class="mt-3">
      <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Live Preview of  the Storefront for owner to see  -->
    <div class="preview">
      <h2 class="mt-4"><?=htmlspecialchars($store_name)?></h2>
      <?php if($profile_image): ?>
        <img src="../images/<?=htmlspecialchars($profile_image)?>">
      <?php endif; ?>
      <p><?=nl2br(htmlspecialchars($address))?></p>
      <p>
        <?php if($facebook): ?>
          <a href="<?=htmlspecialchars($facebook)?>" target="_blank">Facebook</a>
        <?php endif;?>
        <?php if($instagram): ?>
          • <a href="<?=htmlspecialchars($instagram)?>" target="_blank">Instagram</a>
        <?php endif;?>
        <?php if($tiktok): ?>
          • <a href="<?=htmlspecialchars($tiktok)?>" target="_blank">TikTok</a>
        <?php endif;?>
      </p>
    </div>

  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>


