<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in first!'); window.location='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Get selected items
if (!isset($_POST['selected_items'])) {
    echo "<script>alert('No items selected.'); window.location='cart.php';</script>";
    exit();
}

$selected_items = json_decode($_POST['selected_items'], true);
if (empty($selected_items)) {
    echo "<script>alert('No items selected.'); window.location='cart.php';</script>";
    exit();
}

// Fetch selected items
$placeholders = implode(',', array_fill(0, count($selected_items), '?'));
$types = str_repeat('i', count($selected_items));

$sql = "SELECT ci.item_id, ci.product_id, p.name, p.details AS description, p.image, ci.quantity, ci.price 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.item_id IN ($placeholders)";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$selected_items);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle checkout
if (isset($_POST['checkout'])) {
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];

    if (empty($address) || empty($payment_method)) {
        echo "<script>alert('Please enter your address and select payment method.');</script>";
    } else {
        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (user_id, address, payment_method, total) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $user_id, $address, $payment_method, $total);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert each item into order_items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        $stmt->close();

        // Remove items from cart
        $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
        $types = str_repeat('i', count($selected_items));
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id IN ($placeholders)");
        $stmt->bind_param($types, ...$selected_items);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Order placed successfully!'); window.location='orders.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Summary - Cap Store</title>
<style>
/* keep your previous CSS, plus form styling */
.checkout-form { margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 10px; }
.checkout-form label { display:block; margin-bottom:5px; font-weight:bold; }
.checkout-form input, .checkout-form select { width: 100%; padding:8px; margin-bottom:15px; border-radius:5px; border:1px solid #ccc; }
.checkout-btn { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; width:100%; font-size:16px; }
.checkout-btn:hover { background-color: #218838; }
</style>
</head>
<body>
<header>
    <h1>🧢 Cap Store</h1>
</header>

<div class="container">
    <h2>🛍️ Order Summary</h2>

    <?php foreach ($items as $item): ?>
        <div class="item-box" data-id="<?php echo $item['item_id']; ?>">
            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Product">
            <div class="item-info">
                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                <p><?php echo htmlspecialchars($item['description']); ?></p>
                <p class="price">₱<?php echo number_format($item['price'], 2); ?></p>
                <p>Quantity: <?php echo (int)$item['quantity']; ?></p>
                <p>Subtotal: ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="total">
        Grand Total: ₱<span id="grandTotal"><?php echo number_format($total, 2); ?></span>
    </div>

    <!-- Checkout Form -->
    <form method="POST" class="checkout-form">
        <label>Delivery Address:</label>
        <input type="text" name="address" placeholder="Enter your delivery address" required>

        <label>Payment Method:</label>
        <select name="payment_method" required>
            <option value="">Select Payment Method</option>
            <option value="Cash on Delivery">Cash on Delivery</option>
            <option value="GCash">GCash</option>
            <option value="PayPal">PayPal</option>
        </select>

        <button type="submit" name="checkout" class="checkout-btn">Checkout</button>
    </form>
</div>
</body>
</html>
