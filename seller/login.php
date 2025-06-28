<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../backend/db_connect.php');

$conn = $mysqli;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email']);
  $password = $_POST['password'];

  if (!$email || !$password) {
    $errors[] = "Both fields are required.";
  } else {
    $stmt = $conn->prepare("SELECT id, store_name, password FROM sellers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
      $stmt->bind_result($id, $store, $hash);
      $stmt->fetch();

      if (password_verify($password, $hash)) {
        $_SESSION['seller_id']    = $id;
        $_SESSION['store_name']   = $store;
        header("Location: dashboard.php");
        exit;
      } else {
        $errors[] = "Incorrect password.";
      }
    } else {
      $errors[] = "Seller not found.";
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Seller Login – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5" style="max-width:480px;">
    <h2 class="mb-4">🔐 Seller Login</h2>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">Registration successful! Please log in.</div>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" class="form-control" required placeholder="Enter your Email Address">
      </div>
      <div class="mb-3">
        <label for="pass" class="form-label">Password</label>
        <input id="pass" type="password" name="password" class="form-control" required placeholder="Enter your Password">
      </div>
      <button class="btn btn-success w-100">Login</button>
      <a href="register.php" class="btn btn-link d-block text-center mt-2">Create an account</a>
    </form>
    <p class="text-center mt-3">
      <a href="../forgot_password.php?role=customer">Forgot your password?</a>

    </p>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script src="main.js"></script>
</body>
</html>
