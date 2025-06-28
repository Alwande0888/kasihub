<?php
// For debugging. I wanted to check all the errors 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

$errors = [];
$success = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name     = trim($_POST['store_name']);
    $owner_name     = trim($_POST['owner_name']);
    $email          = trim($_POST['email']);
    $password       = $_POST['password'];
    $phone          = trim($_POST['phone']);
    $whatsapp       = trim($_POST['whatsapp']);
    $facebook_link  = trim($_POST['facebook_link']);
    $instagram_link = trim($_POST['instagram_link']);
    $tiktok_link    = trim($_POST['tiktok_link']);
    $address        = trim($_POST['address']);

    
    if (empty($store_name) || empty($owner_name) || empty($email) || empty($password)) {
        $errors[] = "All fields marked * are required.";
    }

    // Prevent duplicate emails
    $check = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = "Email already registered. Please log in instead.";
    }

    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO sellers 
              (store_name,
               owner_name,
               email,
               password,
               phone,
               whatsapp_number,
               facebook_link,
               instagram_link,
               tiktok_link,
               address)
            VALUES 
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssssss",
            $store_name,
            $owner_name,
            $email,
            $hashed_password,
            $phone,
            $whatsapp,
            $facebook_link,
            $instagram_link,
            $tiktok_link,
            $address
        );

        if ($stmt->execute()) {
            header("Location: login.php?registered=true");
            exit();
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Your Store – KasiHub Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">🛍️ Register Your Kasi Store</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Store Name -->
        <div class="mb-3">
            <label class="form-label">Store Name *</label>
            <input type="text" name="store_name" class="form-control" required placeholder="Enter the Store's name">
        </div>

        <!-- Owner Name -->
        <div class="mb-3">
            <label class="form-label">Owner Name *</label>
            <input type="text" name="owner_name" class="form-control" required placeholder="Enter the Store Owner's Name">
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required placeholder="Enter your email address">
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" required placeholder="Create a Password">
        </div>

        <!-- Phone -->
        <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="Please enter your phone number">
        </div>

        <!-- Address -->
        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" placeholder="Enter your store's address">
        </div>

        <!-- WhatsApp -->
        <div class="mb-3">
            <label class="form-label">WhatsApp Number</label>
            <input type="text" name="whatsapp" class="form-control" placeholder="Enter your WhatsApp number">
        </div>

        <!-- Facebook Link -->
        <div class="mb-3">
            <label class="form-label">Facebook Link</label>
            <input type="url" name="facebook_link" class="form-control" placeholder="Your Facebook profile link">
        </div>

        <!-- Instagram Link -->
        <div class="mb-3">
            <label class="form-label">Instagram Link</label>
            <input type="url" name="instagram_link" class="form-control" placeholder="Your Instagram profile link">
        </div>

        <!-- TikTok Link -->
        <div class="mb-3">
            <label class="form-label">TikTok Link</label>
            <input type="url" name="tiktok_link" class="form-control" placeholder="Your TikTok profile link">
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-success d-block mx-auto">
            Register Store
        </button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="main.js"></script>
</body>
</html>
