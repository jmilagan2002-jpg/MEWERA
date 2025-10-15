<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch orders
$orders_result = $conn->query("SELECT * FROM orders WHERE user_id=$user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <style>
        body {font-family: Arial; background:#f5f5f5; margin:0; padding:20px;}
        .order-box {background:white; padding:20px; margin-bottom:20px; border-radius:10px;}
        h2 {margin-top:0;}
        table {width:100%; border-collapse:collapse;}
        th, td {border:1px solid #ccc; padding:10px; text-align:center;}
    </style>
</head>
<body>
<h1>My Orders</h1>
<?php while($order = $orders_result->fetch_assoc()): ?>
    <div class="order-box">
        <h2>Order #<?php echo $order['order_id']; ?> | <?php echo $order['status']; ?></h2>
        <p>Total: ₱<?php echo number_format($order['total'],2); ?> | Placed on: <?php echo $order['created_at']; ?></p>
        <table>
            <tr>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
            <?php
            $items = $conn->query("SELECT * FROM order_items WHERE order_id=".$order['order_id']);
            while($item = $items->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>₱<?php echo number_format($item['price'],2); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
<?php endwhile; ?>
</body>
</html>
