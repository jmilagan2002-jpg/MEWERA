<?php
session_start();
include 'db.php';

// Optional: helpful dev mode (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Get logged in user (shop can be public if desired)
$user_id = $_SESSION['user_id'] ?? null;

// ✅ Fetch hero section
$hero_image = 'images/default-hero.jpg';
$hero_title = 'Welcome to our Shop!';
$hero_subtitle = 'Browse our amazing collection of caps.';

$sql_hero = "SELECT title, subtitle, background_image FROM hero_section WHERE hero_type = ? ORDER BY id DESC LIMIT 1";
if ($stmt = $conn->prepare($sql_hero)) {
    $hero_type = 'shop';
    $stmt->bind_param("s", $hero_type);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $hero_image = !empty($row['background_image']) ? $row['background_image'] : $hero_image;
            $hero_title = !empty($row['title']) ? $row['title'] : $hero_title;
            $hero_subtitle = !empty($row['subtitle']) ? $row['subtitle'] : $hero_subtitle;
        }
    }
    $stmt->close();
}

// ✅ Fetch all products
$sql_products = "SELECT product_id, name, details, price, image FROM products ORDER BY product_id ASC";
$products = [];
if ($stmt2 = $conn->prepare($sql_products)) {
    if ($stmt2->execute()) {
        $res2 = $stmt2->get_result();
        $products = $res2->fetch_all(MYSQLI_ASSOC);
    }
    $stmt2->close();
}

// ✅ Function to count cart items from database
function cartCount($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0)
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int)$count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Shop - Cap Store</title>
<link rel="stylesheet" href="style.css" />
<style>
body {
    font-family: Arial;
    margin: 0;
    background: #f9f9f9;
}

/* --- NAVBAR --- */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
    background: #333;
    color: #fff;
}

.logo a {
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
    transition: color 0.2s ease;
}
.logo a:hover { color: #28a745; }

.controls {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-right: 100px; /* pushes them slightly to the left so not at edge */
}

.controls a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 5px;
    transition: all 0.2s ease;
}

.controls a:hover {
    color: #28a745;
    background: rgba(255,255,255,0.1);
}




/* --- HERO --- */
.hero {
    position: relative;
    color: white;
    text-align: center;
    padding: 100px 20px;
    background-image: url('<?php echo htmlspecialchars($hero_image); ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border-bottom: 5px solid #333;
}
.hero::after {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
}
.hero-content { position: relative; z-index: 1; max-width: 700px; margin: auto; }
.hero h1 { font-size: 2.8rem; margin-bottom: 10px; }
.hero p { font-size: 1.2rem; }

/* --- PRODUCTS --- */
.container { width: 90%; margin: 40px auto; }
.product-list { display: flex; flex-wrap: wrap; gap: 25px; justify-content: center; }
.product {
    border: 1px solid #ccc;
    border-radius: 12px;
    background: #fff;
    padding: 15px;
    text-align: center;
    width: 300px;
    min-height: 480px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.product:hover {
    transform: scale(1.03);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}
.product img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 10px;
}
.product h3 { margin: 8px 0 5px; font-size: 1.2rem; color: #222; }
.details { color: #555; font-size: 14px; min-height: 50px; margin-bottom: 8px; }
.price { color: #28a745; font-weight: bold; font-size: 1.1rem; margin-bottom: 12px; }
button {
    background: #28a745;
    color: #fff;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.95rem;
}
button:hover { background: #218838; }
input[type="number"] {
    width: 65px;
    padding: 5px;
    margin-left: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
    text-align: center;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav>
    <div class="logo"><a href="index.php">CAP STORE</a></div>
    <div class="controls">
        <a href="shop.php">Shop</a>
        <a href="orders.php">Orders</a>
        <a href="cart.php" class="cart-icon">🛒 Cart (<?php echo cartCount($conn, $user_id); ?>)</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<!-- Hero Section -->
<div class="hero">
    <div class="hero-content">
        <h1><?php echo htmlspecialchars($hero_title); ?></h1>
        <p><?php echo htmlspecialchars($hero_subtitle); ?></p>
    </div>
</div>

<!-- Products List -->
<div class="container">
    <h2 style="text-align:center; margin-bottom:20px;">Our Products</h2>
    <div class="product-list">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <div>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="details"><?php echo htmlspecialchars($product['details']); ?></p>
                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                    </div>
                    <form action="add-to-cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>" />
                        <label for="quantity_<?php echo $product['product_id']; ?>">Qty:</label>
                        <input id="quantity_<?php echo $product['product_id']; ?>" type="number" name="quantity" value="1" min="1" max="10" />
                        <button type="submit">Add to Cart</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;">No products available yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
