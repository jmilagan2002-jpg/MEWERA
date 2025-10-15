<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);
    $selected_items = json_decode($_POST['selected_items'], true);

    if (empty($selected_items)) {
        die("No items selected.");
    }

    $total = 0;
    foreach ($selected_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, status, created_at) VALUES (?, ?, 'Pending', NOW())");
    $stmt->bind_param("id", $user_id, $total);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
    foreach ($selected_items as $item) {
        $stmt->bind_param("iisid", $order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    $stmt->close();

    // Optional: Clear cart items
    $item_ids = array_column($selected_items, 'id');
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
    $types = str_repeat('i', count($item_ids));
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id IN ($placeholders)");
    $stmt->bind_param($types, ...$item_ids);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Order placed successfully!'); window.location='my_orders.php';</script>";
    exit();
}
?>
