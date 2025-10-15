<?php
session_start();
include 'db.php';

// ✅ Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ✅ Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Order status updated successfully!'); window.location='admin_orders.php';</script>";
    exit();
}

// ✅ Fetch orders with joined user and order_items info
$sql = "
    SELECT 
        o.order_id,
        o.user_id,
        u.username AS user_name,      -- ✅ Correctly get username
        o.full_name,
        o.total,
        o.address,
        o.payment_method,
        o.status,
        o.created_at,
        oi.product_name,
        oi.quantity,
        oi.price
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN users u ON o.user_id = u.user_id   -- ✅ Fixed column name
    ORDER BY o.created_at DESC
";

$result = $conn->query($sql);

// ✅ Query error handling
if (!$result) {
    die('SQL Error: ' . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Orders - Cap Store</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f9f9f9;
    margin: 0;
    padding: 20px;
}
h2 {
    text-align: center;
    color: #333;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
th, td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: center;
}
th {
    background: #333;
    color: white;
}
button {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.update {
    background: #007bff;
    color: white;
}
.update:hover {
    background: #0056b3;
}
</style>
</head>
<body>

<?php include __DIR__ . '/admin_navbar.php'; ?>

<h2>📦 Manage Orders</h2>

<table>
<tr>
    <th>Order ID</th>
    <th>User</th>
    <th>Products</th>
    <th>Total (₱)</th>
    <th>Address</th>
    <th>Payment</th>
    <th>Status</th>
    <th>Created</th>
    <th>Action</th>
</tr>

<?php
// ✅ Group orders by order_id
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[$row['order_id']]['info'] = [
        'user_name' => $row['user_name'] ?? 'Unknown User',
        'full_name' => $row['full_name'],
        'total' => $row['total'],
        'address' => $row['address'],
        'payment_method' => $row['payment_method'],
        'status' => $row['status'],
        'created_at' => $row['created_at']
    ];
    $orders[$row['order_id']]['items'][] = [
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price']
    ];
}

// ✅ Display grouped results
if (!empty($orders)):
    foreach ($orders as $order_id => $order):
?>
<tr>
    <form method="POST">
        <td><?= $order_id ?></td>
        <td><?= htmlspecialchars($order['info']['user_name']) ?></td>
        <td>
            <?php foreach ($order['items'] as $item): ?>
                <?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)<br>
            <?php endforeach; ?>
        </td>
        <td>₱<?= number_format($order['info']['total'], 2) ?></td>
        <td><?= htmlspecialchars($order['info']['address']) ?></td>
        <td><?= htmlspecialchars($order['info']['payment_method']) ?></td>
        <td>
            <select name="status">
                <option value="Pending" <?= $order['info']['status']=='Pending'?'selected':''; ?>>Pending</option>
                <option value="Delivered" <?= $order['info']['status']=='Delivered'?'selected':''; ?>>Delivered</option>
                <option value="Cancelled" <?= $order['info']['status']=='Cancelled'?'selected':''; ?>>Cancelled</option>
            </select>
        </td>
        <td><?= $order['info']['created_at'] ?></td>
        <td>
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            <button type="submit" name="update_status" class="update">Update</button>
        </td>
    </form>
</tr>
<?php
    endforeach;
else:
    echo '<tr><td colspan="9">No orders found.</td></tr>';
endif;
?>
</table>

</body>
</html>
