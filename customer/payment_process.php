<?php
session_start();

require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

if (!isset($_SESSION['customer_id'])) {
  header('Location: login.php');
  exit;
}
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

// Prepare update based on payment method
$fields = [];
$params = [];
$types  = '';

if (!empty($_POST['eft_ref'])) {
  $fields[]   = "payment_ref = ?";
  $params[]   = $_POST['eft_ref'];
  $types     .= 's';
} elseif (!empty($_POST['wallet_ref'])) {
  $fields[]   = "payment_ref = ?";
  $params[]   = $_POST['wallet_ref'];
  $types     .= 's';
}


$fields[]  = "payment_status = 'paid'";
$setClause = implode(', ', $fields);

$sql = "UPDATE orders SET $setClause WHERE id = ? AND buyer_id = ?";
$params[]   = $orderId;
$params[]   = $_SESSION['customer_id'];
$types     .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
  header('Location: order_success.php?order_id=' . $orderId);
  exit;
} else {
  die("Payment update failed: " . $stmt->error);
}
