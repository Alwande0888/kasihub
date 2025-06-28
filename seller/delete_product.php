<?php


session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Make sure that the seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header('Location: login.php');
    exit;
}

// Connect to the database 
require_once __DIR__ . '/../backend/db_connect.php';

$conn = $mysqli;

$sid = (int) $_SESSION['seller_id'];

// This Validate incoming product ID
$pid = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($pid < 1) {
    die('Invalid product ID.');
}


$stmt = $conn->prepare("
    UPDATE products
       SET deleted = 1
     WHERE id = ? 
       AND seller_id = ?
");
$stmt->bind_param('ii', $pid, $sid);

if (!$stmt->execute()) {
    die('Failed to delete product: ' . $stmt->error);
}

$stmt->close();

// This Redirects back to the products list
header('Location: view_products.php');
exit;
?>

