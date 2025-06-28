<?php

require __DIR__ . '/backend/db_connect.php';

$conn = $mysqli;

$role = $_GET['role'] ?? 'customer';
$loginPage = $role === 'seller'
  ? 'seller/login.php'
  : 'customer/login.php';
 
if ($mysqli->connect_errno) {
  die("DB not connected: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $role  = $_POST['role'];              

  
  if ($role === 'customer') {
    $user_table   = 'customers';
    $id_col       = 'id';
    $reset_table  = 'customer_password_resets';
    $fk_col       = 'customer_id';
  } else {
    $user_table   = 'sellers';
    $id_col       = 'id';
    $reset_table  = 'seller_password_resets';
    $fk_col       = 'seller_id';
  }

  
  $stmt = $mysqli->prepare(
    "SELECT $id_col 
       FROM $user_table 
      WHERE email = ?"
  );
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if ($user) {
    
    $token   = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', time() + 3600); // for 1 hour

    $ins = $mysqli->prepare(
      "INSERT INTO $reset_table
         ($fk_col, token, expires_at)
       VALUES (?, ?, ?)"
    );
    $ins->bind_param('iss', $user[$id_col], $token, $expires);
    $ins->execute();

    // The customer will get an email. 
     $link = "https://kasihub.shop/reset_password.php?token=$token&role=$role";
    $subject = 'KasiHub Password Reset';
    $msg     = "Hi there, \n\n Click this link to rese your KasiHub pssword: \n\n$link\n\nThis link expires in 1 hour.";
    mail($email,'Kasihub Password Reset', 
     $msg, 'From:no-reply@kasihub.shop');
  }

  
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password – KasiHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS for looks ! -->
  <link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
    rel="stylesheet"
  >
  <style>
    body {
      background: #f8f9fa;
    }
    .card {
      max-width: 400px;
      margin: 60px auto;
      border: none;
      border-radius: .75rem;
      box-shadow: 0 0 .5rem rgba(0,0,0,.1);
    }
    .card-header {
      background: #fff;
      border-bottom: none;
      text-align: center;
      font-weight: bold;
      font-size: 1.25rem;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">Forgot Your Password?</div>
    <div class="card-body">
      <form method="post" novalidate>
        <div class="mb-3">
          <label for="email" class="form-label">Email address</label>
          <input 
            type="email" 
            class="form-control" 
            id="email" 
            name="email" 
            placeholder="you@example.com"
            required
          >
        </div>
        <div class="mb-3">
          <label for="role" class="form-label">I am a</label>
          <select class="form-select" id="role" name="role">
            <option value="customer">Customer</option>
            <option value="seller">Seller</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">
          Send Reset Link
        </button>
      </form>
    </div>
    <div class="card-footer text-center">
      <a href="login.php">Back to Login</a>
    </div>
  </div>

  
  <script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>