<?php
session_start();
include 'db.php';

// ✅ Require user login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in first!'); window.location='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Ensure product_id and quantity are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo "<script>alert('Invalid request!'); window.location='shop.php';</script>";
    exit();
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

// ✅ Prevent invalid quantity
if ($quantity < 1) {
    echo "<script>alert('Invalid quantity!'); window.location='shop.php';</script>";
    exit();
}

// ✅ Fetch product details safely
$stmt = $conn->prepare("SELECT name, price, image FROM products WHERE product_id = ? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($name, $price, $image);
$stmt->fetch();
$stmt->close();

if (empty($name)) {
    echo "<script>alert('Product not found!'); window.location='shop.php';</script>";
    exit();
}

// ✅ Find latest active cart for this user
$stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ? ORDER BY cart_id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
$stmt->fetch();
$stmt->close();

// ✅ If no cart found, create new one
if (empty($cart_id)) {
    $stmt = $conn->prepare("INSERT INTO carts (user_id, created_at) VALUES (?, NOW())");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_id = $stmt->insert_id;
    $stmt->close();
}

// ✅ Check if this product already exists in cart
$stmt = $conn->prepare("SELECT item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
$stmt->bind_param("ii", $cart_id, $product_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // 🔁 Update quantity
    $stmt->bind_result($item_id, $existing_qty);
    $stmt->fetch();
    $new_qty = $existing_qty + $quantity;
    $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE item_id = ?");
    $update->bind_param("ii", $new_qty, $item_id);
    $update->execute();
    $update->close();
} else {
    // ➕ Add as new item
    $insert = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $insert->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
    $insert->execute();
    $insert->close();
}
$stmt->close();

// ✅ Update cart count in session (for navbar display)
if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}
$_SESSION['cart_count'] += $quantity;

// ✅ Optional: update session cart details
$_SESSION['cart'][$product_id] = [
    'name' => $name,
    'price' => $price,
    'quantity' => (isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] + $quantity : $quantity),
    'image' => $image
];

// ✅ Redirect with success message
echo "<script>alert('{$name} added to your cart!'); window.location='cart.php';</script>";
exit();
?>
