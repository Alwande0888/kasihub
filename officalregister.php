<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);
require_once('backend/db_connect.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {

  $name     = trim($_POST['name']);
  $email    = trim($_POST['email']);
  $password = $_POST['password'];

  // Buyer‐only logic: insert into customers
  $isSeller = isset($_POST['is_seller']);
  $phone    = trim($_POST['phone']);

  if (!$name || !$email || !$password) {
    $errors[] = "Name, email, and password are required.";
  }

  // Prevent duplicate email in either table
  $chkCust = $conn->prepare("SELECT id FROM customers WHERE email=?");
  $chkCust->bind_param("s",$email);
  $chkCust->execute(); $chkCust->store_result();

  $chkSell = $conn->prepare("SELECT id FROM sellers WHERE email=?");
  $chkSell->bind_param("s",$email);
  $chkSell->execute(); $chkSell->store_result();

  if ($chkCust->num_rows || $chkSell->num_rows) {
    $errors[] = "Email already registered.";
  }

  if (empty($errors)) {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 1) Always create customer record
    $insCust = $conn->prepare("
      INSERT INTO customers (name,email,password,phone)
      VALUES (?,?,?,?)
    ");
    $insCust->bind_param("ssss",$name,$email,$hash,$phone);
    $insCust->execute();

    // 2) Optionally create seller record
    if ($isSeller) {
      $store      = trim($_POST['store_name']);
      $whatsapp   = trim($_POST['whatsapp']);
      $facebook   = trim($_POST['facebook']);
      $tiktok     = trim($_POST['tiktok']);

      // Simple validation
      if (!$store) {
        $errors[] = "Store name is required to register as seller.";
      } else {
        $insSell = $conn->prepare("
          INSERT INTO sellers
            (store_name, owner_name, email, password, phone, whatsapp_number, facebook_link, tiktok_link)
          VALUES (?,?,?,?,?,?,?,?)
        ");
        $insSell->bind_param(
          "ssssssss",
          $store,       // store_name
          $name,        // owner_name
          $email,
          $hash,
          $phone,
          $whatsapp,
          $facebook,
          $tiktok
        );
        $insSell->execute();
      }
    }

    if (empty($errors)) {
      header("Location: login.php?registered=1");
      exit();
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:600px;">
  <h2 class="mb-4">🔑 Create Your Account</h2>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>";?>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <!-- Common user info -->
    <div class="mb-3">
      <label class="form-label">Full Name *</label>
      <input type="text" name="name" class="form-control" required value="<?php echo isset($name)?htmlspecialchars($name):'';?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Email *</label>
      <input type="email" name="email" class="form-control" required value="<?php echo isset($email)?htmlspecialchars($email):'';?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Password *</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Phone Number</label>
      <input type="text" name="phone" class="form-control" value="<?php echo isset($phone)?htmlspecialchars($phone):'';?>">
    </div>

    <!-- Option to become a seller -->
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="is_seller" name="is_seller" <?php echo isset($_POST['is_seller'])?'checked':'';?>>
      <label class="form-check-label" for="is_seller">
        I also want to <strong>open a store</strong> and sell on KasiHub
      </label>
    </div>

    <!-- Seller fields (hidden by default until checkbox is checked) -->
    <div id="sellerFields" style="display:<?php echo isset($_POST['is_seller'])?'block':'none';?>;">
      <div class="mb-3">
        <label class="form-label">Store Name *</label>
        <input type="text" name="store_name" class="form-control" value="<?php echo isset($store)?htmlspecialchars($store):'';?>">
      </div>
      <div class="mb-3">
        <label class="form-label">WhatsApp Number</label>
        <input type="text" name="whatsapp" class="form-control" value="<?php echo isset($whatsapp)?htmlspecialchars($whatsapp):'';?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Facebook Link</label>
        <input type="url" name="facebook" class="form-control" value="<?php echo isset($facebook)?htmlspecialchars($facebook):'';?>">
      </div>
      <div class="mb-3">
        <label class="form-label">TikTok Link</label>
        <input type="url" name="tiktok" class="form-control" value="<?php echo isset($tiktok)?htmlspecialchars($tiktok):'';?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Register</button>
  </form>
</div>

<!-- Toggle seller fields -->
<script>
  document.getElementById('is_seller')
    .addEventListener('change', e => {
      document.getElementById('sellerFields').style.display =
        e.target.checked ? 'block' : 'none';
    });
</script>
</body>
</html>
