<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ✅ Example data (you can replace with actual database data)
$best_selling = ['Black Cap', 'Blue Bini', 'Gray Cap', 'Green Cap'];
$sales_count = [120, 95, 80, 60];

$weekly_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weekly_sales = [1500, 1800, 2100, 1700, 2500, 3000, 2800];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Cap Store</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: #f9fafb;
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background: #111;
    color: white;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 30px;
}
.sidebar h1 {
    margin-bottom: 40px;
    font-size: 22px;
}
.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    background: #222;
    width: 80%;
    padding: 10px 15px;
    margin: 8px 0;
    border-radius: 6px;
    text-align: center;
    transition: 0.3s;
}
.sidebar a:hover {
    background: #555;
}
.logout {
    background: #e74c3c;
}

/* Main content */
.main-content {
    margin-left: 250px;
    padding: 40px;
    width: calc(100% - 250px);
}
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.dashboard-header h2 {
    margin: 0;
}
.chart-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    margin-top: 30px;
}
canvas {
    width: 100%;
    height: 350px;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h1>🧢 Cap Store</h1>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_hero.php">Hero Section</a>
    <a href="admin_products.php">Products</a>
    <a href="admin_orders.php">Orders</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-header">
        <h2>Admin Dashboard</h2>
        <p>Welcome back, Admin!</p>
    </div>

    <!-- Best Selling Products Chart -->
    <div class="chart-container">
        <h3>🛍️ Best Selling Products</h3>
        <canvas id="bestSellingChart"></canvas>
    </div>

    <!-- Weekly Revenue Chart -->
    <div class="chart-container">
        <h3>💰 Weekly Revenue</h3>
        <canvas id="weeklyRevenueChart"></canvas>
    </div>
</div>

<script>
// ✅ Best Selling Products Chart
const bestSellingCtx = document.getElementById('bestSellingChart').getContext('2d');
new Chart(bestSellingCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($best_selling); ?>,
        datasets: [{
            label: 'Units Sold',
            data: <?php echo json_encode($sales_count); ?>,
            backgroundColor: '#007bff'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// ✅ Weekly Revenue Chart
const weeklyRevenueCtx = document.getElementById('weeklyRevenueChart').getContext('2d');
new Chart(weeklyRevenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($weekly_labels); ?>,
        datasets: [{
            label: 'Revenue (₱)',
            data: <?php echo json_encode($weekly_sales); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
