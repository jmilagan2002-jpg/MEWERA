<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uploadDir = "uploads/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

// Add Product
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $details = trim($_POST['details']);
    $price = trim($_POST['price']);
    $imagePath = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . time() . "_" . $imageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    $stmt = $conn->prepare("INSERT INTO products (name, details, price, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $name, $details, $price, $imagePath);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Product added successfully!'); window.location='admin_products.php';</script>";
    exit();
}

// Delete Product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Product deleted successfully!'); window.location='admin_products.php';</script>";
    exit();
}

// Edit Product
if (isset($_POST['edit_product'])) {
    $id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $details = trim($_POST['details']);
    $price = trim($_POST['price']);
    $imagePath = trim($_POST['current_image']);

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . time() . "_" . $imageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    $stmt = $conn->prepare("UPDATE products SET name=?, details=?, price=?, image=? WHERE product_id=?");
    $stmt->bind_param("ssdsi", $name, $details, $price, $imagePath, $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Product updated successfully!'); window.location='admin_products.php';</script>";
    exit();
}

// Fetch Products
$result = $conn->query("SELECT * FROM products ORDER BY product_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Products</title>
<style>
body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 20px; }
h2 { margin-top: 30px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 10px; overflow: hidden; }
th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
th { background: #333; color: white; }
form { margin-bottom: 20px; background: #fff; padding: 20px; border-radius: 10px; }
input, textarea { width: 100%; padding: 8px; margin-bottom: 10px; }
button { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
button.edit { background: #007bff; color: white; }
button.delete { background: #dc3545; color: white; }
img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
</style>
</head>
<body>

<?php include 'admin_navbar.php'; ?>

<h2>Add Product</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Product Name" required>
    <textarea name="details" placeholder="Product Details" required></textarea>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <input type="file" name="image" accept="image/*" required>
    <button type="submit" name="add_product">Add Product</button>
</form>

<h2>Existing Products</h2>
<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Details</th>
    <th>Price</th>
    <th>Image</th>
    <th>Actions</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <form method="POST" enctype="multipart/form-data">
        <td><?php echo $row['product_id']; ?></td>
        <td><input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>"></td>
        <td><textarea name="details"><?php echo htmlspecialchars($row['details']); ?></textarea></td>
        <td><input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($row['price']); ?>"></td>
        <td>
            <?php if ($row['image']): ?>
                <img src="<?php echo $row['image']; ?>" alt="Product">
            <?php endif; ?>
            <input type="file" name="image" accept="image/*">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($row['image']); ?>">
        </td>
        <td>
            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
            <button type="submit" name="edit_product" class="edit">Edit</button>
            <a href="admin_products.php?delete=<?php echo $row['product_id']; ?>" onclick="return confirm('Delete this product?')">
                <button type="button" class="delete">Delete</button>
            </a>
        </td>
    </form>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
