<?php
session_start();
include 'db.php';

if (isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);

    $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id=?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Item removed from cart.'); window.location='cart.php';</script>";
}
?>
