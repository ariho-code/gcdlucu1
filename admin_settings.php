<?php
session_start();
ob_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';

// Fetch branches and users
$branches = $pdo->query("SELECT id, name FROM branches ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT u.id, u.username, u.role, u.branch, b.name as branch_name 
    FROM users u LEFT JOIN branches b ON u.branch_id = b.id ORDER BY u.id")->fetchAll(PDO::FETCH_ASSOC);

// Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = trim($_POST['role']);
    $branch_id = trim($_POST['branch_id']);
    $branch = $pdo->query("SELECT name FROM branches WHERE id = $branch_id")->fetchColumn();
    $profile_picture = null;

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_pictures/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = uniqid() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $profile_picture = $upload_dir . $file_name;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, branch, branch_id, profile_picture) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $role, $branch, $branch_id, $profile_picture]);
        $_SESSION['success'] = "User added successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding user: " . $e->getMessage();
    }
    header("Location: admin_settings.php");
    exit();
}

// Update User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $branch_id = trim($_POST['branch_id']);
    $branch = $pdo->query("SELECT name FROM branches WHERE id = $branch_id")->fetchColumn();

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, branch = ?, branch_id = ? WHERE id = ?");
        $stmt->execute([$username, $role, $branch, $branch_id, $user_id]);
        $_SESSION['success'] = "User updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating user: " . $e->getMessage();
    }
    header("Location: admin_settings.php");
    exit();
}

// Delete User
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    if ($user_id != 1) { // Prevent deleting admin (ID 1)
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "User deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Cannot delete the primary admin user.";
    }
    header("Location: admin_settings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container" style="padding-top: 80px; padding-bottom: 60px;">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h2>Admin Settings</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">Add New User</button>

            <div class="card">
                <div class="card-header"><h4>Users List</h4></div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Role</th><th>Branch</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td><?php echo htmlspecialchars($user['branch_name'] ?: $user['branch']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateUserModal<?php echo $user['id']; ?>">Edit</button>
                                        <?php if ($user['id'] != 1): ?>
                                            <a href="?delete_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <!-- Update User Modal -->
                                <div class="modal fade" id="updateUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="username<?php echo $user['id']; ?>" class="form-label">Username</label>
                                                        <input type="text" class="form-control" id="username<?php echo $user['id']; ?>" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="role<?php echo $user['id']; ?>" class="form-label">Role</label>
                                                        <select class="form-select" id="role<?php echo $user['id']; ?>" name="role" required>
                                                            <option value="manager" <?php if ($user['role'] == 'manager') echo 'selected'; ?>>Manager</option>
                                                            <option value="sales_agent" <?php if ($user['role'] == 'sales_agent') echo 'selected'; ?>>Sales Agent</option>
                                                            <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                                            <option value="ceo" <?php if ($user['role'] == 'ceo') echo 'selected'; ?>>CEO</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="branch_id<?php echo $user['id']; ?>" class="form-label">Branch</label>
                                                        <select class="form-select" id="branch_id<?php echo $user['id']; ?>" name="branch_id" required>
                                                            <?php foreach ($branches as $branch): ?>
                                                                <option value="<?php echo $branch['id']; ?>" <?php if ($branch['id'] == $user['branch_id']) echo 'selected'; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="update_user" class="btn btn-primary">Update</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="manager">Manager</option>
                                        <option value="sales_agent">Sales Agent</option>
                                        <option value="admin">Admin</option>
                                        <option value="ceo">CEO</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select" id="branch_id" name="branch_id" required>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                </div>
                                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>