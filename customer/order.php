<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

// Checks Authentication 
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
$customer_id = (int)$_SESSION['customer_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    if (!empty($_POST['confirm_payment'])) {
        $product_id   = intval($_POST['product_id']);
        $quantity     = intval($_POST['quantity']);
        $delivery_id  = intval($_POST['delivery_id']);
        $payment_type = $_POST['payment_type'] ?? '';
        $payment_ref  = trim($_POST['payment_ref'] ?? '');

        if ($product_id < 1 || $quantity < 1 || $delivery_id < 1 || !$payment_ref) {
            die('Invalid payment details.');
        }

        $sql = "INSERT INTO orders
            (product_id, customer_id, quantity,
             delivery_method_id, payment_method, payment_ref,
             payment_status, order_date)
         VALUES (?, ?, ?, ?, ?, ?, 'paid', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'iiiiss',
            $product_id,
            $customer_id,
            $quantity,
            $delivery_id,
            $payment_type,
            $payment_ref
        );
        if (!$stmt->execute()) {
            die('DB Error: '.$stmt->error);
        }
        header('Location: order_success.php?order_id='.$stmt->insert_id);
        exit;
    }

    
    $product_id   = intval($_POST['product_id']);
    $quantity     = intval($_POST['quantity']);
    $delivery_id  = intval($_POST['delivery_id']);
    $payment_type = $_POST['payment_type'] ?? '';

    if ($product_id < 1 || $quantity < 1 || $delivery_id < 1) {
        header('Location: browseproducts.php?error=invalid');
        exit;
    }

    // COD path
    if ($payment_type === 'cod') {
        $sql = "INSERT INTO orders
            (product_id, customer_id, quantity,
             delivery_method_id, payment_method,
             payment_status, order_date)
         VALUES (?, ?, ?, ?, ?, 'pending_cod', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'iiiis',
            $product_id,
            $customer_id,
            $quantity,
            $delivery_id,
            $payment_type
        );
        if (!$stmt->execute()) {
            die('DB Error: '.$stmt->error);
        }
        header('Location: order_success.php?order_id='.$stmt->insert_id);
        exit;
    }

    // EFT or eWallet den shows pay form
    $pstmt = $conn->prepare("
        SELECT p.name, p.price, dm.method_name, dm.cost
          FROM products p
          JOIN delivery_methods dm ON dm.id = ?
         WHERE p.id = ?
    ");
    $pstmt->bind_param('ii', $delivery_id, $product_id);
    $pstmt->execute();
    $pstmt->bind_result($prod_name, $prod_price, $del_name, $del_cost);
    if (!$pstmt->fetch()) {
        die('Product or delivery info not found.');
    }
    $pstmt->close();
    $total = ($prod_price * $quantity) + $del_cost;
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Payment — KasiHub</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div class="container py-5">
      <h1>Complete Your Payment</h1>
      <p><strong>Product:</strong> <?=htmlspecialchars($prod_name)?> × <?=$quantity?></p>
      <p><strong>Delivery:</strong> <?=htmlspecialchars($del_name)?> (R<?=number_format($del_cost,2)?>)</p>
      <p><strong>Total:</strong> R<?=number_format($total,2)?></p>
      <form method="POST" class="mt-4">
        <input type="hidden" name="confirm_payment" value="1">
        <input type="hidden" name="product_id" value="<?=$product_id?>">
        <input type="hidden" name="quantity" value="<?=$quantity?>">
        <input type="hidden" name="delivery_id" value="<?=$delivery_id?>">
        <input type="hidden" name="payment_type" value="<?=$payment_type?>">
        <div class="mb-3">
          <label class="form-label">Payment Reference (EFT / eWallet code)</label>
          <input type="text" name="payment_ref" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Confirm Payment</button>
      </form>
    </div>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
    </body>
    </html>
    <?php
    exit;
}

header('Location: browseproducts.php');
exit;

