<?php
session_start();
include 'db.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in first!'); window.location='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Handle cancel order action
if (isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    $stmt = $conn->prepare("UPDATE orders SET status='Cancelled' WHERE order_id=? AND user_id=? AND status='Pending'");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Order cancelled successfully.'); window.location='orders.php';</script>";
    exit();
}

// ✅ Fetch orders by category
$pending_orders = $conn->prepare("SELECT * FROM orders WHERE user_id=? AND status='Pending' ORDER BY created_at DESC");
$pending_orders->bind_param("i", $user_id);
$pending_orders->execute();
$res_pending = $pending_orders->get_result();
$pending = $res_pending->fetch_all(MYSQLI_ASSOC);
$pending_orders->close();

$delivered_orders = $conn->prepare("SELECT * FROM orders WHERE user_id=? AND status='Delivered' ORDER BY created_at DESC LIMIT 5");
$delivered_orders->bind_param("i", $user_id);
$delivered_orders->execute();
$res_delivered = $delivered_orders->get_result();
$delivered = $res_delivered->fetch_all(MYSQLI_ASSOC);
$delivered_orders->close();

$history_orders = $conn->prepare("SELECT * FROM orders WHERE user_id=? AND status='Delivered' ORDER BY created_at ASC");
$history_orders->bind_param("i", $user_id);
$history_orders->execute();
$res_history = $history_orders->get_result();
$history = $res_history->fetch_all(MYSQLI_ASSOC);
$history_orders->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Orders - Cap Store</title>
<style>
body { font-family: 'Poppins', sans-serif; background:#f5f5f5; margin:0; padding:0; }
header { background:#111; color:white; padding:15px; text-align:center; }
nav a { color:white; margin:0 10px; text-decoration:none; }
.container { width:90%; max-width:1100px; margin:30px auto; }
.tabs { display:flex; justify-content:center; margin-bottom:25px; }
.tab-btn {
    background:#ddd; border:none; padding:10px 20px;
    cursor:pointer; font-weight:600; margin:0 5px; border-radius:8px;
    transition:0.3s;
}
.tab-btn.active, .tab-btn:hover { background:#007bff; color:white; }
.tab-content { display:none; }
.tab-content.active { display:block; }

.order-box { background:#fff; padding:20px; margin-bottom:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.order-header { display:flex; justify-content:space-between; font-weight:bold; margin-bottom:10px; }
.order-items { border-top:1px solid #ccc; padding-top:10px; }
.item { display:flex; align-items:center; margin-bottom:12px; }
.item img { width:70px; height:70px; border-radius:8px; object-fit:cover; margin-right:15px; }
.item-info { flex:1; font-size:14px; }
.status { padding:4px 10px; border-radius:6px; color:white; font-weight:bold; }
.status.Pending { background-color:#ffc107; }
.status.Delivered { background-color:#28a745; }
.status.Cancelled { background-color:#dc3545; }
.cancel-btn {
    background:#dc3545; color:white; border:none;
    padding:6px 12px; border-radius:6px; cursor:pointer; font-size:14px;
}
.cancel-btn:hover { background:#b02a37; }
.empty { text-align:center; padding:40px; background:white; border-radius:10px; }
</style>
</head>
<body>

<header>
    <h1>🧢 Cap Store</h1>
    <nav>
        <a href="shop.php">Shop</a> |
        <a href="cart.php">Cart</a> |
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>Your Orders</h2>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="pending">🕒 Pending Orders</button>
        <button class="tab-btn" data-tab="delivered">🚚 Delivered (Recent)</button>
        <button class="tab-btn" data-tab="history">📜 Delivery History</button>
    </div>

    <!-- Pending Orders -->
    <div class="tab-content active" id="pending">
        <?php if (empty($pending)): ?>
            <div class="empty">No pending orders.</div>
        <?php else: ?>
            <?php foreach ($pending as $order): ?>
                <div class="order-box">
                    <div class="order-header">
                        <span>Order ID: #<?php echo $order['order_id']; ?></span>
                        <span class="status <?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                    </div>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    <p><strong>Ordered At:</strong> <?php echo $order['created_at']; ?></p>

                    <?php
                    $sql_items = "SELECT oi.quantity, oi.price, p.name, p.image 
                                  FROM order_items oi 
                                  JOIN products p ON oi.product_id = p.product_id 
                                  WHERE oi.order_id = ?";
                    $stmt2 = $conn->prepare($sql_items);
                    $stmt2->bind_param("i", $order['order_id']);
                    $stmt2->execute();
                    $res_items = $stmt2->get_result();
                    while ($item = $res_items->fetch_assoc()): ?>
                        <div class="item">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                            <div class="item-info">
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                Quantity: <?php echo $item['quantity']; ?><br>
                                Price: ₱<?php echo number_format($item['price'], 2); ?>
                            </div>
                        </div>
                    <?php endwhile; $stmt2->close(); ?>
                    <p><strong>Total:</strong> ₱<?php echo number_format($order['total'], 2); ?></p>
                    
                    <!-- Cancel Order Button -->
                    <form method="post" style="margin-top:10px;">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <button type="submit" name="cancel_order" class="cancel-btn">❌ Cancel Order</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Delivered Orders -->
    <div class="tab-content" id="delivered">
        <?php if (empty($delivered)): ?>
            <div class="empty">No delivered items yet.</div>
        <?php else: ?>
            <?php foreach ($delivered as $order): ?>
                <div class="order-box">
                    <div class="order-header">
                        <span>Order ID: #<?php echo $order['order_id']; ?></span>
                        <span class="status <?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                    </div>
                    <p><strong>Delivered At:</strong> <?php echo $order['created_at']; ?></p>
                    <p><strong>Total:</strong> ₱<?php echo number_format($order['total'], 2); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- History -->
    <div class="tab-content" id="history">
        <?php if (empty($history)): ?>
            <div class="empty">No delivery history available.</div>
        <?php else: ?>
            <?php foreach ($history as $order): ?>
                <div class="order-box">
                    <div class="order-header">
                        <span>Order ID: #<?php echo $order['order_id']; ?></span>
                        <span class="status <?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                    </div>
                    <p><strong>Total:</strong> ₱<?php echo number_format($order['total'], 2); ?></p>
                    <p><strong>Delivered At:</strong> <?php echo $order['created_at']; ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// ✅ Simple Tab Switching Script
const tabs = document.querySelectorAll('.tab-btn');
const contents = document.querySelectorAll('.tab-content');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(btn => btn.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
    });
});
</script>

</body>
</html>
