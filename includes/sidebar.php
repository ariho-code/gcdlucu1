<?php
// Ensure session is started and role is available
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'user'; // Default role if not set
} elseif ($_SESSION['user_id'] == 1) {
    $_SESSION['role'] = 'admin'; // Ensure user ID 1 is admin
}
?>

<div class="sidebar">
    <br><br><br>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link"><i class="bi bi-house-door"></i><span>Dashboard</span></a>
        </li>
        <li class="nav-item">
            <a href="procurement.php" class="nav-link"><i class="bi bi-cart-plus"></i><span>Procurement</span></a>
        </li>
        <li class="nav-item">
            <a href="stock.php" class="nav-link"><i class="bi bi-box-seam"></i><span>Stock</span></a>
        </li>
        <li class="nav-item">
            <a href="sales.php" class="nav-link"><i class="bi bi-cash-stack"></i><span>Sales</span></a>
        </li>
        <li class="nav-item">
            <a href="categories.php" class="nav-link"><i class="bi bi-list-ul"></i><span>Categories</span></a>
        </li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="admin_settings.php" class="nav-link"><i class="bi bi-gear"></i><span>Admin Settings</span></a>
            </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
        </li>
    </ul>
</div>

<style>
.sidebar {
    width: 200px;
    background: linear-gradient(135deg, #000080, #000066);
    padding: 20px 10px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    transition: width 0.3s ease;
    z-index: 999;
    overflow: hidden;
}

.sidebar.collapsed {
    width: 60px;
}

.nav-link {
    color: whitesmoke;
    padding: 10px 15px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    margin-bottom: 10px;
    white-space: nowrap;
}

.nav-link i {
    margin-right: 10px;
    font-size: 1.2rem;
    min-width: 20px;
}

.nav-link span {
    display: inline-block;
}

.sidebar.collapsed .nav-link span {
    display: none;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(5px);
}

@media (max-width: 768px) {
    .sidebar { width: 60px; }
    .sidebar.collapsed { width: 60px; }
    .sidebar .nav-link span { display: none; }
}
</style>