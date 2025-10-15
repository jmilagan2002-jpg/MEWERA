<?php
session_start();
include 'db.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in first!'); window.location='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = null;
$cart_items = [];

// ✅ Fetch user's latest cart
$stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ? ORDER BY cart_id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
$stmt->fetch();
$stmt->close();

// ✅ Handle quantity update
if (isset($_POST['action']) && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);

    if ($_POST['action'] === 'increase') {
        $conn->query("UPDATE cart_items SET quantity = quantity + 1 WHERE item_id = $item_id");
    } elseif ($_POST['action'] === 'decrease') {
        $conn->query("UPDATE cart_items SET quantity = GREATEST(quantity - 1, 1) WHERE item_id = $item_id");
    } elseif ($_POST['action'] === 'remove') {
        $conn->query("DELETE FROM cart_items WHERE item_id = $item_id");
    }

    echo "<script>window.location='cart.php';</script>";
    exit();
}

// ✅ Fetch all cart items
if ($cart_id) {
    $stmt = $conn->prepare("SELECT ci.item_id, ci.product_id, p.name, p.image, p.description, ci.quantity, ci.price
                            FROM cart_items ci
                            JOIN products p ON ci.product_id = p.product_id
                            WHERE ci.cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ✅ Checkout logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);
    $selected_items = $_POST['selected_items'] ?? [];

    if (empty($selected_items)) {
        echo "<script>alert('Please select at least one item.'); window.history.back();</script>";
        exit();
    }

    $total = 0;
    $items_to_insert = [];
    foreach ($cart_items as $item) {
        if (in_array($item['item_id'], $selected_items)) {
            $total += $item['price'] * $item['quantity'];
            $items_to_insert[] = $item;
        }
    }

    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, address, payment_method, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("idss", $user_id, $total, $address, $payment_method);

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        $stmt->close();

        $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
        foreach ($items_to_insert as $item) {
            $stmt_items->bind_param("iisid", $order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']);
            $stmt_items->execute();
        }
        $stmt_items->close();

        // Remove selected items
        $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
        $types = str_repeat('i', count($selected_items));
        $stmt_delete = $conn->prepare("DELETE FROM cart_items WHERE item_id IN ($placeholders)");
        $stmt_delete->bind_param($types, ...$selected_items);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Clean empty cart
        $conn->query("DELETE FROM carts WHERE cart_id = $cart_id AND NOT EXISTS (SELECT 1 FROM cart_items WHERE cart_id = $cart_id)");
        unset($_SESSION['cart']);

        echo "<script>alert('Order placed successfully!'); window.location='orders.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart - Cap Store</title>
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f9fafb;
    margin: 0;
    padding-bottom: 120px;
}
header {
    background: #111;
    color: white;
    padding: 15px;
    text-align: center;
}
nav a {
    color: white;
    margin: 0 10px;
    text-decoration: none;
}
.container { width: 80%; margin: 40px auto; }
.cart-item-box {
    background: white;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.cart-item-box img { width: 100px; height: 100px; border-radius: 10px; object-fit: cover; margin-right: 20px; }
.item-info { flex-grow: 1; }
.qty-btn { background: #007bff; color: white; border: none; border-radius: 5px; width: 28px; height: 28px; cursor: pointer; }
.remove-btn { background: #dc3545; border: none; color: white; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
.checkout-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #fff; padding: 15px 30px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    display: flex; justify-content: space-between; align-items: center;
}
.checkout-btn { background: #28a745; color: white; padding: 12px 25px; border-radius: 10px; border: none; cursor: pointer; }
#checkoutForm {
    display: none; background: white; padding: 25px; border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px;
}
.empty-cart {
    text-align: center; background: white; padding: 40px;
    border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
</style>
</head>
<body>
<header>
    <h1>🧢 Cap Store</h1>
    <nav>
        <a href="shop.php">Shop</a>
        <a href="orders.php">Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
<h2>Your Cart</h2>

<?php if (empty($cart_items)): ?>
    <div class="empty-cart"><p>Your cart is empty 🛒</p></div>
<?php else: ?>
<form method="post" id="cartForm">
    <?php foreach ($cart_items as $item): ?>
        <div class="cart-item-box">
            <input type="checkbox" name="selected_items[]" value="<?= $item['item_id']; ?>" checked>
            <img src="<?= htmlspecialchars($item['image']); ?>" alt="Product">
            <div class="item-info">
                <strong><?= htmlspecialchars($item['name']); ?></strong>
                <p><?= htmlspecialchars($item['description']); ?></p>
                <p>Price: ₱<?= number_format($item['price'], 2); ?></p>
                <div class="qty-control">
                    <button type="submit" name="action" value="decrease" class="qty-btn" formaction="?item_id=<?= $item['item_id']; ?>">−</button>
                    <span><?= $item['quantity']; ?></span>
                    <button type="submit" name="action" value="increase" class="qty-btn" formaction="?item_id=<?= $item['item_id']; ?>">+</button>
                    <button type="submit" name="action" value="remove" class="remove-btn" formaction="?item_id=<?= $item['item_id']; ?>">Remove</button>
                </div>
                <p>Total: ₱<?= number_format($item['price'] * $item['quantity'], 2); ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <div id="checkoutForm">
        <h3>Checkout Details</h3>
        <textarea name="address" required placeholder="Enter delivery address" style="width:100%;height:80px;"></textarea><br><br>
        <select name="payment_method" required>
            <option value="">--Select Payment--</option>
            <option value="Cash on Delivery">Cash on Delivery</option>
            <option value="GCash">GCash</option>
            <option value="Credit Card">Credit Card</option>
        </select><br><br>
        <button type="submit" name="checkout" class="checkout-btn">✅ Place Order</button>
    </div>

    <div class="checkout-bar">
        <span>Total Amount: ₱<span id="grandTotal">0.00</span></span>
        <button type="button" class="checkout-btn" id="proceedBtn">Proceed to Checkout</button>
    </div>
</form>

<script>
const checkboxes = document.querySelectorAll('input[type="checkbox"][name="selected_items[]"]');
const grandTotalEl = document.getElementById('grandTotal');
const checkoutForm = document.getElementById('checkoutForm');
const proceedBtn = document.getElementById('proceedBtn');

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.cart-item-box').forEach(box => {
        const checkbox = box.querySelector('input[type="checkbox"]');
        if (checkbox.checked) {
            const price = parseFloat(box.querySelector('p:nth-of-type(2)').textContent.replace('Price: ₱', '').trim());
            const quantity = parseInt(box.querySelector('.qty-control span').textContent);
            total += price * quantity;
        }
    });
    grandTotalEl.textContent = total.toFixed(2);
}
updateTotal();
checkboxes.forEach(chk => chk.addEventListener('change', updateTotal));

proceedBtn.addEventListener('click', () => {
    checkoutForm.style.display = 'block';
    proceedBtn.style.display = 'none';
    window.scrollTo({ top: checkoutForm.offsetTop, behavior: 'smooth' });
});
</script>

<?php endif; ?>
</div>
</body>
</html>
