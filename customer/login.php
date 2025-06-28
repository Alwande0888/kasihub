<?php

session_start();
ini_set('display_errors',1);
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
        $stmt = $conn->prepare("SELECT id,name,password FROM customers WHERE email=?");
        $stmt->bind_param("s",$email);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows===1) {
            $stmt->bind_result($id,$name,$hash);
            $stmt->fetch();
            if (password_verify($password,$hash)) {
                $_SESSION['customer_id']=$id;
                $_SESSION['customer_name']=$name;
                header("Location: browseproducts.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login – KasiHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:480px; margin-top:50px;">
  <h2 class="mb-4">🔐 Customer Login</h2>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success">Registration successful. Please log in.</div>
  <?php endif; ?>
  <form method="POST" action="">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100">Login</button>

    <p class="text-center mt-3">
      No account? <a href="register.php">Register here</a>
    </p>
    <p class="text-center">
      <a href="../forgot_password.php?role=customer">Forgot your password?</a>

    </p>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>
