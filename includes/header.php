<?php
session_start();
ob_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';

// Ensure role is set and user ID 1 is admin
if ($_SESSION['user_id'] == 1 && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    $_SESSION['role'] = 'admin';
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = 1");
    $stmt->execute();
} elseif (!isset($_SESSION['role'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['role'] = $stmt->fetchColumn() ?: 'user';
}

$profile_picture = 'https://via.placeholder.com/40'; // Default fallback
try {
    $stmt = $pdo->prepare("SELECT profile_picture, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && !empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
        $profile_picture = $user['profile_picture'];
    }
    $user_role = $user['role'] ?? $_SESSION['role']; // Use session role if DB fetch fails
} catch (PDOException $e) {
    error_log("Profile picture fetch error: " . $e->getMessage());
    $user_role = $_SESSION['role']; // Fallback to session role
}
?>

<header class="header">
    <div class="header-content">
        <button class="sidebar-toggle" id="sidebarToggle">☰</button>
        <div class="logo-container">
            <div class="logo"></div>
            <h1 class="header-title">Golden Crop Distributors Ltd.</h1>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="avatar-container">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Avatar" class="avatar" id="avatarToggle">
                <div class="dropdown-menu" id="avatarDropdown">
                    <div class="dropdown-item disabled" style="color: #666;"><?php echo ucfirst($user_role); ?> Role</div>
                    <a href="update_profile.php" class="dropdown-item"><i class="bi bi-person-gear"></i> Update Profile</a>
                    <a href="logout.php" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>

<style>
.header {
    background: linear-gradient(90deg, #000080, #000066);
    padding: 15px 20px;
    color: whitesmoke;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    display: flex;
    justify-content: center;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 1200px;
    padding: 0 15px;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: whitesmoke;
    font-size: 1.8rem;
    cursor: pointer;
    padding: 0;
    margin-right: 15px;
}

.logo-container {
    display: flex;
    align-items: center;
}

.logo {
    width: 40px;
    height: 40px;
    background: url('https://img.icons8.com/color/80/000000/wheat.png') no-repeat center;
    background-size: contain;
    animation: pulse 2s infinite ease-in-out;
    margin-right: 10px;
}

.header-title {
    font-size: 1.8rem;
    margin: 0;
}

.avatar-container {
    position: relative;
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid whitesmoke;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 50px;
    background: #ffffff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    border-radius: 5px;
    min-width: 150px;
    z-index: 1001;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    padding: 10px 15px;
    color: #000080;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: background 0.3s;
}

.dropdown-item i {
    margin-right: 10px;
}

.dropdown-item:hover {
    background: #f0f8ff;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@media (max-width: 768px) {
    .header-title { font-size: 1.5rem; }
    .logo { width: 30px; height: 30px; }
    .avatar { width: 35px; height: 35px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const avatar = document.getElementById('avatarToggle');
    const dropdown = document.getElementById('avatarDropdown');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    if (avatar && dropdown) {
        avatar.addEventListener('click', () => {
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', (e) => {
            if (!avatar.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }
});
</script>
<?php ob_end_flush(); ?>