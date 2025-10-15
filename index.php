<?php
session_start();
include 'db.php';

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in first!'); window.location='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch hero section for homepage
$hero_image = 'images/default-hero.jpg';
$hero_title = 'Welcome to our Cap Store!';
$hero_subtitle = 'Check out our latest collection of caps!';

$sql_hero = "SELECT title, subtitle, background_image FROM hero_section WHERE hero_type='homepage' ORDER BY id DESC LIMIT 1";
if ($stmt = $conn->prepare($sql_hero)) {
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

// ✅ Function to count cart items
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
<title>Cap Store - Home</title>
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
    margin-right: 100px; /* keeps items slightly left of edge */
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



/* --- HERO SECTION --- */
.hero {
    position: relative;
    color: white;
    text-align: center;
    padding: 120px 20px;
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
.hero-content {
    position: relative;
    z-index: 1;
    max-width: 700px;
    margin: auto;
}
.hero h1 {
    font-size: 3rem;
    margin-bottom: 10px;
}
.hero p {
    font-size: 1.3rem;
}

/* --- MAIN CONTENT --- */
.container {
    width: 90%;
    margin: 50px auto;
    text-align: center;
}
.container h2 {
    font-size: 2rem;
    color: #333;
}
.container p {
    color: #555;
    font-size: 1.1rem;
    max-width: 700px;
    margin: 15px auto;
}
</style>
</head>
<body>

<!-- ✅ NAVBAR (same as shop.php) -->
<nav>
    <div class="logo"><a href="index.php">CAP STORE</a></div>
    <div class="controls">
        <a href="shop.php">Shop</a>
        <a href="orders.php">Orders</a>
        <a href="cart.php" class="cart-icon">🛒 Cart (<?php echo cartCount($conn, $user_id); ?>)</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<!-- ✅ HERO SECTION -->
<div class="hero">
    <div class="hero-content">
        <h1><?php echo htmlspecialchars($hero_title); ?></h1>
        <p><?php echo htmlspecialchars($hero_subtitle); ?></p>
    </div>
</div>

<!-- ✅ MAIN CONTENT -->
<div class="container">
    <h2>Discover Your Style</h2>
    <p>Welcome to Cap Store — your go-to destination for stylish and quality caps. Explore our shop to find the perfect match for your vibe.</p>
    <p><a href="shop.php" style="color:#28a745; font-weight:bold; text-decoration:none;">🛍️ Start Shopping →</a></p>
</div>

</body>
</html>
