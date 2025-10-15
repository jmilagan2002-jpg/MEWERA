<!-- admin_navbar.php -->
<style>
    .admin-navbar {
        background-color: #222;
        padding: 15px;
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    .admin-navbar a {
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        background: #444;
        border-radius: 5px;
        transition: 0.3s;
    }
    .admin-navbar a:hover {
        background: #28a745;
    }
    .admin-navbar a.active {
        background: #007bff;
    }
</style>

<div class="admin-navbar">
    <a href="admin_hero.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_hero.php' ? 'active' : ''; ?>">Hero</a>
    <a href="admin_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_products.php' ? 'active' : ''; ?>">Products</a>
    <a href="admin_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'active' : ''; ?>">Orders</a>
    <a href="logout.php">Logout</a>
</div>
