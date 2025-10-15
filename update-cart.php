<?php
session_start();
include 'db.php';

if (!isset($_POST['item_id']) || !isset($_POST['action'])) {
    header("Location: cart.php");
    exit();
}

$item_id = (int)$_POST['item_id'];
$action = $_POST['action'];

// Get current quantity
$stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE item_id=?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$stmt->bind_result($quantity);
$stmt->fetch();
$stmt->close();

if ($action === 'remove') {
    // Remove item completely
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id=?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
} else {
    if ($quantity !== null) {
        if ($action === 'increase') {
            $quantity++;
        } elseif ($action === 'decrease') {
            $quantity = max(1, $quantity - 1); // prevent less than 1
        }

        // Update quantity
        $stmt = $conn->prepare("UPDATE cart_items SET quantity=? WHERE item_id=?");
        $stmt->bind_param("ii", $quantity, $item_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: cart.php");
exit();
?>
