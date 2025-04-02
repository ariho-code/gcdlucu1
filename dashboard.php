<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db_connect.php';

// Ensure role is set for user ID 1 as admin
if ($_SESSION['user_id'] == 1 && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    $_SESSION['role'] = 'admin';
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = 1");
    $stmt->execute();
} elseif (!isset($_SESSION['role'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['role'] = $stmt->fetchColumn() ?: 'user';
}

$is_privileged = in_array($_SESSION['role'], ['admin', 'manager', 'ceo']);

// Pagination settings
$items_per_page = isset($_GET['per_page']) ? min(max((int)$_GET['per_page'], 20), 500) : 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $items_per_page;

try {
    $sales_trend = $pdo->query("SELECT DATE(s.date_time) as date, SUM(s.amount_paid) as total 
        FROM sales s 
        JOIN stock st ON s.stock_id = st.id 
        GROUP BY DATE(s.date_time) 
        ORDER BY date 
        LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);

    $profit_margin = $pdo->query("SELECT SUM(s.amount_paid - (st.cost * s.tonnage / st.tonnage)) as profit, 
        SUM(s.amount_paid) as revenue 
        FROM sales s 
        JOIN stock st ON s.stock_id = st.id")->fetch(PDO::FETCH_ASSOC);

    $stock_turnover = $pdo->query("SELECT COUNT(st.id) / DATEDIFF(CURDATE(), MIN(st.date_added)) as turnover 
        FROM stock st")->fetchColumn();

    $procurement_costs = $pdo->query("SELECT SUM(cost) as total 
        FROM procurement 
        WHERE procurement_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

    $total_credit_sales = $pdo->query("SELECT COUNT(*) FROM sales s 
        JOIN stock st ON s.stock_id = st.id 
        WHERE s.amount_paid < (st.selling_price * s.tonnage)")->fetchColumn();

    $credit_sales = $pdo->query("SELECT s.*, c.name as product_name 
        FROM sales s 
        JOIN stock st ON s.stock_id = st.id 
        JOIN crops c ON st.crop_id = c.id 
        WHERE s.amount_paid < (st.selling_price * s.tonnage) 
        LIMIT $offset, $items_per_page")->fetchAll(PDO::FETCH_ASSOC);

    $total_pages = ceil($total_credit_sales / $items_per_page);

    $procurement_by_crop = $pdo->query("SELECT c.name, SUM(p.cost) as total_cost 
        FROM procurement p 
        JOIN crops c ON p.crop_id = c.id 
        WHERE p.procurement_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        GROUP BY c.id, c.name 
        LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    if ($is_privileged) {
        $dealer_performance = $pdo->query("SELECT u.username, COUNT(s.id) as sales_count, SUM(s.amount_paid) as total_sales 
            FROM sales s 
            JOIN stock st ON s.stock_id = st.id 
            JOIN users u ON st.dealer_id = u.id 
            WHERE s.date_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            GROUP BY u.id, u.username 
            ORDER BY total_sales DESC 
            LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        $seller_performance = $pdo->query("SELECT u.username, COUNT(s.id) as sales_count, SUM(s.amount_paid) as total_sales 
            FROM sales s 
            JOIN users u ON s.seller_id = u.id 
            WHERE s.date_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            GROUP BY u.id, u.username 
            ORDER BY total_sales DESC 
            LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container" style="padding-top: 80px; padding-bottom: 60px;">
        <?php include 'includes/sidebar.php'; ?>
        <main>
            <h2><?php echo $is_privileged ? 'Admin/Manager/CEO Dashboard' : 'Dashboard'; ?></h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (empty($sales_trend) && empty($profit_margin) && empty($stock_turnover)): ?>
                <div class="alert alert-info">No data available yet.</div>
            <?php else: ?>
                <div class="kpi-container mb-4">
                    <div class="kpi-card animate-card">
                        <i class="bi bi-graph-up-arrow"></i>
                        <h3>Sales Trend (Last 30 Days)</h3>
                        <canvas id="salesChart" height="150"></canvas>
                    </div>
                    <div class="kpi-card animate-card">
                        <i class="bi bi-pie-chart-fill"></i>
                        <h3>Profit Margin</h3>
                        <canvas id="profitChart" height="150"></canvas>
                    </div>
                    <div class="kpi-card animate-card">
                        <i class="bi bi-arrow-repeat"></i>
                        <h3>Stock Turnover</h3>
                        <p><?php echo number_format($stock_turnover ?: 0, 2); ?> times/day</p>
                        <canvas id="turnoverGauge" height="150"></canvas>
                    </div>
                    <div class="kpi-card animate-card">
                        <i class="bi bi-bar-chart-fill"></i>
                        <h3>Procurement Costs by Crop</h3>
                        <canvas id="procurementChart" height="150"></canvas>
                    </div>
                </div>

                <?php if ($is_privileged): ?>
                    <div class="kpi-container mb-4">
                        <div class="kpi-card animate-card">
                            <i class="bi bi-people-fill"></i>
                            <h3>Dealer Performance</h3>
                            <canvas id="dealerChart" height="150"></canvas>
                        </div>
                        <div class="kpi-card animate-card">
                            <i class="bi bi-person-lines-fill"></i>
                            <h3>Seller Performance</h3>
                            <canvas id="sellerChart" height="150"></canvas>
                        </div>
                        <div class="kpi-card animate-card">
                            <i class="bi bi-currency-exchange"></i>
                            <h3>Total Procurement Costs</h3>
                            <p><?php echo number_format($procurement_costs ?: 0, 2); ?> UGX</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="reports-container">
                    <div class="report-card" id="creditSales">
                        <h3>Credit Sales</h3>
                        <div class="list-header">
                            <span>Product</span><span>Tonnage</span><span>Amount Paid</span><span>Buyer</span>
                        </div>
                        <ul class="list-group">
                            <?php foreach ($credit_sales as $sale): ?>
                                <li class="list-group-item">
                                    <span><?php echo htmlspecialchars($sale['product_name']); ?></span>
                                    <span><?php echo $sale['tonnage']; ?> tons</span>
                                    <span><?php echo $sale['amount_paid']; ?> UGX</span>
                                    <span><?php echo htmlspecialchars($sale['buyer_name']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $items_per_page; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $items_per_page; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $items_per_page; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        <button class="btn btn-primary mt-3" onclick="exportReport('creditSales', 'Credit_Sales')">Export</button>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
    <?php if (!empty($sales_trend)): ?>
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($row) => "'{$row['date']}'", $sales_trend)); ?>],
            datasets: [{ label: 'Sales (UGX)', data: [<?php echo implode(',', array_column($sales_trend, 'total')); ?>], backgroundColor: 'rgba(0, 0, 128, 0.7)', borderColor: '#000080', borderWidth: 1 }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
    <?php endif; ?>

    <?php if (!empty($profit_margin)): ?>
    const profitCtx = document.getElementById('profitChart').getContext('2d');
    new Chart(profitCtx, {
        type: 'doughnut',
        data: {
            labels: ['Profit', 'Cost'],
            datasets: [{ data: [<?php echo $profit_margin['profit'] ?: 0; ?>, <?php echo ($profit_margin['revenue'] - $profit_margin['profit']) ?: 0; ?>], backgroundColor: ['#000080', '#f0f8ff'], borderColor: '#ffffff', borderWidth: 2 }]
        },
        options: { plugins: { legend: { position: 'bottom' }, title: { display: true, text: '<?php echo number_format(($profit_margin['profit'] / $profit_margin['revenue']) * 100, 2) ?: 0; ?>%' } } }
    });
    <?php endif; ?>

    <?php if ($stock_turnover !== false): ?>
    const turnoverCtx = document.getElementById('turnoverGauge').getContext('2d');
    new Chart(turnoverCtx, {
        type: 'doughnut',
        data: {
            labels: ['Turnover', 'Remaining'],
            datasets: [{ data: [<?php echo $stock_turnover ?: 0; ?>, 10 - <?php echo $stock_turnover ?: 0; ?>], backgroundColor: ['#000080', '#e9ecef'], borderWidth: 0 }]
        },
        options: { circumference: 180, rotation: -90, cutout: '70%', plugins: { legend: { display: false } } }
    });
    <?php endif; ?>

    <?php if (!empty($procurement_by_crop)): ?>
    const procurementCtx = document.getElementById('procurementChart').getContext('2d');
    new Chart(procurementCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($row) => "'{$row['name']}'", $procurement_by_crop)); ?>],
            datasets: [{ label: 'Cost (UGX)', data: [<?php echo implode(',', array_column($procurement_by_crop, 'total_cost')); ?>], backgroundColor: 'rgba(0, 0, 128, 0.7)', borderColor: '#000080', borderWidth: 1 }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
    <?php endif; ?>

    <?php if ($is_privileged && !empty($dealer_performance)): ?>
    const dealerCtx = document.getElementById('dealerChart').getContext('2d');
    new Chart(dealerCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($row) => "'{$row['username']}'", $dealer_performance)); ?>],
            datasets: [{ label: 'Total Sales (UGX)', data: [<?php echo implode(',', array_column($dealer_performance, 'total_sales')); ?>], backgroundColor: 'rgba(0, 0, 128, 0.7)', borderColor: '#000080', borderWidth: 1 }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
    <?php endif; ?>

    <?php if ($is_privileged && !empty($seller_performance)): ?>
    const sellerCtx = document.getElementById('sellerChart').getContext('2d');
    new Chart(sellerCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(fn($row) => "'{$row['username']}'", $seller_performance)); ?>],
            datasets: [{ label: 'Total Sales (UGX)', data: [<?php echo implode(',', array_column($seller_performance, 'total_sales')); ?>], backgroundColor: 'rgba(0, 0, 128, 0.7)', borderColor: '#000080', borderWidth: 1 }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
    <?php endif; ?>

    function exportReport(elementId, filename) {
        const element = document.getElementById(elementId);
        const format = prompt('Choose format (pdf/csv/excel):').toLowerCase();
        switch(format) {
            case 'pdf': html2pdf(element, { filename: `${filename}.pdf` }); break;
            case 'csv':
                const rows = Array.from(element.querySelectorAll('li')).map(li => Array.from(li.children).map(span => `"${span.textContent}"`).join(',')).join('\n');
                download(`${filename}.csv`, 'Product,Tonnage,Amount Paid,Buyer\n' + rows); break;
            case 'excel':
                const ws = XLSX.utils.aoa_to_sheet([['Product', 'Tonnage', 'Amount Paid', 'Buyer'], ...Array.from(element.querySelectorAll('li')).map(li => Array.from(li.children).map(span => span.textContent))]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
                XLSX.writeFile(wb, `${filename}.xlsx`); break;
        }
    }

    function download(filename, text) {
        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
        element.click();
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>