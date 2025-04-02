<?php
session_start();
ob_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db_connect.php';

$items_per_page = isset($_GET['per_page']) ? min(max((int)$_GET['per_page'], 20), 500) : 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $items_per_page;

try {
    $stmt = $pdo->prepare("SELECT s.id, c.name as crop_name, s.tonnage, s.cost, s.selling_price, s.date_added 
        FROM stock s JOIN crops c ON s.crop_id = c.id 
        ORDER BY s.date_added DESC LIMIT :offset, :items_per_page");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_items = $pdo->query("SELECT COUNT(*) FROM stock")->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error loading stock: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container" style="padding-top: 80px; padding-bottom: 60px;">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h2>Stock</h2>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($stocks)): ?>
                <div class="alert alert-info">No stock data available.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr><th>ID</th><th>Crop</th><th>Tonnage</th><th>Cost</th><th>Selling Price</th><th>Date Added</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><?php echo $stock['id']; ?></td>
                                <td><?php echo htmlspecialchars($stock['crop_name']); ?></td>
                                <td><?php echo $stock['tonnage']; ?> tons</td>
                                <td><?php echo number_format($stock['cost'], 2); ?> UGX</td>
                                <td><?php echo number_format($stock['selling_price'], 2); ?> UGX</td>
                                <td><?php echo $stock['date_added']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

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
                        <select name="per_page" class="form-control d-inline-block w-auto mt-2" onchange="location.href='?page=1&per_page='+this.value;">
                            <?php foreach ([20, 50, 100, 200, 500] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php if ($items_per_page == $opt) echo 'selected'; ?>><?php echo $opt; ?> per page</option>
                            <?php endforeach; ?>
                        </select>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>