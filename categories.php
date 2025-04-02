<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db_connect.php';

$items_per_page = isset($_GET['per_page']) ? min(max((int)$_GET['per_page'], 20), 500) : 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($_POST['add_branch'])) {
    $branch_name = trim($_POST['branch_name']);
    if (!empty($branch_name)) {
        $stmt = $pdo->prepare("INSERT INTO branches (name) VALUES (?)");
        $stmt->execute([$branch_name]);
    }
}

if (isset($_POST['add_crop'])) {
    $crop_name = trim($_POST['crop_name']);
    if (!empty($crop_name)) {
        $stmt = $pdo->prepare("INSERT INTO crops (name) VALUES (?)");
        $stmt->execute([$crop_name]);
    }
}

if (isset($_GET['delete_branch'])) {
    $id = $_GET['delete_branch'];
    $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
    $stmt->execute([$id]);
}
if (isset($_GET['delete_crop'])) {
    $id = $_GET['delete_crop'];
    $stmt = $pdo->prepare("DELETE FROM crops WHERE id = ?");
    $stmt->execute([$id]);
}

$branch_count = $pdo->query("SELECT COUNT(*) FROM branches WHERE name LIKE '%$search%'")->fetchColumn();
$crop_count = $pdo->query("SELECT COUNT(*) FROM crops WHERE name LIKE '%$search%'")->fetchColumn();

$branches = $pdo->query("SELECT * FROM branches WHERE name LIKE '%$search%' LIMIT $offset, $items_per_page")->fetchAll();
$crops = $pdo->query("SELECT * FROM crops WHERE name LIKE '%$search%' LIMIT $offset, $items_per_page")->fetchAll();

$total_pages_branches = ceil($branch_count / $items_per_page);
$total_pages_crops = ceil($crop_count / $items_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container" style="padding-top: 80px; padding-bottom: 60px;">
        <?php include 'includes/sidebar.php'; ?>
        <main>
            <h2>Categories</h2>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addBranchModal"><i class="bi bi-plus-circle"></i> Add Branch</button>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCropModal"><i class="bi bi-plus-circle"></i> Add Crop</button>

            <div class="search-bar mb-3">
                <form method="GET">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">
                    <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-search"></i></button>
                </form>
            </div>

            <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="branches-tab" data-bs-toggle="tab" href="#branches" role="tab">Branches</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="crops-tab" data-bs-toggle="tab" href="#crops" role="tab">Crops</a>
                </li>
            </ul>

            <div class="tab-content" id="categoryTabContent">
                <div class="tab-pane fade show active" id="branches" role="tabpanel">
                    <div class="list-header mt-3">
                        <span>Name</span>
                        <span>Actions</span>
                    </div>
                    <ul class="list-group">
                        <?php foreach ($branches as $branch): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($branch['name']); ?></span>
                                <div>
                                    <button class="btn btn-warning btn-sm me-2" disabled><i class="bi bi-pencil"></i> Update</button>
                                    <a href="?delete_branch=<?php echo $branch['id']; ?>&page=<?php echo $page; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i> Delete</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($total_pages_branches > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages_branches; $i++): ?>
                                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php if ($page >= $total_pages_branches) echo 'disabled'; ?>">
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
                </div>
                <div class="tab-pane fade" id="crops" role="tabpanel">
                    <div class="list-header mt-3">
                        <span>Name</span>
                        <span>Actions</span>
                    </div>
                    <ul class="list-group">
                        <?php foreach ($crops as $crop): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($crop['name']); ?></span>
                                <div>
                                    <button class="btn btn-warning btn-sm me-2" disabled><i class="bi bi-pencil"></i> Update</button>
                                    <a href="?delete_crop=<?php echo $crop['id']; ?>&page=<?php echo $page; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i> Delete</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($total_pages_crops > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages_crops; $i++): ?>
                                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $items_per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php if ($page >= $total_pages_crops) echo 'disabled'; ?>">
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
                </div>
            </div>

            <div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content" style="background: #333; color: #fff;">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addBranchModalLabel">Add Branch</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label>Branch Name:</label>
                                    <input type="text" name="branch_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="add_branch" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addCropModal" tabindex="-1" aria-labelledby="addCropModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content" style="background: #333; color: #fff;">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCropModalLabel">Add Crop</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label>Crop Name:</label>
                                    <input type="text" name="crop_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="add_crop" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div class="graphics">
        <div class="shape circle" style="top: 5%; left: 15%;"></div>
        <div class="shape triangle" style="top: 20%; left: 80%;"></div>
        <div class="shape oval" style="top: 30%; left: 10%;"></div>
        <div class="shape semi-circle" style="top: 40%; left: 90%;"></div>
        <div class="shape circle" style="top: 50%; left: 25%;"></div>
        <div class="shape triangle" style="top: 60%; left: 70%;"></div>
        <div class="shape oval" style="top: 70%; left: 5%;"></div>
        <div class="shape semi-circle" style="top: 80%; left: 85%;"></div>
        <div class="shape circle" style="top: 90%; left: 30%;"></div>
        <div class="shape triangle" style="top: 10%; left: 60%;"></div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>