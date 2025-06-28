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

$id = $_SESSION['seller_id'];
$errors = [];
$success = false;

// fetch existing info dog 
$stmt = $conn->prepare("
  SELECT store_name,
         owner_name,
         phone,
         whatsapp_number,
         facebook_link,
         instagram_link,
         tiktok_link,
         profile_image,
         address
    FROM sellers
   WHERE id=?
");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->bind_result(
  $store_name,
  $owner_name,
  $phone,
  $whatsapp,
  $facebook,
  $instagram,
  $tiktok,
  $profile_image,
  $address
);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  // text fields
  $store_name = trim($_POST['store_name']);
  $owner_name = trim($_POST['owner_name']);
  $phone      = trim($_POST['phone']);
  $whatsapp   = trim($_POST['whatsapp']);
  $facebook   = trim($_POST['facebook']);
  $instagram  = trim($_POST['instagram_link']);
  $tiktok     = trim($_POST['tiktok_link']);
  $address    = trim($_POST['address']);

  // handle profile image upload
  if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error']===UPLOAD_ERR_OK) {
    $tmp   = $_FILES['profile_image']['tmp_name'];
    $orig  = basename($_FILES['profile_image']['name']);
    $ext   = strtolower(pathinfo($orig,PATHINFO_EXTENSION));
    $allowed=['jpg','jpeg','png','gif'];
    if (!in_array($ext,$allowed)) {
      $errors[]="Only JPG, PNG, GIF allowed for profile pic.";
    } else {
      if($profile_image && file_exists(__DIR__.'/../images/'.$profile_image)){
        unlink(__DIR__.'/../images/'.$profile_image);
      }
      $newfn = uniqid('prof_').'.'.$ext;
      $dest  = __DIR__.'/../images/'.$newfn;
      if (move_uploaded_file($tmp,$dest)) {
        $profile_image = $newfn;
      } else {
        $errors[]="Failed to upload profile image.";
      }
    }
  }

  if (!$store_name||!$owner_name) {
    $errors[]="Store & Owner name required.";
  }

  if (empty($errors)) {
    $upd = $conn->prepare("
      UPDATE sellers
         SET store_name      = ?,
             owner_name      = ?,
             phone           = ?,
             whatsapp_number = ?,
             facebook_link   = ?,
             instagram_link  = ?,
             tiktok_link     = ?,
             profile_image   = ?,
             address         = ?
       WHERE id = ?
    ");
    $upd->bind_param(
      "sssssssssi",
      $store_name,
      $owner_name,
      $phone,
      $whatsapp,
      $facebook,
      $instagram,
      $tiktok,
      $profile_image,
      $address,
      $id
    );
    if ($upd->execute()) {
      $_SESSION['store_name']=$store_name;
      $success=true;
    } else {
      $errors[]="Failed to update profile.";
    }
    $upd->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Profile – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:600px;">
  <h2>Edit Store Profile</h2>
  <?php if($success):?><div class="alert alert-success">Profile updated!</div><?php endif;?>
  <?php if($errors):?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>";?></div><?php endif;?>
  <form method="POST" action="" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Store Name *</label>
      <input type="text" name="store_name" class="form-control" required value="<?=htmlspecialchars($store_name)?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Owner Name *</label>
      <input type="text" name="owner_name" class="form-control" required value="<?=htmlspecialchars($owner_name)?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Profile Photo</label><br>
      <?php if($profile_image && file_exists(__DIR__.'/../images/'.$profile_image)):?>
        <img src="../images/<?=htmlspecialchars($profile_image)?>" class="rounded-circle mb-2" style="width:100px;height:100px;"><br>
      <?php endif;?>
      <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
    </div>
    <div class="mb-3">
      <label class="form-label">Phone Number</label>
      <input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($phone)?>">
    </div>
    <div class="mb-3">
      <label class="form-label">WhatsApp Number</label>
      <input type="text" name="whatsapp" class="form-control" value="<?=htmlspecialchars($whatsapp)?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Address</label>
      <textarea name="address" class="form-control" rows="2"><?=htmlspecialchars($address)?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Facebook Link</label>
      <input type="url" name="facebook" class="form-control" value="<?=htmlspecialchars($facebook)?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Instagram Link</label>
      <input type="url" name="instagram_link" class="form-control" value="<?=htmlspecialchars($instagram)?>">
    </div>
    <div class="mb-3">
      <label class="form-label">TikTok Link</label>
      <input type="url" name="tiktok_link" class="form-control" value="<?=htmlspecialchars($tiktok)?>">
    </div>
    <button class="btn btn-primary">Save Changes</button>
    <a href="dashboard.php" class="btn btn-link">Cancel</a>
  </form>

  
  <div class="mt-3">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


  <script src="main.js"></script>
</body>
</html>

