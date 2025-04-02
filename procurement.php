<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db_connect.php';

$is_admin = in_array($_SESSION['role'], ['admin', 'manager', 'ceo']);
$is_dealer = $_SESSION['role'] === 'dealer';

$items_per_page = isset($_GET['per_page']) ? min(max((int)$_GET['per_page'], 20), 500) : 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$branch_filter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

if (isset($_POST['add_procurement']) && ($is_admin || $is_dealer)) {
    $branch_id = $_POST['branch_id'];
    $crop_id = $_POST['crop_id'];
    $tonnage = floatval($_POST['tonnage']);
    $cost = floatval($_POST['cost']);
    $dealer_name = trim($_POST['dealer_name']);
    $contact = trim($_POST['contact']);
    $selling_price = floatval($_POST['selling_price']);
    $recorded_by = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO procurement (branch_id, crop_id, procurement_date, procurement_time, tonnage, cost, dealer_name, contact, selling_price, recorded_by) VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$branch_id, $crop_id, $tonnage, $cost, $dealer_name, $contact, $selling_price, $recorded_by]);
    
    $procurement_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO stock (procurement_id, branch_id, crop_id, tonnage, cost, selling_price, date_added) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
    $stmt->execute([$procurement_id, $branch_id, $crop_id, $tonnage, $cost, $selling_price]);
}

if (isset($_GET['delete_procurement']) && $is_admin) {
    $id = $_GET['delete_procurement'];
    $stmt = $pdo->prepare("DELETE FROM procurement WHERE id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM stock WHERE procurement_id = ?");
    $stmt->execute([$id]);
}

$query = "SELECT p.*, b.name AS branch_name, c.name AS crop_name FROM procurement p JOIN branches b ON p.branch_id = b.id JOIN crops c ON p.crop_id = c.id";
if (!$is_admin) {
    $query .= " WHERE p.recorded_by = {$_SESSION['user_id']}";
    if ($branch_filter) $query .= " AND p.branch_id = $branch_filter";
} elseif ($branch_filter) {
    $query .= " WHERE p.branch_id = $branch_filter";
}
$query .= " AND p.dealer_name LIKE '%$search%'";
$total_items = $pdo->query("SELECT COUNT(*) FROM ($query) as temp")->fetchColumn();
$procurements = $pdo->query("$query LIMIT $offset, $items_per_page")->fetchAll();
$total_pages = ceil($total_items / $items_per_page);

$branches = $pdo->query("SELECT * FROM branches" . ($is_dealer ? " WHERE id IN (SELECT branch_id FROM users WHERE id = {$_SESSION['user_id']})" : ""))->fetchAll();
$crops = $pdo->query("SELECT * FROM crops")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Procurement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <br><br><br>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        <main>
            <h2>Procurement</h2>
            <?php if ($is_admin || $is_dealer): ?>
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProcurementModal"><i class="bi bi-cart-plus"></i> Procure</button>
            <?php endif; ?>

            <?php if ($is_admin || count($branches) > 1): ?>
                <div class="mb-3">
                    <label for="branch_filter">Filter by Branch:</label>
                    <select id="branch_filter" class="form-control d-inline-block w-auto" onchange="location.href='?branch_id='+this.value+'&search=<?php echo urlencode($search); ?>&per_page=<?php echo $items_per_page; ?>';">
                        <option value="0">All Branches</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>" <?php if ($branch_filter == $branch['id']) echo 'selected'; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="search-bar mb-3">
                <form method="GET">
                    <input type="text" name="search" class="form-control" placeholder="Search by dealer..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">
                    <input type="hidden" name="branch_id" value="<?php echo $branch_filter; ?>">
                    <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-search"></i></button>
                </form>
            </div>

            <div class="list-header">
                <span>Branch</span>
                <span>Crop</span>
                <span>Tonnage</span>
                <span>Cost</span>
                <span>Dealer</span>
                <?php if ($is_admin): ?><span>Actions</span><?php endif; ?>
            </div>
            <ul class="list-group">
                <?php foreach ($procurements as $proc): ?>
                    <li class="list-group-item">
                        <span><?php echo htmlspecialchars($proc['branch_name']); ?></span>
                        <span><?php echo htmlspecialchars($proc['crop_name']); ?></span>
                        <span><?php echo $proc['tonnage']; ?> tons</span>
                        <span><?php echo $proc['cost']; ?> UGX</span>
                        <span><?php echo htmlspecialchars($proc['dealer_name']); ?></span>
                        <?php if ($is_admin): ?>
                            <div>
                                <button class="btn btn-warning btn-sm me-2" disabled><i class="bi bi-pencil"></i> Update</button>
                                <a href="?delete_procurement=<?php echo $proc['id']; ?>&page=<?php echo $page; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>&branch_id=<?php echo $branch_filter; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i> Delete</a>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>&branch_id=<?php echo $branch_filter; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>&branch_id=<?php echo $branch_filter; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>&branch_id=<?php echo $branch_filter; ?>">Next</a>
                        </li>
                    </ul>
                    <select name="per_page" class="form-control d-inline-block w-auto mt-2" onchange="location.href='?page=1&per_page='+this.value+'&search=<?php echo urlencode($search); ?>&branch_id=<?php echo $branch_filter; ?>';">
                        <?php foreach ([20, 50, 100, 200, 500] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php if ($items_per_page == $opt) echo 'selected'; ?>><?php echo $opt; ?> per page</option>
                        <?php endforeach; ?>
                    </select>
                </nav>
            <?php endif; ?>

            <?php if ($is_admin || $is_dealer): ?>
            <div class="modal fade" id="addProcurementModal" tabindex="-1" aria-labelledby="addProcurementModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content" style="background: #333; color: #fff;">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addProcurementModalLabel">Add Procurement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label>Branch:</label>
                                    <select name="branch_id" class="form-control" required>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Crop:</label>
                                    <select name="crop_id" class="form-control" required>
                                        <?php foreach ($crops as $crop): ?>
                                            <option value="<?php echo $crop['id']; ?>"><?php echo htmlspecialchars($crop['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Tonnage:</label>
                                    <input type="number" step="0.01" name="tonnage" class="form-control" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label>Cost (UGX):</label>
                                    <input type="number" step="0.01" name="cost" class="form-control" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label>Dealer Name:</label>
                                    <input type="text" name="dealer_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Contact:</label>
                                    <input type="text" name="contact" class="form-control" placeholder="+256123456789" required>
                                </div>
                                <div class="mb-3">
                                    <label>Selling Price (UGX):</label>
                                    <input type="number" step="0.01" name="selling_price" class="form-control" min="0" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="add_procurement" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <div class="graphics">
        <div class="shape circle"></div>
        <div class="shape semi-circle"></div>
        <div class="shape triangle"></div>
        <div class="shape circle"></div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>