<?php


require __DIR__ . '/backend/db_connect.php';

$conn = $mysqli;

$role = $_GET ['role']?? 'customer';
$loginPage = $role === 'seller'
? 'seller/login.php'
:'customer/login.php';

if ($mysqli->connect_errno) {
  die("DB not connected: " . $mysqli->connect_error);
}

$token = $_GET['token']  ?? '';
$role  = $_GET['role']   ?? '';
if (!$token || !in_array($role, ['customer','seller'])) {
  exit("Invalid link.");
}


if ($role === 'customer') {
  $reset_table = 'customer_password_resets';
  $fk_col      = 'customer_id';
  $user_table  = 'customers';
  $id_col      = 'id';
} else {
  $reset_table = 'seller_password_resets';
  $fk_col      = 'seller_id';
  $user_table  = 'sellers';
  $id_col      = 'id';
}

$stmt = $mysqli->prepare(
  "SELECT $fk_col AS user_id
     FROM $reset_table
    WHERE token = ?
      AND expires_at > NOW()"
);
$stmt->bind_param('s', $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  exit("Invalid or expired link.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newHash = password_hash($_POST['password'], PASSWORD_DEFAULT);


  $u = $mysqli->prepare(
    "UPDATE $user_table
        SET password = ?
      WHERE $id_col = ?"
  );
  $u->bind_param('si', $newHash, $row['user_id']);
  $u->execute();

  
  $d = $mysqli->prepare(
    "DELETE FROM $reset_table WHERE token = ?"
  );
  $d->bind_param('s', $token);
  $d->execute();

  exit( "<p>Password updated! 
    <a href='https://kasihub.shop/$loginPage'>
      Login now
    </a>.
  </p>"
);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password – KasiHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
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
     <a 
    href="https://kasihub.shop/<?= $loginPage ?>" 
    class="btn btn-link"
  > 
    Back to <?= ucfirst($role) ?> Login
  </a>
  </div>

  
  <script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>
