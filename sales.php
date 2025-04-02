<?php
session_start();
ob_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db_connect.php';

$is_admin = in_array($_SESSION['role'], ['admin', 'manager', 'ceo']);
$is_saler = $_SESSION['role'] === 'sales_agent'; // Updated to match schema

$items_per_page = isset($_GET['per_page']) ? min(max((int)$_GET['per_page'], 20), 500) : 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($_POST['save_sale']) && ($is_admin || $is_saler)) {
    $stock_id = $_POST['stock_id'];
    $tonnage = floatval($_POST['tonnage']);
    $amount_paid = floatval($_POST['amount_paid']);
    $buyer_name = trim($_POST['buyer_name']);
    $agent_name = trim($_POST['agent_name']);
    $buyer_contact = trim($_POST['buyer_contact']);
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO sales (stock_id, tonnage, amount_paid, buyer_name, agent_name, date_time, buyer_contact, user_id) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->execute([$stock_id, $tonnage, $amount_paid, $buyer_name, $agent_name, $buyer_contact, $user_id]);

        $stmt = $pdo->prepare("UPDATE stock SET tonnage = tonnage - ? WHERE id = ? AND tonnage >= ?");
        $stmt->execute([$tonnage, $stock_id, $tonnage]);
        $_SESSION['success'] = "Sale recorded successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error recording sale: " . $e->getMessage();
    }
    header("Location: sales.php");
    exit();
}

try {
    $query = "SELECT s.*, c.name AS product_name, st.selling_price 
        FROM sales s 
        JOIN stock st ON s.stock_id = st.id 
        JOIN crops c ON st.crop_id = c.id";
    if (!$is_admin) {
        $query .= " WHERE s.user_id = :user_id";
    }
    if ($search) {
        $query .= ($is_admin ? " WHERE" : " AND") . " s.buyer_name LIKE :search";
    }
    $stmt = $pdo->prepare("$query LIMIT :offset, :items_per_page");
    if (!$is_admin) $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_query = "SELECT COUNT(*) FROM sales s 
        JOIN stock st ON s.stock_id = st.id 
        JOIN crops c ON st.crop_id = c.id" . ($is_admin ? "" : " WHERE s.user_id = :user_id") . ($search ? ($is_admin ? " WHERE" : " AND") . " s.buyer_name LIKE :search" : "");
    $stmt = $pdo->prepare($total_query);
    if (!$is_admin) $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    if ($search) $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->execute();
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    $stocks = $pdo->query("SELECT st.id, c.name FROM stock st JOIN crops c ON st.crop_id = c.id WHERE st.tonnage > 0")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error loading sales: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container" style="padding-top: 80px; padding-bottom: 60px;">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h2>Sales</h2>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($is_admin || $is_saler): ?>
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSaleModal"><i class="bi bi-cart-plus"></i> Record Sale</button>
            <?php endif; ?>

            <div class="search-bar mb-3">
                <form method="GET">
                    <input type="text" name="search" class="form-control d-inline-block w-75" placeholder="Search by buyer..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">
                </form>
            </div>

            <?php if (empty($sales)): ?>
                <div class="alert alert-info">No sales data available.</div>
            <?php else: ?>
                <div class="list-header">
                    <span>Product</span><span>Tonnage</span><span>Amount Paid</span><span>Buyer</span><span>Agent</span><span>Date/Time</span>
                </div>
                <ul class="list-group">
                    <?php foreach ($sales as $sale): ?>
                        <li class="list-group-item">
                            <span><?php echo htmlspecialchars($sale['product_name']); ?></span>
                            <span><?php echo $sale['tonnage']; ?> tons</span>
                            <span><?php echo number_format($sale['amount_paid'], 2); ?> UGX</span>
                            <span><?php echo htmlspecialchars($sale['buyer_name']); ?></span>
                            <span><?php echo htmlspecialchars($sale['agent_name']); ?></span>
                            <span><?php echo $sale['date_time']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($total_pages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            </li>
                        </ul>
                        <select name="per_page" class="form-control d-inline-block w-auto mt-2" onchange="location.href='?page=1&per_page='+this.value+'&search=<?php echo urlencode($search); ?>';">
                            <?php foreach ([20, 50, 100, 200, 500] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php if ($items_per_page == $opt) echo 'selected'; ?>><?php echo $opt; ?> per page</option>
                            <?php endforeach; ?>
                        </select>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($is_admin || $is_saler): ?>
            <div class="modal fade" id="addSaleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Record Sale</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label>Product:</label>
                                    <select name="stock_id" class="form-select" required>
                                        <?php foreach ($stocks as $stock): ?>
                                            <option value="<?php echo $stock['id']; ?>"><?php echo htmlspecialchars($stock['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Tonnage:</label>
                                    <input type="number" step="0.01" name="tonnage" class="form-control" min="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label>Amount Paid:</label>
                                    <input type="number" step="0.01" name="amount_paid" class="form-control" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label>Buyer Name:</label>
                                    <input type="text" name="buyer_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Agent Name:</label>
                                    <input type="text" name="agent_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Buyer Contact:</label>
                                    <input type="text" name="buyer_contact" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="save_sale" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>