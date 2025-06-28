<?php 
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);

    
    $isSeller   = isset($_POST['is_seller']);
    $storeName  = trim($_POST['store_name'] ?? '');
    $whatsapp   = trim($_POST['whatsapp'] ?? '');
    $facebook   = trim($_POST['facebook'] ?? '');
    $instagram  = trim($_POST['instagram'] ?? '');
    $tiktok     = trim($_POST['tiktok'] ?? '');

    
    if (!$name || !$email || !$password) {
        $errors[] = "Full name, email & password are required.";
    }
    if ($isSeller && !$storeName) {
        $errors[] = "Store name is required to register as a seller.";
    }

    // 1) Check customers table
    $chkCust = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $chkCust->bind_param("s", $email);
    $chkCust->execute();
    $chkCust->store_result();
    if ($chkCust->num_rows > 0) {
        $errors[] = "That email is already registered as a customer. Please log in.";
    }

    // 2) If they want to sell, they need to  check sellers table
    if ($isSeller) {
        $chkSell = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
        $chkSell->bind_param("s", $email);
        $chkSell->execute();
        $chkSell->store_result();
        if ($chkSell->num_rows > 0) {
            $errors[] = "That email is already registered as a seller. Please log in.";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        
        $insCust = $conn->prepare("
          INSERT INTO customers (name,email,password,phone,address)
          VALUES (?,?,?,?,?)
        ");
        $insCust->bind_param("sssss",
            $name, $email, $hash, $phone, $address
        );
        $insCust->execute();

        
        if ($isSeller) {
            $insSell = $conn->prepare("
              INSERT INTO sellers
                (store_name,owner_name,email,password,phone,whatsapp_number,facebook_link,instagram_link,tiktok_link)
              VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $insSell->bind_param("sssssssss",
                $storeName,
                $name,
                $email,
                $hash,
                $phone,
                $whatsapp,
                $facebook,
                $instagram,
                $tiktok
            );
            $insSell->execute();
        }

        header("Location: login.php?registered=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign Up – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:500px;">
  <h2 class="mb-4">Create Your KasiHub Account</h2>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    
    <div class="mb-3">
      <label class="form-label">Full Name *</label>
      <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Email *</label>
      <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Password *</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Phone Number</label>
      <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Address</label>
      <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($address ?? '') ?></textarea>
    </div>

    
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="is_seller" name="is_seller" <?= isset($_POST['is_seller'])?'checked':'' ?>>
      <label class="form-check-label" for="is_seller">
        I also want to <strong>open a store</strong> and sell on KasiHub
      </label>
    </div>

    
    <div id="sellerFields" style="display: <?= isset($_POST['is_seller'])?'block':'none' ?>;">
      <div class="mb-3">
        <label class="form-label">Store Name *</label>
        <input type="text" name="store_name" class="form-control" value="<?= htmlspecialchars($storeName ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">WhatsApp Number</label>
        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($whatsapp ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Facebook Link</label>
        <input type="url" name="facebook" class="form-control" value="<?= htmlspecialchars($facebook ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Instagram Link</label>
        <input type="url" name="instagram" class="form-control" value="<?= htmlspecialchars($instagram ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">TikTok Link</label>
        <input type="url" name="tiktok" class="form-control" value="<?= htmlspecialchars($tiktok ?? '') ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">Register</button>
    <p class="text-center mt-3">
      Already have an account? <a href="login.php">Log in</a>
    </p>
  </form>
</div>

<script>
  document.getElementById('is_seller').addEventListener('change', function(){
    document.getElementById('sellerFields').style.display = this.checked ? 'block' : 'none';
  });
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>

