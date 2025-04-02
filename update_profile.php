<?php
session_start();
ob_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = $success = '';

try {
    $stmt = $pdo->prepare("SELECT username, password, profile_picture, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $error = "User not found.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $profile_picture = $user['profile_picture'] ?? null;

    if (!empty($new_password)) {
        if (empty($old_password)) {
            $error = "Please enter your old password to change it.";
        } elseif (!password_verify($old_password, $user['password'])) {
            $error = "Old password is incorrect.";
        }
    }

    if (empty($error) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['profile_picture']['size'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];

        if (!in_array($file_ext, $allowed)) {
            $error = "Only JPG, JPEG, and PNG files are allowed.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error = "File size must not exceed 2MB.";
        } else {
            $upload_dir = 'uploads/profile_pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $new_file_name = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_picture = $upload_path;
                if ($user['profile_picture'] && file_exists($user['profile_picture']) && $user['profile_picture'] !== $upload_path) {
                    unlink($user['profile_picture']);
                }
            } else {
                $error = "Failed to upload profile picture. Check directory permissions.";
            }
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] != UPLOAD_ERR_NO_FILE) {
        $error = "Error uploading file: " . $_FILES['profile_picture']['error'];
    }

    if (empty($error)) {
        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, profile_picture = ? WHERE id = ?");
                $stmt->execute([$username, $hashed_password, $profile_picture, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, profile_picture = ? WHERE id = ?");
                $stmt->execute([$username, $profile_picture, $user_id]);
            }
            $_SESSION['username'] = $username;
            $success = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error = "Database update failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Golden Crop Distributors Ltd.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background: #f0f8ff;
            overflow-x: hidden;
            margin: 0;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 130px);
            padding-top: 70px;
            padding-bottom: 60px;
        }

        main {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            margin-left: 200px;
            margin-top: 20px;
        }

        .sidebar.collapsed + main {
            margin-left: 60px;
        }

        h2 {
            color: #000080;
            text-align: center;
            margin-bottom: 20px;
        }

        .error { color: #dc3545; text-align: center; margin-bottom: 15px; }
        .success { color: #28a745; text-align: center; margin-bottom: 15px; }

        .form-control:focus {
            border-color: #000080;
            box-shadow: 0 0 5px rgba(0, 0, 128, 0.5);
        }

        .btn-primary {
            background: #000080;
            border: none;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #000066;
        }

        .current-pic {
            max-width: 100px;
            border-radius: 50%;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        @media (max-width: 768px) {
            .container { padding-top: 70px; }
            main { margin-left: 60px; margin-top: 20px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        <main>
            <h2>Update Profile</h2>
            <?php if (!empty($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($user['role'] ?? ''); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label for="old_password" class="form-label">Old Password (required to change password)</label>
                    <input type="password" class="form-control" id="old_password" name="old_password">
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                </div>
                <div class="mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture</label>
                    <?php if ($user['profile_picture'] && file_exists($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Current Profile Picture" class="current-pic">
                    <?php endif; ?>
                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png">
                    <small class="form-text text-muted">Max 2MB, JPG/PNG only</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
            </form>
        </main>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>